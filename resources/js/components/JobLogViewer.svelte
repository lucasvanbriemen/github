<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores';
  import StepGroup from './StepGroup.svelte';
  import ListItemSkeleton from './ListItemSkeleton.svelte';

  let { job } = $props();

  let steps = $state([]);
  let isLoading = $state(true);

  onMount(async () => {
    isLoading = true;
    steps = await api.get(route('organizations.repositories.workflow-job.logs', { $organization, $repository, jobId: job.id }));
    // isLoading = false;
  });
</script>

<div class="job-log-viewer">
  {#if isLoading}
    {#each Array(3) as _}
      <ListItemSkeleton />
    {/each}
  {:else}
    <div class="steps-container">
      {#each steps as step, idx (idx)}
        <StepGroup {step} />
      {/each}
    </div>
  {/if}
</div>

<style lang="scss">
  @import '../../scss/components/job-log-viewer';
</style>
