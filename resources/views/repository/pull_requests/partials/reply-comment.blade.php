<div class="issue-comment reply" data-comment="{{ $comment->github_id }}">
  <div class="comment-header">
    <span class="author"><img src="{{ $comment->author?->avatar_url }}" alt="{{ $comment->author?->name }}"> {{ $comment->author?->name }}</span>
    <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
  </div>
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
  </div>
</div>