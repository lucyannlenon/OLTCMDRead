<?php

namespace LLENON\OltInformation\OLT\Fiberhome\Command\DataProcessors;

use LLENON\OltInformation\Exceptions\OltCommandException;
use LLENON\OltInformation\OLT\Utils\Parse\StringParserInterface;

 abstract class AbstractStringParser implements StringParserInterface
{
     /**
      * @param string $input
      * @return array
      * @throws OltCommandException
      */
     public function parse(string $input): array
     {
         if (preg_match('/\sENDESC=No error$/', $input) || !str_contains($input, 'ENDESC')) {
             return $this->localParse($input);
         } elseif (preg_match('/ENDESC=([^\s]+(?:\s+[^\s]+)*)\s+EADD=(.+)/', $input, $matches)) {
             $message = "Error: $matches[1]: desc $matches[2]\n";
         } else {
             $message = "Error not found in the string.\n";
         }

         $this->handlerException($input, $message);
     }

     private function handlerException(string $cause, string $error): void
     {
         throw  new OltCommandException($error, $cause);
     }
     protected abstract function localParse(string $input):array;
 }