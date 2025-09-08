<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Repository;
use App\Http\Controllers\RepositoryController;

class DashboardController extends Controller
{
  public function index()
  {
    $organizations = Organization::all();


    // Update repositories
    RepositoryController::updateRepositories();

    $repositories = Repository::all();

    dump($repositories);

    return view("dashboard.index", compact("organizations", "repositories"));
  }
}
