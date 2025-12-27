<script>
  import { organization, repository } from './stores';
  import { onMount } from 'svelte';

  let organizations = [];
  let notifications = [];

  onMount(async () => {
    organization.set(null);
    repository.set(null);

    organizations = await api.get(route('organizations'));
    notifications = await api.get(route('notifications'));

    console.log('Notifications:', notifications);
  });

  function selectRepository(org, repo) {
    organization.set(org.name);
    repository.set(repo.name);
    window.location.hash = `#/${$organization}/${$repository}`;
  }

</script>

<main>
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
    <h2>Notifications</h2>
    {#if notifications.length === 0}
      <p>No new notifications.</p>
    {:else}
      <ul>
        {#each notifications as notification}
          <li class="notification">
            {#if notification.type === 'comment_mention'}
              <strong>Mentioned in comment:</strong> "{notification.comment.body}"
            {:else}
              <strong>{notification.type}:</strong> {notification.message}
            {/if}
          </li>
        {/each}
      </ul>
    {/if}
  </div>
</main>

<style>
  @import "../../scss/components/dashboard.scss";
</style>
