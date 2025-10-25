<script>
  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
</script>

<div class="organization">
  <h1>Organization: {organization}</h1>
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
