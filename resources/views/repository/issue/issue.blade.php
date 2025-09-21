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
        <span>{{ $issue->title }}</span>
        <div class="opened-by">
          <span class="author"><img src="{{ $issue->openedBy->avatar_url }}" alt="{{ $issue->openedBy->name }}"> {{ $issue->openedBy->name }}</span>
          <span class="created-at">{{ $issue->created_at->diffForHumans() }}</span>
        </div>
      </div>
      <div class='markdown-body'><x-markdown theme="github-dark">{!! $issue->body !!}</x-markdown></div>
    </div>

    @foreach ($issue->comments as $comment)
      <div class="issue-comment">
        <div class="comment-header">
          <span class="author"><img src="{{ $comment->author->avatar_url }}" alt="{{ $comment->author->name }}"> {{ $comment->author->name }}</span>
          <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <div class='markdown-body'><x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown></div>
      </div>
    @endforeach
  </div>

  <div class="issue-details">
    <div class="issue-detail assignees">
      <h3>Assignees</h3>
      @foreach ($issue->assignees_data as $assignee)
        <div class="assignee">
          <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
          <span>{{ $assignee->name }}</span>
        </div>
      @endforeach
    </div>

    <div class="issue-detail labels">
      <h3>Labels</h3>
      @foreach (json_decode($issue->labels, true) as $label)
        @php
          $hex = ltrim($label['color'], '#');
          $r = hexdec(substr($hex, 0, 2));
          $g = hexdec(substr($hex, 2, 2));
          $b = hexdec(substr($hex, 4, 2));
          $luminance = (0.299*$r + 0.587*$g + 0.114*$b) / 255;
          $textColor = $luminance > 0.5 ? '#000000' : '#FFFFFF';
        @endphp

        <span class="label" style="background-color: #{{ $label['color'] }}; color: {{ $textColor }};">
          {{ $label['name'] }}
        </span>
      @endforeach
    </div>
  </div>
</x-app-layout>
