<div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $comment->author,
    'createdAt' => $comment->created_at
  ])
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
    @if(!$comment->resolved)
      <button class="button-primary resolve-comment"
              data-url="{{ route('api.repositories.issues.comment.resolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->id }}">
        Mark as resolved
      </button>
    @else
      <button class="button-primary unresolve-comment"
              data-url="{{ route('api.repositories.issues.comment.unresolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->id }}">
        Mark as unresolved
      </button>
    @endif
  </div>
</div>