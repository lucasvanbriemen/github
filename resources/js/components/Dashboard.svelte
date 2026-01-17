<script>
  import { organization, repository } from './stores';
  import Notification from './Notification.svelte';
  import Icon from './Icon.svelte';
  import { onMount } from 'svelte';

  let organizations = $state([]);
  let notifications = $state([]);
  let nextItems = $state([]);

  onMount(async () => {
    organization.set(null);
    repository.set(null);

    organizations = await api.get(route('organizations'));
    notifications = await api.get(route('notifications'));
    nextItems = await api.get(route('items.next-to-work-on'));
  });

  function selectRepository(org, repo) {
    organization.set(org.name);
    repository.set(repo.name);
    window.location.hash = `#/${$organization}/${$repository}`;
  }

  function itemUrl(item) {
    const base = window.location.origin;
    const type = item.type === 'pull_request' ? 'prs' : 'issues';
    const orgName = item.repository?.organization?.name || '';
    const repoName = item.repository?.name || '';
    return `${base}/#/${orgName}/${repoName}/${type}/${item.number}`;
  }

</script>

<main>
  {#if nextItems.length > 0}
    <div class="next-to-work-on">
      <h2>Next to Work On</h2>
      {#each nextItems as item}
        <a href={itemUrl(item)} class="work-item">
          <Icon name={item.type} size="1.2rem" className="item-{item.state}" />
          <div class="item-info">
            <h3>{item.title}</h3>
            <p>{item.repository.name}</p>
          </div>
          <div class="score-info">
            <div class="score">Score: {item.importance_score}</div>
            <div class="breakdown">
              {#each item.score_breakdown as reason}
                <div>{reason}</div>
              {/each}
            </div>
          </div>
        </a>
      {/each}
    </div>
  {/if}

  <div class="organizations">
    {#each organizations as org}
      <div class="organization">
        <img src="{org.avatar_url}" alt="{org.name} Avatar" />
        <h2 class="title">{org.name}</h2>

        {#if org.description}
          <span class="description">{org.description}</span>
        {:else}
          <span class="no-description">No description provided.</span>
        {/if}

        <div class="repositories">
          {#each org.repositories as repo}
            <button class="repository" onclick={() => selectRepository(org, repo)}>
              <h3 class="title">{repo.name}</h3>
              {#if repo.description}
                <span class="description">{repo.description}</span>
              {:else}
                <span class="no-description">No description provided.</span>
              {/if}
            </button>
          {/each}
        </div>
      </div>
    {/each}
  </div>

  <div class="notifications">
    {#each notifications as notification}
      <Notification {notification} />
    {/each}
  </div>
</main>

<style>
  @import "../../scss/components/dashboard.scss";
</style>
