<?php

namespace App\Http\Controllers;

use App\Services\RepositoryService;

class WorkflowJobController extends Controller
{
    public function getLogs($organizationName, $repositoryName, $jobId)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        $fullUrl = 'https://api.github.com/repos/' . $organization->name . '/' . $repository->name . '/actions/jobs/' . $jobId . '/logs';

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . config('services.github.access_token'),
            'Github-Api-Version: 2022-11-28',
            'User-Agent: github-gui',
        ];

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$responseBody) {
            return response()->json(['error' => 'Failed to fetch job logs: ' . ($error ?: "HTTP $httpCode")], 404);
        }

        return response($responseBody, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8'
        ]);
    }

    public function getWorkflowFile($organizationName, $repositoryName)
    {
        [$organization, $repository] = RepositoryService::getRepositoryWithOrganization($organizationName, $repositoryName);

        // Hardcoded workflow file path
        $fullUrl = 'https://api.github.com/repos/' . $organization->name . '/' . $repository->name . '/contents/.github/workflows/ruby.yml';

        $headers = [
            'Accept: application/vnd.github.v3.raw',
            'Authorization: Bearer ' . config('services.github.access_token'),
            'Github-Api-Version: 2022-11-28',
            'User-Agent: github-gui',
        ];

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$responseBody) {
            return response()->json(['error' => 'Failed to fetch workflow file: ' . ($error ?: "HTTP $httpCode")], 404);
        }

        return response($responseBody, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8'
        ]);
    }
}
