<div class="issue-comment review state-{{ strtolower($review->state) }}" data-review="{{ $review->github_id }}">
  @include('repository.pull_requests.partials.comment-header', [
    'author' => $review->user,
    'createdAt' => $review->created_at,
    'state' => $review->state
  ])
  <div class="markdown-body">
    <x-markdown theme="github-dark">{!! $review->body !!}</x-markdown>
  </div>
</div>