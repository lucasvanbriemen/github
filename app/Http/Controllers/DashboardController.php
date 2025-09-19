<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Repository;

class DashboardController extends Controller
{
  public function index()
  {
    $repositories = Repository::with("organization")->orderBy("last_updated", "desc")->get();

    return view("dashboard.show", compact("repositories"));
  }
}
