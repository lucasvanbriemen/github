<div class='notice-wrapper'>
  @foreach ($branchesForNotice as $branch)
    <div class='notice'>
      <span><strong>{{ $branch->name }}</strong> had recent pushes 5 seconds ago</span>
      <button class="button-primary-outline create-pr" data-branch="{{ $branch->name }}">Create Pull Request</button>
    </div>
  @endforeach
</div>