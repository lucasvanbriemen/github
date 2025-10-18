<script>
  import { onMount } from 'svelte';

  let organizations = [];

  onMount(async () => {
    const res = await fetch('/api/organizations');
    organizations = await res.json();
  });

  function selectOrganization(selectedOrg) {
    organizations = organizations.map(org => ({
      ...org,
      selected: org === selectedOrg
    }));
  }

  function goToRepository(repositoryName) {
    window.location.href = `#/${organizations[0].name}/${repositoryName}`;
  }
</script>

<header>
  {#each organizations as organization}
    <div class="organization" class:selected={organization.selected}>
      <button on:click={() => selectOrganization(organization)}>
        <img src="{organization.avatar_url}" alt="{organization.name} Avatar" width="50" height="50" />
        <span class="name">{organization.name}</span>
      </button>

      <div class="repos">
        {#each organization.repositories as repository}
          <!-- Go to repository page -->
          <span class="repo" on:click={goToRepository(repository.name)}>{repository.name}</span>
        {/each}
      </div>
    </div>
  {/each}
</header>


<style>
  header {
    background-color: var(--background-color-one);
    padding: 0.5rem;

    display: flex;
    align-items: center;

    .organization {
      display: flex;
      align-items: center;
      position: relative;
      padding: 0.5rem;

      div, button {
        background-color: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-weight: bold;
        font-size: 1.2rem;
      }

      img {
        border-radius: 50%;
        margin-right: 0.5rem;
        height: 2rem;
        width: 2rem;
      }

      .repos {
        display: flex;
        gap: 1rem;
        overflow: hidden;
        max-width: 0;
        opacity: 0;
        transition: max-width 0.3s ease, opacity 0.3s ease;
      }

      &.selected {
        background-color: var(--background-color-two);
        border-radius: 0.5rem;

        .repos {
          max-width: 500px;
          opacity: 1;

          .repo {
            background-color: var(--background-color-one);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
          }
        }
      }
    }
  }
</style>
