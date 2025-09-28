<div class="issue-comment reply {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->id }}">
  <div class="comment-header">
    <span class="author"><img src="{{ $comment->author?->avatar_url }}" alt="{{ $comment->author?->name }}"> {{ $comment->author?->name }}</span>
    <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
  </div>
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>

    @if($comment->resolved)
      <button class="button-primary unresolve-comment"
              data-url="{{ route('api.repositories.pull_requests.comment.unresolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->github_id }}">
        Mark as unresolved
      </button>
    @else
      <button class="button-primary resolve-comment"
              data-url="{{ route('api.repositories.pull_requests.comment.resolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->github_id }}">
        Mark as resolved
      </button>
    @endif
  </div>
</div>