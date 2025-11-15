<script>
  import Icon from './Icon.svelte';

  export let itemType = 'issue';
  export let state = 'open';

  // Map item types to icon names; defaults to itemType
  const typeToIcon = {
    issue: 'issue',
    pull_request: 'pull_request',
  };

  $: iconName = typeToIcon[itemType] || itemType;
</script>

<div class="item-icon">
  <Icon name={iconName} size={16} className={`icon ${state}`} />
</div>
  
<style>
  @import '../../scss/components/item-icon.scss';
</style>
