<?php

namespace App\Traits;

trait MessageTrait
{
    //send message
    public function sendMessage(string $phoneNumber, string $message)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.africastalking.com/version1/messaging',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "username=katznicho&to=" . urlencode($phoneNumber) . "&message=" . urlencode($message),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: 3b452fdc67868a7692aa9ecc947b082d385426f982cbde4cbd03044a16886348'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
