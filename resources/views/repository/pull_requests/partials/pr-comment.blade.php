@props([
  'hideDiffHunks' => false,
])

<div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $comment->author,
    'createdAt' => $comment->created_at
  ])
  <div class="markdown-body">
    @if ($comment->diff_hunk && $hideDiffHunks !== true)
      <div class="diff-hunk">
        <span class="file-name">{{ $comment->path }}</span>
        @foreach (commentDiffHunk($comment->diff_hunk, $comment->line_start, $comment->line_end) as $line)
          <div class="line">{{ $line }}</div>
        @endforeach
      </div>
    @endif

    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>

    @if($comment->resolved)
      <button class="button-primary unresolve-comment"
              data-url="{{ route('api.repositories.pull_requests.comment.unresolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->id }}">
        Mark as unresolved
      </button>
    @else
      <button class="button-primary resolve-comment"
              data-url="{{ route('api.repositories.pull_requests.comment.resolve', [$organization->name, $repository->name, $pullRequest->number, $comment->id]) }}"
              data-comment="{{ $comment->id }}">
        Mark as resolved
      </button>
    @endif

    @foreach ($replies as $reply)
      @include('repository.pull_requests.partials.reply-comment', ['comment' => $reply])
    @endforeach
  </div>
</div>