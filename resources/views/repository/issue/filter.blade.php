<div class="filters card">
  <x-search-select
    :options="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']"
    placeholder="Search issues"
    name="state"
    selected="open" />

    @php
      $options = [];
      foreach ($repository->users as $user) {
        $options[$user['user_id']] = $user['name'];
      }
    @endphp

    <x-search-select
    :options="$options"
    placeholder="Search assignee"
    name="assignee"
    :selected="\App\GithubConfig::USERID" />
</div>
