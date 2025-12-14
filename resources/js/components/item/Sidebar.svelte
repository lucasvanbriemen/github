<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';

  let { item, isPR, isLoading, params = {} } = $props();
  let activeItem = $state('Issues');

  let organization = $derived(params.organization);
  let repository = $derived(params.repository);
  
  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  onMount(async () => {
    if (isPR) {
      activeItem = 'Pull Requests';
    }
  });

  function reRequestReviewer(userId) {
    api.post(route('organizations.repositories.pr.add.reviewers', {organization, repository, number: item.number}),
      { reviewers: [userId] }
    )
  }

</script>

<Sidebar {params} {activeItem}>
  {#if !isLoading}
    <SidebarGroup title="Assignees">
      {#each item.assignees as assignee}
        <div class="assignee">
          <img src={assignee.avatar_url} alt={assignee.name} />
          <span>{assignee.display_name}</span>
        </div>
      {/each}
    </SidebarGroup>

    <SidebarGroup title="Labels">
      <div class="labels">
        {#each item.labels as label}
          <span class="label" style={getLabelStyle(label)}>
            {label.name}
          </span>
        {/each}
      </div>
    </SidebarGroup>

    {#if isPR}
      <SidebarGroup title="Reviewers">
        {#each item.requested_reviewers as reviewer}
          <div class="reviewer">
            <img src={reviewer.user.avatar_url} alt={reviewer.user.name} />
            <span>{reviewer.user.display_name}</span>
            <Icon name={reviewer.state} className={`icon review ${reviewer.state}`} />
            <Icon name="sync" className="icon sync" onclick={() => reRequestReviewer(reviewer.user.id)} />
          </div>
        {/each}
      </SidebarGroup>
    {/if}
  {/if}
</Sidebar>

  
<style lang="scss">
  @import '../../../scss/components/item/sidebar.scss';
</style>
