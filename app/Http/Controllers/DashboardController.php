<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Repository;
use App\Models\Issue;
use Github\Api\CurrentUser;

class DashboardController extends Controller
{
  public function index()
  {
    $repositories = Repository::with("organization")->orderBy("last_updated", "desc")->get();
    $issues = [];
    
    foreach ($repositories as $repo) {
      // Get any issue that has been updated since the last_activity and where im assigned to me
      $repoIssues = $repo->issues()->get();

      foreach ($repoIssues as $issue) {
        $issues[] = $issue;
      }
    }

    return view("dashboard.show", compact("repositories", "issues"));
  }
}
