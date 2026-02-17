<script>
  import { onMount } from 'svelte';
  import Icon from './Icon.svelte';
  import { organization, repository } from './stores';

  let organizations = $state([]);
  let selectedRoute = $state('');
  let showMobileMenu = $state(false);

  onMount(async () => {
    organizations = await api.get(route('organizations'));
    selectedRoute = window.location.hash.split('/')[3] || '';
  });

  function handleNavClick(route) {
    selectedRoute = route;
    showMobileMenu = false;
  }
</script>

<!-- Mobile menu backdrop -->
{#if showMobileMenu}
  <div class="mobile-menu-backdrop" onclick={() => showMobileMenu = false}></div>
{/if}

<!-- Mobile menu modal -->
{#if showMobileMenu && $organization && $repository}
  <div class="mobile-menu-modal">
    <div class="menu-header">
      <span>Menu</span>
      <button class="menu-close" type="button" onclick={() => showMobileMenu = false}>✕</button>
    </div>
    <div class="menu-links">
      <a href="#/{$organization}/{$repository}/" class:active={selectedRoute === ''} onclick={() => handleNavClick('')}>Overview</a>
      <a href="#/{$organization}/{$repository}/issues" class:active={selectedRoute === 'issues'} onclick={() => handleNavClick('issues')}>Issues</a>
      <a href="#/{$organization}/{$repository}/prs" class:active={selectedRoute === 'prs'} onclick={() => handleNavClick('prs')}>Pull Requests</a>
      <a href="#/{$organization}/{$repository}/projects" class:active={selectedRoute === 'projects'} onclick={() => handleNavClick('projects')}>Projects</a>
    </div>
  </div>
{/if}

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

    <!-- Desktop navigation links -->
    <div class="nav-links">
      <a href="#/{$organization}/{$repository}/" class:active={selectedRoute === ''} onclick={() => selectedRoute = ''}>Overview</a>
      <a href="#/{$organization}/{$repository}/issues" class:active={selectedRoute === 'issues'} onclick={() => selectedRoute = 'issues'}>Issues</a>
      <a href="#/{$organization}/{$repository}/prs" class:active={selectedRoute === 'prs'} onclick={() => selectedRoute = 'prs'}>Pull Requests</a>
      <a href="#/{$organization}/{$repository}/projects" class:active={selectedRoute === 'projects'} onclick={() => selectedRoute = 'projects'}>Projects</a>
    </div>

    <!-- Mobile hamburger menu button -->
    <button class="hamburger-menu" type="button" onclick={() => showMobileMenu = !showMobileMenu} title="Menu">☰</button>
  {/if}
</header>

<style>
  @import "../../scss/components/header.scss";
</style>
