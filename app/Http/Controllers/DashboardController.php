<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $repositories = Repository::with('organization')->orderBy('last_updated', 'desc')->get();
        $issues = [];

        foreach ($repositories as $repo) {
            // Get any issue that has been updated since the last_activity and where im assigned to me
            $repoIssues = $repo->issues()->get();

            foreach ($repoIssues as $issue) {

                // Issue that hasn't been updated since my last activity (unless it was updated in the last hour)
                if ($issue->updated_at <= currentUser()->last_activity && $issue->updated_at <= now()->subHour()) {
                    continue;
                }

                $issues[] = $issue;
            }
        }

        return view('dashboard.show', compact('repositories', 'issues'));
    }
}
