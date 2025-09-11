<x-app-layout class="repository-page">
  <x-slot:header>
    @if ($organization)
      <a href="{{ route('organization.show', $organization->name) }}">
        <img class="logo" src="{{ $organization->avatar_url }}" alt="{{ $organization->name }}">
      </a>
    @endif
    <a href="{{ route('repository.show', [$organization->name, $repository->name]) }}"><h1>{{ $repository->full_name }}</h1></a>
  </x-slot>

  @include("repository.sidebar")

  <div class="issues-list">
    @foreach ($issues as $issue)
      <a class="issue-wrapper" href={{ route("repository.issue.show", [$organization->name, $repository->name, $issue->number]) }}>
        <div class="issue">
          <h2>#{{ $issue->number }} - {{ $issue->title }}</h2>
          <p>{{ $issue->user->login }} -  <img class="avatar" src="{{ $issue->user->avatar_url }}" alt="{{ $issue->user->login }}"></p>
          <div class="assignees">
            @foreach ($issue->assignees as $assignee)
              <img class="avatar" src="{{ $assignee->avatar_url }}" alt="{{ $assignee->login }}">
            @endforeach
          </div>
        </div>
      </a>
    @endforeach
  </div>
</x-app-layout>
