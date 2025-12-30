<script>
  import { onMount } from 'svelte';
  import Icon from './Icon.svelte';
  import { organization, repository } from './stores';

  let organizations = $state([]);

  onMount(async () => {
    organizations = await api.get(route('organizations'));
  });
</script>

<header>
  <a class="logo" href="#/">
    <Icon name="logo" />
    <span class="title">Git</span>
  </a>

  <div class="separator"></div>

  {#each organizations as org}
    {#if org.name === $organization}
      <div class="organization">
        <div>
          <img src="{org.avatar_url}" alt="{org.name} Avatar" />
          <span class="org">{$organization}</span>
        </div>

        <div class="separator"></div>
        <span class="repo">{$repository}</span>
      </div>
    {/if}
  {/each}

  {#if $organization && $repository}
    <div class="separator"></div>

    <a href="#/{$organization}/{$repository}">home</a>
    <a href="#/{$organization}/{$repository}/issues">issues</a>
    <a href="#/{$organization}/{$repository}/prs">pull requests</a>
    <a href="#/{$organization}/{$repository}/projects">projects</a>

  {/if}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>
