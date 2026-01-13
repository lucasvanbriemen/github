<script>
  import Icon from '../Icon.svelte';

  let { item } = $props();
</script>

<div class="item-header">
  <Icon name={item.type} size="1.5rem" className="item-{item.state}" />
  <div>
    <h2>{item.title}</h2>

    <div class="details">
      created {item.created_at_human} by
      <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
      {item.opened_by?.display_name}
    </div>
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item/header.scss';
</style>
