<script>
  let { name = 'input', label = "Label", value = $bindable(), placeholder = ' ', onChange } = $props();

  function handleInput(event) {
    value = event.target.value;
    onChange?.({ value });
  }

</script>

<div class="input-wrapper">
  <label for={name}>{label}</label>
  <input {name} {placeholder} bind:value={value} oninput={handleInput}>
</div>

<style lang="scss">
  @import "../../scss/components/input";
</style>
