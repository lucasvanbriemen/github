<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Services\RepositoryService;

class ReleaseController extends Controller
{
    public function index($organizationName, $repositoryName)
    {
    }

    public function show(string $organizationName, string $repositoryName, int $projectNumber)
    {
    }
}
