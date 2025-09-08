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
    $repositories = Repository::orderBy("last_updated", "desc")->get();

    return view("dashboard.index", compact("organizations", "repositories"));
  }
}
