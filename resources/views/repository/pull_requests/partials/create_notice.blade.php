<div class='notice-wrapper'>
  @foreach ($branchesForNotice as $branch)
    <div class='notice'>
      <span><strong>{{ $branch->name }}</strong> had recent pushes @if($branch->commits) {{ $branch->commits->last()->closed_at->diffForHumans() }} @endif</span>
      <button class="button-primary-outline create-pr" data-branch="{{ $branch->name }}">Create Pull Request</button>
    </div>
  @endforeach
</div>