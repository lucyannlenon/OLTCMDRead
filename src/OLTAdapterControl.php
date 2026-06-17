<?php

    namespace LLENON\OltInformation;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;
    use LLENON\OltInformation\Enum\OltModel;
    use LLENON\OltInformation\OltInterfaces\OnuDataInterface;
    use RuntimeException;

    /**
     * @deprecated Legacy entry point. Use the versioned feature adapters
     *             (e.g. CDataFeatureAdapter) under LLENON\OltInformation\OLT\*.
     */
    class OLTAdapterControl implements OnuDataInterface
    {
        private OLT $OLT;
        private Client $client;

        public function __construct(OLT $OLT, Client $client)
        {

            $this->OLT = $OLT;
            $this->client = $client;
        }

        public function getDadosDoCliente(): Client
        {
            /** @var OnuDataInterface $oltAdapter */
            $oltAdapter = $this->getOltAdapter();


            $client = $oltAdapter->getDadosDoCliente();
            $client->setOltNome($this->OLT->nome);
            return $client;
        }

        private function getOltAdapter()
        {
            $olt = OltModel::ADAPTERS[$this->OLT->model];

            if (empty($olt))
                throw new RuntimeException("Olt passada {$this->OLT->model} não foi encontrada!");

            return new $olt($this->OLT, $this->client);
        }
    }
