<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PullRequest;
use App\Models\Issue;
use App\Models\Repository;
use App\Helpers\ApiHelper;

class SyncPullRequestLinkedIssues extends Command
{
    protected $signature = 'github:sync-pr-linked-issues {repository} {pr_number}';
    protected $description = 'Sync linked issues for a specific pull request';

    public function handle()
    {
        $repoName = $this->argument('repository');
        $prNumber = $this->argument('pr_number');

        // Parse repository name (owner/repo format)
        $parts = explode('/', $repoName);
        if (count($parts) !== 2) {
            $this->error('Repository must be in owner/repo format');
            return 1;
        }

        [$owner, $repo] = $parts;

        // Fetch PR data with linked issues from GitHub API
        $response = ApiHelper::githubApi("/repos/{$owner}/{$repo}/pulls/{$prNumber}");

        if (!$response || !isset($response->id)) {
            $this->error("Failed to fetch pull request #{$prNumber} from GitHub");
            return 1;
        }

        // Get or create the repository
        $repository = Repository::where('name', $repo)->first();
        if (!$repository) {
            $this->error("Repository {$repo} not found in database");
            return 1;
        }

        // Get or update the pull request
        $pullRequest = PullRequest::updateOrCreate(
            ['github_id' => $response->id],
            [
                'repository_id' => $repository->github_id,
                'number' => $response->number,
                'title' => $response->title ?? '',
                'body' => $response->body ?? '',
                'state' => $response->state ?? 'open',
                'draft' => $response->draft ?? false,
                'node_id' => $response->node_id ?? null,
                'author_id' => $response->user->id ?? null,
                'source_branch' => $response->head->ref ?? null,
                'target_branch' => $response->base->ref ?? null,
            ]
        );

        $this->info("Processing PR #{$prNumber} (GitHub ID: {$pullRequest->github_id})");

        // Fetch linked issues using GraphQL
        if ($pullRequest->node_id) {
            $linkedIssues = $this->fetchLinkedIssuesViaGraphql($pullRequest->node_id, $owner, $repo);

            if (!empty($linkedIssues)) {
                foreach ($linkedIssues as $linkedIssue) {
                    // Fetch full issue data from REST API
                    $issueResponse = ApiHelper::githubApi("/repos/{$owner}/{$repo}/issues/{$linkedIssue['number']}");

                    if ($issueResponse && isset($issueResponse->id)) {
                        // Create or update the issue
                        $issue = Issue::updateOrCreate(
                            ['github_id' => $issueResponse->id],
                            [
                                'repository_id' => $repository->github_id,
                                'number' => $issueResponse->number,
                                'title' => $issueResponse->title ?? '',
                                'body' => $issueResponse->body ?? '',
                                'state' => $issueResponse->state ?? 'open',
                                'labels' => isset($issueResponse->labels) ? json_decode(json_encode($issueResponse->labels), true) : [],
                                'opened_by_id' => $issueResponse->user->id ?? null,
                                'last_updated' => $issueResponse->updated_at ?? now(),
                            ]
                        );

                        // Link the issue to the pull request
                        $pullRequest->linkedIssues()->syncWithoutDetaching([$issue->github_id]);
                        $this->info("Linked issue #{$issue->number}: {$issue->title}");
                    }
                }
            }
        }

        $linkedCount = $pullRequest->linkedIssues()->count();
        $this->info("Successfully synced {$linkedCount} linked issue(s) for PR #{$prNumber}");

        return 0;
    }

    private function fetchLinkedIssuesViaGraphql(string $nodeId, string $owner, string $repo): array
    {
        $query = <<<'GQL'
query ($id: ID!) {
  node(id: $id) {
    ... on PullRequest {
      id
      number
      closingIssuesReferences(first: 10) {
        nodes {
          id
          number
          title
          repository {
            name
            owner { login }
          }
        }
      }
      timelineItems(itemTypes: CONNECTED_EVENT, first: 20) {
        nodes {
          __typename
          ... on ConnectedEvent {
            subject {
              __typename
              ... on Issue {
                id
                number
                title
                repository {
                  name
                  owner { login }
                }
              }
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

            // Get issues that will be closed by this PR
            if (!empty($node->closingIssuesReferences->nodes)) {
                foreach ($node->closingIssuesReferences->nodes as $n) {
                    // Only include issues from the same repository
                    if (($n->repository->owner->login ?? '') === $owner &&
                        ($n->repository->name ?? '') === $repo) {
                        $issues[$n->number] = [
                            'number' => $n->number ?? null,
                            'title' => $n->title ?? '',
                            'node_id' => $n->id ?? '',
                        ];
                    }
                }
            }

            // Get connected issues from timeline
            if (!empty($node->timelineItems->nodes)) {
                foreach ($node->timelineItems->nodes as $ev) {
                    if (($ev->__typename ?? '') === 'ConnectedEvent' &&
                        isset($ev->subject) &&
                        ($ev->subject->__typename ?? '') === 'Issue') {
                        // Only include issues from the same repository
                        if (($ev->subject->repository->owner->login ?? '') === $owner &&
                            ($ev->subject->repository->name ?? '') === $repo) {
                            $issues[$ev->subject->number] = [
                                'number' => $ev->subject->number ?? null,
                                'title' => $ev->subject->title ?? '',
                                'node_id' => $ev->subject->id ?? '',
                            ];
                        }
                    }
                }
            }
        }

        return array_values($issues);
    }
}