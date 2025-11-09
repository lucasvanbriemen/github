<script>
  let { item } = $props();
</script>

<div class="item-header">
  <h2>{item.title}</h2>
  <div>
    created {item.created_at_human} by
    <img src={item.opened_by?.avatar_url} alt={item.opened_by?.name} />
    {item.opened_by?.display_name}
    <span class="item-state item-state-{item.state}">{item.state}</span>
  </div>
</div>

<style lang="scss">
  @import '../../../scss/components/item.scss';
</style>
