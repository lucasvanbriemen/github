<script>
  import LogGroup from './LogGroup.svelte';

  let { step } = $props();

  let isExpanded = $state(step.isExpanded ?? false);

  function toggleExpanded() {
    isExpanded = !isExpanded;
  }

  function getStatusIcon() {
    // Handle all status and conclusion combinations
    if (step.status === 'in_progress') return '⟳';
    if (step.status === 'queued') return '○';

    if (step.status === 'completed') {
      if (step.conclusion === 'success') return '✓';
      if (step.conclusion === 'failure') return '✕';
      if (step.conclusion === 'skipped') return '−';
      if (step.conclusion === 'cancelled') return '✕';
      return '✓';
    }

    return '●';
  }

  function getStatusClass() {
    // Determine status class based on conclusion
    if (step.conclusion === 'success') return 'success';
    if (step.conclusion === 'failure') return 'failure';
    if (step.conclusion === 'skipped') return 'skipped';
    if (step.conclusion === 'cancelled') return 'cancelled';
    if (step.status === 'in_progress') return 'in-progress';
    return 'neutral';
  }
</script>

<div class="step-group {getStatusClass()}">
  <button class="step-header" onclick={toggleExpanded}>
    <span class="step-toggle">
      {#if isExpanded}
        ▼
      {:else}
        ▶
      {/if}
    </span>
    <span class="step-status-icon" title={step.conclusion}>{getStatusIcon()}</span>
    <span class="step-number">#{step.number}</span>
    <span class="step-name">{step.name}</span>
  </button>

  {#if isExpanded}
    {#if step.logGroups && step.logGroups.length > 0}
      <div class="log-groups-container">
        {#each step.logGroups as logGroup (logGroup.name)}
          <LogGroup {logGroup} />
        {/each}
      </div>
    {:else if step.logs && step.logs.trim().length > 0}
      <pre class="step-logs">{step.logs}</pre>
    {:else}
      <div class="step-logs-empty">
        <p>No logs output for this step</p>
      </div>
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../scss/components/step-group';
</style>
