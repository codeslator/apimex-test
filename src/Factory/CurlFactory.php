<?php

namespace App\Factory;

final class CurlFactory
{
    public $url;
    public $headers;

    public function __construct($url, $headers = [])
    {
        $this->url = $url;
        $this->headers = $headers;
    }

    public function request($request, $method, $data = [])
    {
        $curl = curl_init();
        $dataString = '';
        if (!empty($data)) {
            $dataString = json_encode($data);
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . $request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $dataString,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                ...$this->headers
            ),
        ));

        $response = curl_exec($curl);

        //$responseJson = json_decode($response);
        // curl_close($curl);
        // print_r($response);
        return $response;
    }
}
