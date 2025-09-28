<div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->github_id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $comment->author,
    'createdAt' => $comment->created_at
  ])
  <div class="markdown-body">
    <div class="diff-hunk">
      @foreach (commentDiffHunk($comment->diff_hunk, $comment->line_start, $comment->line_end) as $line)
        <div class="line">{{ $line }}</div>
      @endforeach
    </div>

    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>

    @foreach ($replies as $reply)
      @include('repository.pull_requests.partials.reply-comment', ['comment' => $reply])
    @endforeach
  </div>
</div>