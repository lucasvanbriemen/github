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
        <div class="issue-info">
          <h2>{{ $issue->title }}</h2>
          <p>
            <img class="avatar" src="{{ $issue->user->avatar_url }}" alt="{{ $issue->user->login }}">

            {{ $issue->created_at }}

            @foreach ($issue->labels as $label)
              <span class="label" style="background-color: #{{ $label->color }}; color: {{ (hexdec(substr($label->color, 0, 2)) * 0.299 + hexdec(substr($label->color, 2, 2)) * 0.587 + hexdec(substr($label->color, 4, 2)) * 0.114) > 186 ? '#000' : '#fff' }};">
                {{ $label->name }}
              </span>
            @endforeach
          </p>
        </div>

        <div class="assignees">
          @foreach ($issue->assignees as $assignee)
            <img class="avatar" src="{{ $assignee->avatar_url }}" alt="{{ $assignee->login }}">
          @endforeach
        </div>
      </a>
    @endforeach
  </div>
</x-app-layout>
