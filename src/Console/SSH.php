<?php

    namespace LLENON\OltInformation\Console;

    use Exception;

    class SSH extends \Meklis\Network\Console\SSH
    {
        function login($username, $password)
        {
            if ($sizes = $this->helper->getWindowSize()) {
                $wide = $sizes[0];
                $high = $sizes[1];
                $sizeType = SSH2_TERM_UNIT_CHARS;
            } else {
                $wide = null;
                $high = null;
                $sizeType = null;
            }

            if (!ssh2_auth_password($this->connection, $username, $password)) {
                throw new Exception("Error auth");
            }
            $this->session = ssh2_shell($this->connection, "vt102");
            try {
                if ($wide && $high) {
                    $this->setWindowSize($wide, $high);
                }
            } catch (Exception $e) {
            }
            try {
                $this->waitPrompt();
                if ($this->helper->isDoubleLoginPrompt()) {
                    $this->waitPrompt();
                }
            } catch (Exception $e) {
                throw new Exception("Login failed. ({$e->getMessage()})");
            }
            return $this->runAfterLoginCommands();
        }

    }
