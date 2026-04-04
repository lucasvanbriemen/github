<script>
  import { onMount, onDestroy } from 'svelte';

  let events = $state([]);
  let connected = $state(false);
  let ch1Loading = $state(false);
  let ch2Loading = $state(false);
  let eventSource = null;

  onMount(() => {
    const key = window.ABLY_SUB_KEY;
    const channels = 'channel-1,channel-2';
    const url = `https://realtime.ably.io/sse?key=${key}&channels=${channels}&v=1.1`;

    eventSource = new EventSource(url);

    eventSource.onopen = () => {
      connected = true;
    };

    eventSource.onmessage = (msg) => {
      const data = JSON.parse(msg.data);
      events = [{
        channel: data.channel,
        name: data.name,
        data: data.data,
        timestamp: new Date().toLocaleTimeString(),
      }, ...events];
    };

    eventSource.onerror = () => {
      connected = false;
    };
  });

  onDestroy(() => {
    eventSource?.close();
  });

  async function publish(channel) {
    if (channel === 1) ch1Loading = true;
    else ch2Loading = true;

    try {
      await api.post(route(`ably.test.channel${channel}`));
    } catch (e) {
      // toast handles the error
    } finally {
      if (channel === 1) ch1Loading = false;
      else ch2Loading = false;
    }
  }
</script>

<main>
  <div class="ably-test">
    <h1>Ably Integration Test</h1>
    <div class="status" class:connected>
      {connected ? 'Subscribed' : 'Connecting...'}
    </div>

    <div class="actions">
      <button onclick={() => publish(1)} disabled={ch1Loading}>
        {ch1Loading ? 'Sending...' : 'Publish to Channel 1'}
      </button>
      <button onclick={() => publish(2)} disabled={ch2Loading}>
        {ch2Loading ? 'Sending...' : 'Publish to Channel 2'}
      </button>
    </div>

    <div class="event-log">
      <h2>Events ({events.length})</h2>
      {#if events.length === 0}
        <p class="empty">No events yet. Click a button to publish one.</p>
      {/if}
      {#each events as event}
        <div class="event" class:ch1={event.channel === 'channel-1'} class:ch2={event.channel === 'channel-2'}>
          <span class="channel">{event.channel}</span>
          <span class="data">{event.data}</span>
          <span class="time">{event.timestamp}</span>
        </div>
      {/each}
    </div>
  </div>
</main>

<style>
  @import "../../scss/components/ably-test.scss";
</style>
