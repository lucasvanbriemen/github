<script>
  import { onMount } from 'svelte';
  import Icon from './Icon.svelte';
  import { organization, repository } from './stores';

  let organizations = $state([]);
  let selectedRoute = $state('');

  onMount(async () => {
    organizations = await api.get(route('organizations'));
    selectedRoute = window.location.hash.split('/')[3] || '';
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

    <a href="#/{$organization}/{$repository}/" class:active={selectedRoute === ''} onclick={() => selectedRoute = ''}>Overview</a>
    <a href="#/{$organization}/{$repository}/issues" class:active={selectedRoute === 'issues'} onclick={() => selectedRoute = 'issues'}>Issues</a>
    <a href="#/{$organization}/{$repository}/prs" class:active={selectedRoute === 'prs'} onclick={() => selectedRoute = 'prs'}>Pull Requests</a>
    <a href="#/{$organization}/{$repository}/projects" class:active={selectedRoute === 'projects'} onclick={() => selectedRoute = 'projects'}>Projects</a>
  {/if}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>
