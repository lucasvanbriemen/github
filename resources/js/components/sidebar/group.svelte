<script>
  import Icon from "../Icon.svelte";

  let { title, children } = $props();
  let isOpen = $state(false);

  let maxHeight = $state(0);
  let body;

  function toggleOpen() {
    isOpen = !isOpen;

    if (isOpen) {
      maxHeight = body.scrollHeight;
      body.style.maxHeight = maxHeight + 'px';
    } else {
      body.style.maxHeight = 0;
    }
  }
</script>

<div class="group" class:open={isOpen}>
  <button class="group-title" onclick={toggleOpen}>
    {title}
    <Icon name="gear" className="icon gear" onclick={toggleOpen} />
  </button>
  
  <div class="body" bind:this={body}>{@render children?.() }</div>
</div>

<style lang="scss">
  @import "../../../scss/components/sidebar/group";
</style>