<div class="issue-comment review state-{{ strtolower($review->state) }} {{ $review->resolved ? 'resolved' : '' }}" data-review="{{ $review->id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $review->user,
    'createdAt' => $review->created_at,
    'state' => $review->state
  ])
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $review->body !!}</x-markdown>

    @if(!$review->resolved)
      <button class="button-primary resolve-comment"
              data-url="{{ route('api.repositories.pull_requests.review.resolve', [$organization->name, $repository->name, $pullRequest->number, $review->id]) }}"
              data-comment="{{ $review->github_id }}">
        Mark as resolved
      </button>
    @else
      <button class="button-primary unresolve-comment"
              data-url="{{ route('api.repositories.pull_requests.review.unresolve', [$organization->name, $repository->name, $pullRequest->number, $review->id]) }}"
              data-comment="{{ $review->github_id }}">
        Mark as unresolved
      </button>
    @endif
  </div>
</div>