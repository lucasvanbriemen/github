<div class="sidebar">
  <a href="{{ route("repository.issues.show", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs('repository.issues.*') ? 'active' : '' }}">
    Show issues <span class="count">{{ $repository->openIssues->count() }}</span>
  </a>
  <a href="{{ route("organization.show", [$organization->name, $repository->name]) }}" class="sidebar-item {{ request()->routeIs('repository.pr.*') ? 'active' : '' }}">
    Show PR <span class="count">{{ $repository->pr_count }}</span>
  </a>
  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">
    Show org
  </a>
  <a href="{{ route("organization.show", $organization->name) }}" class="sidebar-item">
    Show org
  </a>
</div>
