<script>
  let { params = {} } = $props();
  let name = $derived(params.name || '');
</script>

<div class="organization">
  <h1>Organization: {name}</h1>
  <a href="#/">Back to Home</a>
</div>

<style>
  .organization {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    flex-direction: column;
  }

  h1 {
    font-size: 3rem;
    font-weight: bold;
  }
</style>
