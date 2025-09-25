<x-app-layout class="repository-page pull-request-page">
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
    <div class="pull-request-content">
      <div class="pull-request-header">
        <span>{{ $pullRequest->title }}</span>
        <div class="opened-by">
          <span class="author"><img src="{{ $pullRequest->openedBy->avatar_url }}" alt="{{ $pullRequest->openedBy->name }}"> {{ $pullRequest->openedBy->name }}</span>
          <span class="created-at">{{ $pullRequest->created_at->diffForHumans() }}</span>
        </div>
      </div>
      <div class='markdown-body'><x-markdown theme="github-dark">{!! $pullRequest->body !!}</x-markdown></div>
    </div>
  </div>

  <div class="pull-request-details">
    <div class="pull-request-detail assignees">
      <h3>Assignees</h3>
      @foreach ($pullRequest->assignees_data as $assignee)
        <div class="assignee">
          <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
          <span>{{ $assignee->name }}</span>
        </div>
      @endforeach
    </div>

    @if (count( (is_string($pullRequest->labels) ? json_decode($pullRequest->labels, true) : []) ) > 0)
      <div class="pull-request-detail labels">
        <h3>Labels</h3>
        @foreach (json_decode($pullRequest->labels, true) as $label)
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
    @endif
  </div>
</x-app-layout>
