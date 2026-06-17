<?php

    namespace LLENON\OltInformation\OltInterfaces;

    use LLENON\OltInformation\DTO\Client;
    use LLENON\OltInformation\DTO\OLT;

    /**
     * @deprecated Legacy ONU-data contract. Use the versioned command/feature
     *             layer under LLENON\OltInformation\OLT\* instead.
     */
    interface OnuDataInterface
    {

        /**
         * @param OLT $oltModel
         * @param Client $clientModel
         */
        public function __construct(OLT $oltModel, Client $clientModel);

        /**
         * @return Client
         */
        public function getDadosDoCliente(): Client;


    }
