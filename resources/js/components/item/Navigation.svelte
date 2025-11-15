<script>
  // Make activeTab bindable from parent (Svelte runes)
  let { activeTab = $bindable('conversation') } = $props();

  const TABS = [
    { value: "conversation", label: "Conversation" },
    { value: "files", label: "Files changed" }
  ];
</script>

<!-- Tab Navigation -->
<div class="tab-navigation">
  {#each TABS as tab}
    <button
      class="tab-button"
      class:active={activeTab === tab.value}
      onclick={() => activeTab = tab.value}
    >
      {tab.label}
    </button>
  {/each}
</div>


<style lang="scss">
  @import '../../../scss/components/item/navigation.scss';
</style>
