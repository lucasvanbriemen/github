<script>
  import { onMount, onDestroy } from 'svelte';

  let events = $state([]);
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
</script>
