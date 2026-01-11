<?php

namespace App\Http\Controllers;

use App\Helpers\ApiHelper;
use App\Models\WorkflowJob;

class WorkflowJobController extends Controller
{

    public function show($organizationName, $repositoryName, $jobId)
    {

        $job = WorkflowJob::find($jobId);
        $logs = $this->getLogs($organizationName, $repositoryName, $jobId);
        $workflowFile = $this->getWorkflowFile($organizationName, $repositoryName);

        $steps = json_decode($job->steps, true);

        return response()->json([]);
    }

    public function getLogs($organizationName, $repositoryName, $jobId)
    {
        $body = ApiHelper::githubApi('/repos/' . $organizationName . '/' . $repositoryName . '/actions/jobs/' . $jobId . '/logs', 'GET', null, true);
        return $body;
    }

    public function getWorkflowFile($organizationName, $repositoryName)
    {
        $route = '/repos/' . $organizationName . '/' . $repositoryName . '/contents/.github/workflows/ruby.yml';
        $body = ApiHelper::githubApi($route, 'GET', null, true, ['Accept' => 'application/vnd.github.v3.raw']);

        return $body;
    }
}
