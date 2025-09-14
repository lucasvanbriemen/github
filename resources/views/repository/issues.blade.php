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
    <form method="GET" action="{{ route('repository.issues.index', [$organization->name, $repository->name]) }}" class="filters card" style="margin-bottom: 1rem; padding: 0.75rem; display:flex; gap:0.75rem; align-items: center;">
      <label>
        <span style="margin-right: 0.25rem;">State</span>
        <select name="state">
          <option value="open" {{ ($filters['state'] ?? 'open') === 'open' ? 'selected' : '' }}>Open</option>
          <option value="closed" {{ ($filters['state'] ?? 'open') === 'closed' ? 'selected' : '' }}>Closed</option>
          <option value="all" {{ ($filters['state'] ?? 'open') === 'all' ? 'selected' : '' }}>All</option>
        </select>
      </label>

      <label>
        <span style="margin-right: 0.25rem;">Assignee</span>
        <select name="assignee">
          <option value="" {{ empty($filters['assignee']) ? 'selected' : '' }}>Any</option>
          <option value="unassigned" {{ ($filters['assignee'] ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
          @foreach(($assignees) as $a)
            <option value="{{ $a->name }}" {{ ($filters['assignee'] ?? '') === $a->name ? 'selected' : '' }}>
              {{ $a->name }}
            </option>
          @endforeach
        </select>
      </label>

      <button type="submit" class="btn">Apply</button>
    </form>
    @foreach ($issues as $issue)
    @include("repository.issue_card")
    @endforeach

    {{ $issues->links() }}
  </div>
</x-app-layout>
