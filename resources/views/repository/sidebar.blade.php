<div class="sidebar">
  <a href="{{ route("repository.show", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.show") ? "active" : "" }}">
    Code
  </a>
  
  <a href="{{ route("repository.issues.index", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.issues.*") ? "active" : "" }}">
    Show issues <span class="count">{{ $repository->issues->count() }}</span>
  </a>

  <a href="{{ route("organization.show", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.pr.*") ? "active" : "" }}">
    Show PR <span class="count">{{ $repository->pr_count }}</span>
  </a>

  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">
    Show org
  </a>

  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">
    Show org
  </a>

  <div class="users-list">
    @foreach ($repository->users as $user)
      <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="user" title="{{ $user->name }}" >
    @endforeach
  </div>
</div>
