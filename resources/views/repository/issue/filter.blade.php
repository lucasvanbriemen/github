<div class="filters card">
  <x-search-select
    :options="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']"
    placeholder="Search issues"
    name="state"
    selected="open" />

    @php
      $options = ["any" => "Any"];
      foreach ($repository->users as $repositoryUser) {
        if ($repositoryUser->githubUser) {
          $options[$repositoryUser->user_id] = $repositoryUser->githubUser->name;
        }
      }
    @endphp

    <x-search-select
    :options="$options"
    placeholder="Search assignee"
    name="assignee"
    :selected="\App\GithubConfig::USERID" />
</div>
