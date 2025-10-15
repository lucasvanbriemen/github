import { mount } from 'svelte';
import Organization from './components/Organization.svelte';

const organizationName = document.getElementById('app').dataset.organization;

const app = mount(Organization, {
  target: document.getElementById('app'),
  props: {
    name: organizationName
  }
});

export default app;
