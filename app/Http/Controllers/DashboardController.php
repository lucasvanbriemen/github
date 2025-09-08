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

    RepositoryController::updateRepositories();

    $repositories = Repository::all();

    return view("dashboard.index", compact("organizations", "repositories"));
  }
}
