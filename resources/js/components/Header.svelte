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
      selected: org === selectedOrg,
      // Clear selected repository when changing organization
      repositories: org.repositories.map(repo => ({ ...repo, selected: false }))
    }));
  }

  function selectRepository(organization, selectedRepo) {
    organizations = organizations.map(org => {
      if (org === organization) {
        return {
          ...org,
          repositories: org.repositories.map(repo => ({
            ...repo,
            selected: repo === selectedRepo
          }))
        };
      }
      return org;
    });

    goToRepository(organization, selectedRepo);
  }

  function goToRepository(organization, repository) {
    window.location.href = `#/${organization.name}/${repository.name}`;
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
          <a 
            href={`#/${organization.name}/${repository.name}`}
            class="repo" 
            class:selected={repository.selected} 
            on:click={() => selectRepository(organization, repository)}
          >
            {repository.name}
          </a>
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
            text-decoration: none;

            &.selected {
              background-color: var(--primary-color);
            }
          }
        }
      }
    }
  }
</style>
