<script>
  import { organization, repository } from "../stores";
  let { activeTab = $bindable('conversation'),  type, number } = $props();

  const TABS = [
    { value: "conversation", label: "Conversation" },
    { value: "files", label: "Files changed" }
  ];
</script>

<!-- Tab Navigation -->
<div class="tab-navigation">
  {#each TABS as tab}
    <a
      class="tab-button"
      class:active={activeTab === tab.value}
      onclick={() => activeTab = tab.value}
      href={`#/${$organization}/${$repository}/${type}/${number}/${tab.value}`}
    >
      {tab.label}
    </a>
  {/each}
</div>


<style lang="scss">
  @import '../../../scss/components/item/navigation.scss';
</style>
