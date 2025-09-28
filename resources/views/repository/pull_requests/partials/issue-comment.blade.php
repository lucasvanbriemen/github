<div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->github_id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $comment->author,
    'createdAt' => $comment->created_at
  ])
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
    @if(!$comment->resolved)
      <button class="button-primary resolve-comment"
              data-url="{{ route('api.repositories.issues.comment.resolve', [$organization->name, $repository->name, $pullRequest->number, $comment->github_id]) }}"
              data-comment="{{ $comment->github_id }}">
        Mark as resolved
      </button>
    @else
      <button class="button-primary unresolve-comment"
              data-url="{{ route('api.repositories.issues.comment.unresolve', [$organization->name, $repository->name, $pullRequest->number, $comment->github_id]) }}"
              data-comment="{{ $comment->github_id }}">
        Mark as unresolved
      </button>
    @endif
  </div>
</div>