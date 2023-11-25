<?php
 
 namespace App\Traits;

 trait ResponseTrait
 {
     public function writeResponse($msg, $isend = false)
     {
         $resp_msg = '';
         if ($isend) {
             $resp_msg = "END " . $msg;
         } else {
             $resp_msg = "CON " . $msg;
         }
         return $resp_msg;
     }
 }

 

 