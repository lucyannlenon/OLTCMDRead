#!/bin/bash

CONTAINER="suporte-symfony-php-1"
BASE="/tmp/OLTCMDRead"
USERNAME="teste"
VLAN="2009"

echo "Copiando arquivos para o container..."
docker cp /var/srv/workspace/php/OLTCMDRead "$CONTAINER":/tmp/OLTCMDRead

echo "Listando ONUs não autorizadas..."
ONUS=$(docker exec "$CONTAINER" php "$BASE/examples/list_unc_onus.php")

if [ -z "$ONUS" ]; then
    echo "Nenhuma ONU não autorizada encontrada."
    exit 0
fi

COUNT=$(echo "$ONUS" | wc -l)
echo "Encontradas $COUNT ONU(s). Iniciando provisionamento paralelo..."
echo ""

PIDS=()

while IFS=" " read -r PON SERIAL; do
    docker exec "$CONTAINER" php "$BASE/examples/provision_single_onu.php" \
        "$PON" "$SERIAL" "$USERNAME" "$VLAN" &
    PIDS+=($!)
done <<< "$ONUS"

# Aguarda todos os processos paralelos terminarem
for PID in "${PIDS[@]}"; do
    wait "$PID"
done

echo ""
echo "Provisionamento concluído."