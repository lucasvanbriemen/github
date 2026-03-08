<script>
  let { logGroup } = $props();

  let isExpanded = $state(logGroup.isExpanded ?? false);

  function toggleExpanded() {
    isExpanded = !isExpanded;
  }
</script>

<div class="log-group">
  <button class="log-group-header" onclick={toggleExpanded}>
    <span class="log-group-toggle">
      {#if isExpanded}
        ▼
      {:else}
        ▶
      {/if}
    </span>
    <span class="log-group-name">{logGroup.name}</span>
  </button>

  {#if isExpanded}
    {#if logGroup.logs && logGroup.logs.trim().length > 0}
      <pre class="log-group-logs">{logGroup.logs}</pre>
    {:else}
      <div class="log-group-logs-empty">
        <p>No logs output for this group</p>
      </div>
    {/if}
  {/if}
</div>

<style lang="scss">
  @import '../../scss/components/log-group';
</style>
