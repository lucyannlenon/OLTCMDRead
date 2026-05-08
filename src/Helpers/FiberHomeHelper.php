<?php

    namespace LLENON\OltInformation\Helpers;

    use Meklis\Network\Console\Helpers\DefaultHelper;

    class FiberHomeHelper extends  DefaultHelper
    {
        protected $prompt = '[%>#$]';
        protected $userPrompt = 'gin:';
        protected $passwordPrompt = 'ord:';
        protected $afterLoginCommands = [];
        protected $beforeLogoutCommands = [];
        protected $windowSize = [220, 9999];
        protected $paginationDetect = '/--More--|--- More ---/';

        /**
         * @return bool|mixed
         */
        public function isDoubleLoginPrompt()
        {
            if ($this->connectionType === 'ssh') {
                return true;
            }
            return $this->doubleLoginPrompt;
        }
    }
