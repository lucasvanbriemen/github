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

        <div class="edit-button-wrapper">
          <button class="edit-pr button-primary-outline" data-editing="0">{!! svg('pencil') !!}</button>
          <button class="save-edit button-primary" data-editing="1">save</button>
          <button class="cancel-edit button-primary-outline" data-editing="1">cancel</button>
        </div>
      </div>
      
      <div class="pull-request-header">
        <span id="pr-title" data-raw="{{ $pullRequest->title }}" data-editing="0"><x-markdown theme="github-dark">{!! $pullRequest->title !!}</x-markdown></span>
        <x-compoment
          name="input"
          :options="[
            'type' => 'text',
            'value' => $pullRequest->title,
            'id' => 'edit-pr-title',
            'label' => 'Title',
            'wrapperOptions' => ['data-editing' => 1]
          ]"
        />
      
        <span class="created-at" data-editing="0">{{ $pullRequest->created_at->diffForHumans() }}</span>
      </div>

      <div class='markdown-body' id="pr-body" data-raw="{{ $pullRequest->body }}">
        <x-markdown theme="github-dark" data-editing="0">{!! $pullRequest->body !!}</x-markdown>
        <textarea id="edit-pr-body" data-editing="1"></textarea>
      </div>

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
    window.pullRequestNumber = "{{ $pullRequest->number }}";
    window.repositoryName = "{{ $repository->name }}";
    window.organizationName = "{{ $organization->name }}";
  </script>
</x-app-layout>
