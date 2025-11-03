<script>
  let { params = {} } = $props();
  let organization = $derived(params.organization || '');
</script>

<div class="organization">
  <h1>Organization: {organization}</h1>
  <a href="#/">Back to Home</a>
</div>

<style lang="scss">
  @import '../../scss/components/organization.scss';
</style>
