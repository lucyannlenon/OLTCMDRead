#!/bin/bash

docker cp /var/srv/workspace/php/OLTCMDRead suporte-symfony-php-1:/tmp/OLTCMDRead
docker exec suporte-symfony-php-1 php /tmp/OLTCMDRead/examples/remove_teste_onus.php