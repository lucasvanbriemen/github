<script>
  import Icon from "./Icon.svelte";
  import { organization, repository } from "./stores.js";

  let { item } = $props();

  let isPR = $derived(item.type === 'pull_request' && item.review_status);

  function itemUrl(number) {
    const base = window.location.origin;

    let type = 'issues';
    if (item.type === 'pull_request') {
      type = 'prs';
    } else if (item.type === 'project') {
      type = 'projects';
    }

    // Use item data if stores are not set (e.g., on homepage)
    const org = $organization || item.repository?.full_name?.split('/')[0];
    const repo = $repository || item.repository?.full_name?.split('/')[1];

    return `${base}/#/${org}/${repo}/${type}/${number}`;
  }

  function subTitle() {
    if (item.type === 'pull_request' || item.type === 'issue') {
      return `opened ${item.created_at_human} by ${item.opened_by?.display_name}`;
    }

    if (item.type === 'project') {
      return `#${item.number} updated ${item.created_at_human}`;
    }
  }

  function isCurrentUserAssigned() {
    const currentUserId = window.USER_ID;
    return item.assignees?.some(assignee => assignee.id == currentUserId);
  }
</script>

<a class="list-item" class:assigned={isCurrentUserAssigned()} href="{itemUrl(item.number)}">
  <Icon name={item.type} size="1.5rem" className="item-{item.state}" />

  <div class="content">
    <h3>{item.title}</h3>
    <div class="meta">
      {subTitle()}

      {#if isPR}
        <span class="devider"></span>
        <span class="review-status review-status--{item.review_status}">
          <Icon name={item.review_status === 'no_reviewers' ? 'pending' : (item.review_status === 'draft' ? 'pull_request' : item.review_status)} size="0.875rem" />
          {#if item.review_status === 'approved'}
            Approved
          {:else if item.review_status === 'changes_requested'}
            Changes requested
          {:else if item.review_status === 'pending'}
            Pending review
          {:else if item.review_status === 'draft'}
            Draft
          {:else if item.review_status === 'no_reviewers'}
            No reviewers
          {/if}
        </span>

        {#if item.ci_status === 'failure'}
          <span class="devider"></span>
          <span class="review-status review-status--ci-failure">
            <Icon name="cross" size="0.875rem" />
            CI failing
          </span>
        {/if}

        {#if item.has_conflicts}
          <span class="devider"></span>
          <span class="review-status review-status--conflicts">
            <Icon name="cross" size="0.875rem" />
            Merge conflicts
          </span>
        {/if}
      {/if}

      {#if item.labels?.length > 0}
        <span class="devider"></span>

        <div class="labels">
          {#each item.labels as label}
            <span class="label" style="background-color: #{label.color}4D; color: #{label.color}; border: 1px solid #{label.color};">{label.name}</span>
          {/each}
        </div>
      {/if}
    </div>
  </div>

  <div class="assignees">
    {#if item.assignees?.length > 0}
      {#each item.assignees as assignee}
        <img src="{assignee.avatar_url}" alt="">
      {/each}
    {/if}
  </div>
</a>

<style lang="scss">
  @import '../../scss/components/list-item.scss';
</style>
