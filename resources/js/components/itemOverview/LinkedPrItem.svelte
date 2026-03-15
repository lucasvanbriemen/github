<script>
  import Icon from '../Icon.svelte';
  import { organization, repository } from '../stores.js';

  let { pr } = $props();

  function prUrl() {
    return `${window.location.origin}/#/${$organization}/${$repository}/prs/${pr.number}`;
  }
</script>

<a class="linked-pr-item" href={prUrl()}>
  <Icon name="pull_request" size="1.2rem" className="item-{pr.state}" />

  <span class="pr-title">#{pr.number} {pr.title}</span>

  <span class="pr-state state-{pr.state}">{pr.state}</span>

  {#if pr.head_branch}
    <span class="pr-branch">{pr.head_branch}</span>
  {/if}
</a>

<style lang="scss">
  @import '../../../scss/components/linked-pr-item.scss';
</style>
