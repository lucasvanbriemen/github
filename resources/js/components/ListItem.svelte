<script>
  import { onMount } from "svelte";
  let { item } = $props();

  onMount(() => {
    console.log(item);
  });
</script>

<article class="list-item">

</article>

<style>
  .list-item {
    padding: 0.5rem;
    background-color: var(--background-color-one);
    border-radius: 1rem;
  }
</style>
