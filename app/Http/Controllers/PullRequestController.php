<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Helpers\ApiHelper;

class PullRequestController extends Controller
{
    public function index($organizationName, $repositoryName)
    {
        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }

        $repository = $query->firstOrFail();

        $pulls = PullRequest::where('repository_id', $repository->github_id)
            ->orderBy('github_id', 'desc')
            ->paginate(30);

        return view('repository.pr.pulls', [
            'organization' => $organization,
            'repository' => $repository,
            'pulls' => $pulls,
        ]);
    }

    public function show($organizationName, $repositoryName, $number)
    {
        if ($organizationName === 'user') {
            $organizationName = null;
        }

        $organization = Organization::where('name', $organizationName)->first();

        $query = Repository::where('name', $repositoryName);
        if ($organization) {
            $query->where('organization_id', $organization->github_id);
        }
        $repository = $query->firstOrFail();

        $pull = PullRequest::where('repository_id', $repository->github_id)
            ->where('number', $number)
            ->firstOrFail();

        // Build reviewer statuses: latest review state per reviewer
        $reviewerStatuses = [];
        $reviews = $pull->reviews()->with('user')->get();
        foreach ($reviews as $review) {
            $uid = $review->user_id;
            if (!isset($reviewerStatuses[$uid]) || (optional($review->submitted_at) > optional($reviewerStatuses[$uid]['submitted_at']))) {
                $reviewerStatuses[$uid] = [
                    'state' => strtoupper($review->state ?? 'PENDING'),
                    'submitted_at' => $review->submitted_at,
                ];
            }
        }

        // Attempt to fetch linked issues via GraphQL if missing locally
        $linkedIssues = $pull->linkedIssues()->get();
        if ($linkedIssues->isEmpty() && !empty($pull->node_id)) {
            $graph = $this->fetchLinkedIssuesViaGraphql($pull->node_id);
            if (!empty($graph)) {
                // Attach any issues we already have in DB
                foreach ($graph as $issue) {
                    // We expect Issue by github_id; only attach if exists
                    $existing = \App\Models\Issue::where('github_id', $issue['github_id'] ?? 0)->first();
                    if ($existing) {
                        $pull->linkedIssues()->syncWithoutDetaching([$existing->github_id]);
                    }
                }
                $linkedIssues = $pull->linkedIssues()->get();
            }
        }

        return view('repository.pr.pull', [
            'organization' => $organization,
            'repository' => $repository,
            'pull' => $pull,
            'reviewerStatuses' => $reviewerStatuses,
            'linkedIssues' => $linkedIssues,
        ]);
    }

    private function fetchLinkedIssuesViaGraphql(string $nodeId): array
    {
        $query = <<<'GQL'
query ($id: ID!) {
  node(id: $id) {
    ... on PullRequest {
      id
      number
      closingIssuesReferences(first: 10) { nodes { id number repository { name owner { login } } } }
      timelineItems(itemTypes: CONNECTED_EVENT, first: 20) {
        nodes {
          __typename
          ... on ConnectedEvent {
            subject {
              __typename
              ... on Issue { id number repository { name owner { login } } }
            }
          }
        }
      }
    }
  }
}
GQL;

        $res = ApiHelper::githubGraphql($query, ['id' => $nodeId]);
        $issues = [];
        if ($res && isset($res->data->node)) {
            $node = $res->data->node;
            if (!empty($node->closingIssuesReferences->nodes)) {
                foreach ($node->closingIssuesReferences->nodes as $n) {
                    $issues[] = [
                        'github_id' => self::decodeNodeId($n->id ?? '') ?? null,
                        'number' => $n->number ?? null,
                        'repo_owner' => $n->repository->owner->login ?? null,
                        'repo_name' => $n->repository->name ?? null,
                    ];
                }
            }
            if (!empty($node->timelineItems->nodes)) {
                foreach ($node->timelineItems->nodes as $ev) {
                    if (($ev->__typename ?? '') === 'ConnectedEvent' && isset($ev->subject) && ($ev->subject->__typename ?? '') === 'Issue') {
                        $issues[] = [
                            'github_id' => self::decodeNodeId($ev->subject->id ?? '') ?? null,
                            'number' => $ev->subject->number ?? null,
                            'repo_owner' => $ev->subject->repository->owner->login ?? null,
                            'repo_name' => $ev->subject->repository->name ?? null,
                        ];
                    }
                }
            }
        }
        return $issues;
    }

    private static function decodeNodeId(?string $nodeId): ?int
    {
        // GitHub node_id is base64 of type:id (e.g., Issue:123). We try to decode to get numeric id.
        if (!$nodeId) return null;
        $decoded = base64_decode($nodeId, true);
        if ($decoded && strpos($decoded, ':') !== false) {
            $parts = explode(':', $decoded);
            $id = end($parts);
            if (ctype_digit($id)) return (int) $id;
        }
        return null;
    }
}
