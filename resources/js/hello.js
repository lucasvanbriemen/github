import { mount } from 'svelte';
import HelloWorld from './components/HelloWorld.svelte';

const app = mount(HelloWorld, {
  target: document.getElementById('app'),
});

export default app;
