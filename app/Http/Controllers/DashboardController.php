<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\OrganizationController;

class DashboardController extends Controller
{
  public function index()
  {

    OrganizationController::updateOrganizations();

    return view("dashboard.index");
  }
}
