<script>
  import { onMount, untrack } from 'svelte';
  import Sidebar from '../sidebar/Sidebar.svelte';
  import SidebarGroup from '../sidebar/group.svelte';
  import Icon from '../Icon.svelte';

  let { item, isPR, isLoading, activeTab, params = {}, files, selectedFileIndex = $bindable(0), selectedFile = $bindable(null) } = $props();
  let activeItem = $state('Issues');
  
  let fileList = $state([]);

  // Generate label style with proper color formatting
  function getLabelStyle(label) {
    return `background-color: #${label.color}4D; color: #${label.color}; border: 1px solid #${label.color};`;
  }

  onMount(async () => {
    if (isPR) {
      activeItem = 'Pull Requests';
    }
  });

  $effect(() => {
    void files;

    untrack(() => {
      fileList = files.map((file, index) => {
        
        let fullPath = file.filename;
        // Remove everything before the last slash
        let filename = fullPath.replace(/^.*\//, '');
        
        return {
          filename: filename,
          index: index,
        };
      });
    });
  });

</script>

<Sidebar {params} {activeItem}>
  {#if activeTab === 'conversation'}
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
              <Icon name={reviewer.state} className={`icon ${reviewer.state}`} />
            </div>
          {/each}
        </SidebarGroup>
      {/if}
    {/if}
  {:else}
    <SidebarGroup title="Files">
      <div class="files">
        {#each fileList as file}
          <button class="file" type="button" class:selected={selectedFileIndex === file.index} onclick={() => selectedFileIndex = file.index}>
            <span class="file-name">{file.filename}</span>
          </button>
        {/each}
      </div>
    </SidebarGroup>
  {/if}
</Sidebar>

  
<style lang="scss">
  @import '../../../scss/components/item/sidebar.scss';
</style>
