<script>
  let { input = $bindable(''), onChange = () => {}, title = '', description = '' } = $props();
</script>

<div class="switch-container">
  <div class="switch-label">
    <h3>{title}</h3>
    <p>{description}</p>
  </div>
  <label class="switch">
    <input type="checkbox" bind:checked={input} />
    <span class="slider"></span>
  </label>
</div>

<style lang="scss">
  @import '../../scss/components/switch.scss';
</style>