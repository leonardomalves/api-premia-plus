<?php

namespace App\Services\Core;

use Ixudra\Curl\Facades\Curl;

class HttpClient
{

    public function apiRequest($endpoint, $data, $headers, $method)
    {
        $response = Curl::to($endpoint)
            ->withHeaders($headers)
            ->withData($data)
            ->asJson()
            ->returnResponseObject();

        return match ($method) {
            'POST' => $response->post(),
            'DELETE' => $response->delete(),
            'PATCH' => $response->patch(),
            default => $response->get()
        };
    }


}