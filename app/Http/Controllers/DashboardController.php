<?php

namespace App\Http\Controllers;

use App\Models\Organization;

class DashboardController extends Controller
{
  public function index()
  {

    $organizations = Organization::all();

    return view("dashboard.index", compact("organizations"));
  }
}
