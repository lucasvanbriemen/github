<form method="GET" action="{{ route('repository.issues.index', [$organization->name, $repository->name]) }}" class="filters card" style="margin-bottom: 1rem; padding: 0.75rem; display:flex; gap:0.75rem; align-items: center;">
  <div class="search-select-wrapper">
    <select name="state" class="search-select">
      <option value="open" {{ ($filters['state'] ?? 'open') === 'open' ? 'selected' : '' }}>Open</option>
      <option value="closed" {{ ($filters['state'] ?? 'open') === 'closed' ? 'selected' : '' }}>Closed</option>
      <option value="all" {{ ($filters['state'] ?? 'open') === 'all' ? 'selected' : '' }}>All</option>
    </select>

    <div class="select-ui-wrapper">
      <input type="text" name="search" value="{{ $filters['search'] ?? "open" }}" class="search-input">
      <div class='option-wrapper'>
        <div class="option-item" data-value="open">
          <div class="main-text">Open</div>
          <div class="sub-text">{{ $repository->issues("open")->count() }}</div>
        </div>
        <div class="option-item" data-value="closed">
          <div class="main-text">Closed</div>
          <div class="sub-text">{{ $repository->issues("closed")->count() }}</div>
        </div>
        <div class="option-item" data-value="all">
          <div class="main-text">Any</div>
          <div class="sub-text">{{ $repository->issues("all")->count() }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="search-select-wrapper">
    <select name="assignee" class="search-select">
      <option value="" {{ empty($filters['assignee']) ? 'selected' : '' }}>Any</option>
      <option value="unassigned" {{ ($filters['assignee'] ?? '') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
      @foreach(($assignees) as $a)
        <option value="{{ $a->name }}" {{ ($filters['assignee'] ?? '') === $a->name ? 'selected' : '' }}>
          {{ $a->name }}
        </option>
      @endforeach
    </select>

    <div class="select-ui-wrapper">
      <input type="text" name="search" value="{{ $filters['assignee'] ?? "any" }}" class="search-input">
      <div class='option-wrapper'>
        @foreach($assignees as $assignee)
          <div class='option-item' data-value='{{ $assignee->name }}'>
            <div class="main-text">{{ $assignee->name }}</div>
            <div class="sub-text">
              <img src='{{ $assignee->avatar_url }}' alt='{{ $assignee->name }}' style='width:16px; height:16px; border-radius:50%; vertical-align:middle; margin-right:4px;'>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>


  <button type="submit" class="button-primary">Apply</button>
</form>
