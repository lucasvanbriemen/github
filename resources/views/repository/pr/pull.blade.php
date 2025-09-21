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
            <span class="path">{{ $comment->path }}</span>
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
        <div class="assignee">
          <img src="{{ $reviewer->avatar_url }}" alt="{{ $reviewer->name }}">
          <span>{{ $reviewer->name }}</span>
        </div>
      @endforeach
    </div>

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

