<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class FreshService
{


    public function validateCredentials($domain, $apiKey)
    {
        try {

            $urlc = "https://${domain}.freshservice.com/api/v2/tickets";
            // Encode the API key in base64 format with ':X' as password
            $encodedApiKey = base64_encode("$apiKey:X");

            // Make the API request with the Authorization header
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $encodedApiKey
            ])->get($urlc);

            // Check if the response is successful
            if ($response->successful()) {
                return ['success' => true]; // Valid credentials
            } elseif ($response->status() === 401) {
                return ['error' => 'Invalid API key or domain.']; // Unauthorized
            } elseif ($response->status() === 403) {
                return ['error' => 'Access forbidden. Check your API key permissions.']; // Forbidden
            } else {
                return ['error' => 'Invalid API key or domain.']; // Other errors
            }

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()]; // Return the exception message
        }
    }

}