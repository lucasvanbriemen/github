<div class="header card">
  <button class="new-issue-button button-primary-outline">
    {!! svg('plus') !!}
  </button>

  <div class="header-filter">
    <x-search-select
      :options="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']"
      placeholder="Search issues"
      name="state"
      selected="open" />

    @php
      $options = [
        "any" => "Any",
        \App\GithubConfig::USERID => "Me"
      ];
      foreach ($repository->contributors as $repositoryUser) {

        if ($repositoryUser->githubUser->id == \App\GithubConfig::USERID) {
          continue;
        }

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
</div>