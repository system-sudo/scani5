<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class JiraService
{
    // protected $baseUrl = 'https://secqureone-team-r8i3piuv.atlassian.net'; // Update with your Jira domain

    /**
     * Validate Jira credentials
     *
     * @param string $username
     * @param string $apiToken
     * @return bool
     */
    public function validateCredentials($username, $apiToken, $domain)
    {
        
        // Jira uses Basic Authentication with an API token

        $urlc = "https://{$domain}.atlassian.net";

        $response = Http::withBasicAuth($username, $apiToken)
            ->get($urlc . '/rest/api/2/myself');

        // Check if the credentials are valid based on the response status
        if ($response->successful()) {
            return true;
        }

        return false;
    }
}
