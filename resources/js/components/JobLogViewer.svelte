<script>
  import { onMount } from 'svelte';
  import { organization, repository } from './stores';
  import StepGroup from './StepGroup.svelte';

  let { job } = $props();

  let steps = $state([]);
  let isLoading = $state(true);

  onMount(async () => {
    isLoading = true;

    // Fetch processed logs from backend
    const response = await api.get(route('organizations.repositories.workflow-job.logs', { $organization, $repository, jobId: job.id }));
    steps = response;

    isLoading = false;
  });
</script>

<div class="job-log-viewer">
  {#if isLoading}
    <div class="loading">
      <p>Loading logs...</p>
    </div>
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
