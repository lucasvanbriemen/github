<div class="filters card">
  <x-search-select
    :options="['open' => 'Open', 'closed' => 'Closed', 'all' => 'All']"
    placeholder="Search issues"
    name="issue-filter"
    selected="open" />
</div>
