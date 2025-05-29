<?php

namespace Tests\Feature\Traits;

trait ApiRequestTrait
{
    protected $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYjAzOTMwMTJlZmQ0ZWVkYWMzMTlhZjc5OWU0YzljYjA4N2EwYTZmNTVjYTI2M2Y5MWMzY2ZkNWU0ZWY3MGY3NTg5NWY1ZDY2MmFhYzRiMjUiLCJpYXQiOjE3NDA1NDcxNzQuMzUyNTgsIm5iZiI6MTc0MDU0NzE3NC4zNTI1ODEsImV4cCI6MTc0MDYzMzU3NC4zNDAxNCwic3ViIjoiMSIsInNjb3BlcyI6WyIyZmFfc3RhdHVzX3ZlcmlmaWVkIl19.nQdKOlM3wJ9m7QVfyt5cwoVU-GSmX82uEHOtKTwcF6VtHwAiQudyscVskELwfrYdoLAcrbILjg7KEiH3ezvlt9OyqRrZ0q66rjhNjMtMcEYmPhd_u1G-00mB0QE3p1dEKHmWSXH3c6k0mBuIfAH7J0jmruXpu1UCqXhRYNLSrGvjj-xAogoZxiQDE-1h6rvqhl9YeLnW_YoxQLy3Eyy1CPwvlF99HYHxa4kuY7f0qrcnjgkBlTHSmGJuD8FErOkH8B8Y8C8U6fmf-JYIe67m8S7Ml6nBcJbW63c0m4xeMyoFatUPHNPG0BJErqnkgnIYxrqtagRF2o9Qe-Nm8v5Jt0rhI_gYM5bD87H6MRSnC0bEi7XbcXvoRAqHS4oAdRMmsZs5tIGKniNW5reEK5n55C-LTBco3PHm5YUgnffeGc4yF4It4hNIULq4QnJIUpOXVfwlVP_rw623vlGPYh-y0CjOVQW0WMwNrD5lo0VTGyGRPmnmmta4TVnJjKCMjTyVUkRPO0vkRmlbItukck82cFMO91d8ty9PAEwFf4nODkkyYsK_Vs4bjEH13_ppqYBfVAq_VFEYdsOY9T3mKGArv-qlnQ3OEDNA2wWQuxp_z-0wOXE8ffOvyiCmqVnsjYFNP4s5t0cWsDOFeelNvK5pwHLsPe9mrWmpHVHoHO26C_U';

    protected function apiRequest(string $method, string $url, array $data = [])
    {
        $methods = [
            'get' => 'getJson',
            'post' => 'postJson',
            'put' => 'putJson',
            'patch' => 'patchJson',
            'delete' => 'deleteJson',
        ];

        // Validate the method
        if (!isset($methods[$method])) {
            throw new \InvalidArgumentException("Invalid HTTP method: $method");
        }

        // Handle query parameters for GET requests
        if ($method === 'get' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            $data = []; // Clear data since GET requests don't have a request body
        }

        $headers = ['Authorization' => 'Bearer ' . $this->token];

        // Handle request based on method type
        $resp = ($method === 'get')
            ? $this->{$methods[$method]}($url, $headers)
            : $this->{$methods[$method]}($url, $data, $headers);

        $resp = $this->handleResponse($resp);
        return $resp;
    }

    protected function handleResponse($resp)
    {
        if ($resp->status() !== 200) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $caller = 'Unknown Method';

            foreach ($backtrace as $trace) {
                if (isset($trace['function']) && str_starts_with($trace['function'], 'test_')) {
                    $caller = $trace['function'];
                    break;
                }
            }

            // Extract  error message
            $errorMessage = $resp->json('message') ?? 'Something went wrong';

            if (is_array($errorMessage)) {
                $errorMessage = implode(', ', array_merge(...array_values($errorMessage)));
            }

            dump("Error in {$caller}: " . $errorMessage);
        }

        return $resp;
    }
}
