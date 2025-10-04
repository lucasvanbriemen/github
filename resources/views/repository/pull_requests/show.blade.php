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
    

    @include("repository.pull_requests.nav")
    
    <div class="pull-request-content">
      <div class="top-header">
        <div class="opened-by">
          <img src="{{ $pullRequest->openedBy->avatar_url }}" alt="{{ $pullRequest->openedBy->name }}"> {{ $pullRequest->openedBy->name }} wants to merge <span class="branch">{{ $pullRequest->head_branch }}</span> into <span class="branch">{{ $pullRequest->base_branch }}</span>
        </div>
        <button class="edit-pr button-primary-outline">
          {!! svg('pencil') !!}
        </button>
      </div>
      
      <div class="pull-request-header">
        <span id="pr-title">{{ $pullRequest->title }}</span>
        <span class="created-at">{{ $pullRequest->created_at->diffForHumans() }}</span>
      </div>
      <div class='markdown-body' id="pr-body"><x-markdown theme="github-dark">{!! $pullRequest->body !!}</x-markdown></div>
    </div>

    @foreach ($allComments as $item)
      <x-comment-renderer
        :item="$item"
        :organization="$organization"
        :repository="$repository"
        :pull-request="$pullRequest"
      />
    @endforeach
  </div>

  <div class="pull-request-details">
    <div class="pull-request-detail reviewers">
      <h3>Reviewers</h3>
      @foreach ($pullRequest->reviewers_data as $reviewer)
        <div class="reviewer state-{{ $reviewer->state }}">
          <span class='icon'>{!! svg($reviewer->state) !!}</span>
          <img src="{{ $reviewer->avatar_url }}" alt="{{ $reviewer->name }}">
          <span>{{ $reviewer->name }}</span>
        </div>
      @endforeach
    </div>

    <div class="pull-request-detail assignees">
      <h3>Assignees</h3>
      @foreach ($pullRequest->assignees_data as $assignee)
        <div class="assignee">
          <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
          <span>{{ $assignee->name }}</span>
        </div>
      @endforeach
    </div>

    <div class="pull-request-detail linked-issues">
    </div>
  </div>

  <script>
    window.start = "pull_request";
    window.pullRequestId = "{{ $pullRequest->number }}";
    window.repositoryName = "{{ $repository->name }}";
    window.organizationName = "{{ $organization->name }}";

    function editPR() {
      const title = prompt("Title:", document.getElementById('pr-title').textContent.trim());
      const body = prompt("Body:");

      if (!title && !body) return;

      const data = {};
      if (title) data.title = title;
      if (body) data.body = body;

      fetch(`/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestId}/edit`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
      })
      .then(res => res.json())
      .then(result => {
        if (result.status === 'success') {
          location.reload();
        } else {
          alert('Error: ' + result.message);
        }
      });
    }
  </script>
</x-app-layout>
