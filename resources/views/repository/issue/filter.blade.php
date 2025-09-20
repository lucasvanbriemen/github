<div class="filters card">
  <x-search-select
    :options="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']"
    placeholder="Search issues"
    name="state"
    selected="open" />

    @php
      $options = [];
      foreach ($repository->users as $user) {
        $options[$user['id']] = $user['name'];
      }
    @endphp

    <x-search-select
    :options="$options"
    placeholder="Search assignee"
    name="assignee" />
</div>
