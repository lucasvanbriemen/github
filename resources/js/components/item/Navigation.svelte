<script>
  // Make activeTab bindable from parent (Svelte runes)
  let { activeTab = $bindable('conversation') } = $props();
</script>

<!-- Tab Navigation -->
<div class="tab-navigation">
  <button
    class="tab-button"
    class:active={activeTab === 'conversation'}
    onclick={() => activeTab = 'conversation'}
  >
    Conversation
  </button>
  <button
    class="tab-button"
    class:active={activeTab === 'files'}
    onclick={() => activeTab = 'files'}
  >
    Files changed
  </button>
</div>


<style lang="scss">
  @import '../../../scss/components/item/navigation.scss';
</style>
