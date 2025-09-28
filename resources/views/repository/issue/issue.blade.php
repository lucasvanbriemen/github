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
      <div class="issue-comment {{ $comment->resolved ? 'resolved' : '' }}" data-comment="{{ $comment->github_id }}">
        <div class="comment-header">
          <span class="author"><img src="{{ $comment->author?->avatar_url }}" alt="{{ $comment->author?->name }}"> {{ $comment->author?->name }}</span>
          <span class="created-at">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <div class='markdown-body'>
          <x-markdown theme="github-dark">{!! $comment->body !!}</x-markdown>
          
          @if(!$comment->resolved)
            <button class="button-primary resolve-comment" data-url="{{ route('api.repositories.issues.comment.resolve', [$organization->name, $repository->name, $issue->number, $comment->github_id]) }}" data-comment="{{ $comment->github_id }}">Mark as resolved</button>
          @else
            <button class="button-primary unresolve-comment" data-url="{{ route('api.repositories.issues.comment.unresolve', [$organization->name, $repository->name, $issue->number, $comment->github_id]) }}" data-comment="{{ $comment->github_id }}">Mark as unresolved</button>
          @endif
        </div>
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

    @if (count( (is_string($issue->labels) ? json_decode($issue->labels, true) : []) ) > 0)
      <div class="issue-detail labels">
        <h3>Labels</h3>
        @foreach (json_decode($issue->labels, true) as $label)
          <span class="label" style="background-color: {{ labelColor($label['color'])['background'] }}; color: {{ labelColor($label['color'])['text'] }};border: 1px solid {{ labelColor($label['color'])['border'] }};">
            {{ $label['name'] }}
          </span>
        @endforeach
      </div>
    @endif

    <div class="issue-detail linked-pull-request">
      
    </div>
  </div>

  <script> 
  window.start = "issue";
  window.issueId = "{{ $issue->number }}";
  window.repositoryName = "{{ $repository->name }}";
  window.organizationName = "{{ $organization->name }}";
  </script>
</x-app-layout>
