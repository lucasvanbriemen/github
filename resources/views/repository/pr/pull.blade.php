<style>
  .linked-issue-item {
    padding: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .issue-link {
    color: #0366d6;
    text-decoration: none;
    font-weight: 500;
    flex: 1;
  }

  .issue-link:hover {
    text-decoration: underline;
  }

  .badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
  }

  .badge-open {
    background-color: #28a745;
    color: white;
  }

  .badge-closed {
    background-color: #cb2431;
    color: white;
  }

  .diff-hunk {
    background-color: #0d1117;
    border: 1px solid #30363d;
    border-radius: 6px;
    padding: 0;
    margin: 10px 0;
    overflow-x: auto;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 12px;
    line-height: 1.45;
  }

  .diff-table {
    width: 100%;
    border-collapse: collapse;
  }

  .diff-row {
    display: table-row;
  }

  .diff-row.target-line {
    background-color: #1f2937;
    position: relative;
  }

  .diff-row.target-line::before {
    content: '→';
    position: absolute;
    left: -20px;
    color: #fbbf24;
    font-weight: bold;
  }

  .line-num {
    width: 40px;
    padding: 0 10px;
    text-align: right;
    color: #6e7681;
    background-color: #0d1117;
    border-right: 1px solid #21262d;
    user-select: none;
    vertical-align: top;
  }

  .line-num-old {
    background-color: #0d1117;
  }

  .line-num-new {
    background-color: #0d1117;
  }

  .diff-line {
    padding: 0 16px;
    white-space: pre;
    width: 100%;
  }

  tr:has(.diff-line.addition) .line-num-new,
  .diff-line.addition {
    background-color: #0d2e1f;
    color: #3fb950;
  }

  tr:has(.diff-line.deletion) .line-num-old,
  .diff-line.deletion {
    background-color: #3d1818;
    color: #f85149;
  }

  .diff-line.context {
    color: #8b949e;
    background-color: #0d1117;
  }

  .diff-line.hunk-header {
    background-color: #161b22;
    color: #58a6ff;
    font-weight: bold;
    padding: 5px 16px;
    text-align: center;
  }

  .resolved-badge {
    background-color: #8957e5;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-left: 8px;
  }

  .file-path {
    background-color: #161b22;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 12px;
    color: #58a6ff;
    margin: 0 4px;
  }
</style>

<x-app-layout class="repository-page issue-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  @include("repository.sidebar")

  <div class="main-content">
    <div class="issue-content">
      <div class="issue-header">
        <span>#{{ $pull->number }} {{ $pull->title }}</span>
        <div class="opened-by">
          @if($pull->author)
            <span class="author"><img src="{{ $pull->author->avatar_url }}" alt="{{ $pull->author->name }}"> {{ $pull->author->name }}</span>
          @endif
          <span class="state">State: {{ $pull->state }} @if($pull->draft) (draft) @endif</span>
        </div>
      </div>
      <div class='markdown-body'><x-markdown theme="github-dark">{!! $pull->body !!}</x-markdown></div>
    </div>

    @if ($pull->comments->count() > 0)
      <h3>Conversation</h3>
      @foreach ($pull->comments as $comment)
        <div class="issue-comment">
          <div class="comment-header">
            <span class="author"><img src="{{ $comment->user?->avatar_url }}" alt="{{ $comment->user?->name }}"> {{ $comment->user?->name }}</span>
            <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
          </div>
          <div class='markdown-body'><x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown></div>
        </div>
      @endforeach
    @endif

    <h3>Reviews</h3>
    @foreach ($pull->reviews as $review)
      <div class="issue-comment">
        <div class="comment-header">
          <span class="author"><img src="{{ $review->user?->avatar_url }}" alt="{{ $review->user?->name }}"> {{ $review->user?->name }}</span>
          <span class="created-at">{{ optional($review->submitted_at)->diffForHumans() }}</span>
          <span class="state">{{ strtoupper($review->state) }}</span>
        </div>
        @if($review->body)
          <div class='markdown-body'><x-markdown theme="github-dark">{!! $review->body !!}</x-markdown></div>
        @endif
      </div>
    @endforeach

    <h3>Review Comments</h3>
    @foreach ($pull->reviewComments as $comment)
      <div class="issue-comment">
        <div class="comment-header">
          <span class="author"><img src="{{ $comment->user?->avatar_url }}" alt="{{ $comment->user?->name }}"> {{ $comment->user?->name }}</span>
          <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
          @if($comment->path)
            <span class="file-path">{{ $comment->path }}</span>
          @endif
          @if($comment->resolved)
            <span class="resolved-badge">Resolved</span>
          @endif
        </div>

        <div class='markdown-body'><x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown></div>
      </div>
    @endforeach
  </div>

  <div class="issue-details">
    <div class="issue-detail assignees">
      <h3>Assignees</h3>
      @foreach ($pull->assignees as $assignee)
        <div class="assignee">
          <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
          <span>{{ $assignee->name }}</span>
        </div>
      @endforeach
    </div>

    <div class="issue-detail reviewers">
      <h3>Requested Reviewers</h3>
      @foreach ($pull->reviewers as $reviewer)
        @php
          $status = $reviewerStatuses[$reviewer->github_id]['state'] ?? 'PENDING';
        @endphp
        <div class="assignee">
          <img src="{{ $reviewer->avatar_url }}" alt="{{ $reviewer->name }}">
          <span>{{ $reviewer->name }}</span>
          <span class="badge">{{ ucfirst(strtolower($status)) }}</span>
        </div>
      @endforeach
    </div>

    @if(($linkedIssues ?? collect())->count() > 0)
      <div class="issue-detail linked-issues">
        <h3>Linked Issues</h3>
        @foreach ($linkedIssues as $li)
          <div class="linked-issue-item">
            <a href="/organization/{{ $organization->name ?? 'user' }}/{{ $repository->name }}/issues/{{ $li->number }}" class="issue-link">
              #{{ $li->number }} - {{ $li->title }}
            </a>
            <span class="badge {{ $li->state === 'open' ? 'badge-open' : 'badge-closed' }}">
              {{ ucfirst($li->state) }}
            </span>
          </div>
        @endforeach
      </div>
    @endif

    @if (is_array($pull->labels) && count($pull->labels) > 0)
      <div class="issue-detail labels">
        <h3>Labels</h3>
        @foreach ($pull->labels as $label)
          @php
            $hex = ltrim($label['color'] ?? '#000000', '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $luminance = (0.299*$r + 0.587*$g + 0.114*$b) / 255;
            $textColor = $luminance > 0.5 ? '#000000' : '#FFFFFF';
          @endphp

          <span class="label" style="background-color: #{{ $label['color'] ?? '000000' }}; color: {{ $textColor }};">
            {{ $label['name'] ?? 'label' }}
          </span>
        @endforeach
      </div>
    @endif
  </div>
</x-app-layout>
