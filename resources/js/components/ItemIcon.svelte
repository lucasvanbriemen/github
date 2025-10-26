<script>
  export let itemType = "issue";
  export let state = "open";
</script>

<div class="item-icon">
  {#if itemType === 'issue'}
    <svg color="open.fg" viewBox="0 0 16 16" width="16" height="16" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom" class="icon {state}">
      <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
      <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0M1.5 8a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0"/>
    </svg>
  {:else if itemType === 'pull_request'}
  {/if}
</div>
  
<style>
  .icon {
    height: 1.5rem;
    width: 1.5rem;
    margin-top: 0.25rem;
    fill: var(--success-color);

    &.closed {
      fill: var(--error-color);
    }
  }
</style>
