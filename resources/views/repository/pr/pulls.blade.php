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
    <div class="issues-wrapper">
      @foreach ($pulls as $pull)
        <a href="{{ route('repository.pr.show', [$organization->name, $repository->name, $pull->number]) }}" class="issue-card card {{ $pull->state }}">
          {!! svg('pr') ?? '' !!}
          <div class='main-info'>
            <h3 class='issue-title'>
              #{{ $pull->number }} {{ $pull->title }}
            </h3>
            <span class='opened-by'>
              @if($pull->author)
                Opened by {{ $pull->author->name }} <img src="{{ $pull->author->avatar_url }}" alt="{{ $pull->author->name }}">
              @endif
            </span>
          </div>
          <div class="side-info">
            @foreach ($pull->assignees as $assignee)
              <img src="{{ $assignee->avatar_url }}" alt="{{ $assignee->name }}">
            @endforeach
          </div>
        </a>
      @endforeach

      {{ $pulls->links() }}
    </div>
  </div>
</x-app-layout>

