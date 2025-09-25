<div class="sidebar">
  <a href="{{ route("repository.show", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.show") ? "active" : "" }}">
    Code
  </a>
  
  <a href="{{ route("repository.issues.index", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.issues.*") ? "active" : "" }}">
    Show issues <span class="count">{{ $repository->issues("open", "any")->count() }}</span>
  </a>

  <a href="{{ route("repository.prs.index", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs("repository.prs.*") ? "active" : "" }}">
    Show PR's <span class="count">{{ $repository->pullRequests("open", "any")->count() }}</span>
  </a>
</div>
