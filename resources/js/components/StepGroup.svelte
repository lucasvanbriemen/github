<script>
  let { step } = $props();

  let isExpanded = $state(step.isExpanded ?? false);

  function toggleExpanded() {
    isExpanded = !isExpanded;
  }

  function getStatusIcon(status) {
    if (status === 'completed') {
      if (step.conclusion === 'success') return '✓';
      if (step.conclusion === 'failure') return '✕';
      return '✓';
    }
    return '●';
  }

  function getStatusClass() {
    if (step.conclusion === 'success') return 'success';
    if (step.conclusion === 'failure') return 'failure';
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
    <span class="step-status-icon" title={step.conclusion}>{getStatusIcon(step.status)}</span>
    <span class="step-name">{step.name}</span>
  </button>

  {#if isExpanded}
    {#if step.logs && step.logs.trim().length > 0}
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
