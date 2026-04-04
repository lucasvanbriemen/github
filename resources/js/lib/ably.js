const listeners = {};
let eventSource = null;
let subscribedChannels = [];

function connect(channels) {
  if (eventSource) eventSource.close();

  subscribedChannels = channels;
  const key = window.ABLY_SUB_KEY;
  const url = `https://realtime.ably.io/sse?key=${key}&channels=${channels.join(',')}&v=1.1`;

  eventSource = new EventSource(url);

  eventSource.onmessage = (msg) => {
    const data = JSON.parse(msg.data);
    const channel = data.channel;

    if (listeners[channel]) {
      listeners[channel].forEach((cb) => cb(data));
    }
    if (listeners['*']) {
      listeners['*'].forEach((cb) => cb(data));
    }
  };
}

function subscribe(channel, callback) {
  if (!listeners[channel]) listeners[channel] = [];
  listeners[channel].push(callback);

  // Auto-connect if this channel isn't subscribed yet
  if (channel !== '*' && !subscribedChannels.includes(channel)) {
    connect([...subscribedChannels, channel]);
  }

  return () => unsubscribe(channel, callback);
}

function unsubscribe(channel, callback) {
  if (!listeners[channel]) return;
  listeners[channel] = listeners[channel].filter((cb) => cb !== callback);
  if (listeners[channel].length === 0) delete listeners[channel];
}

function disconnect() {
  if (eventSource) {
    eventSource.close();
    eventSource = null;
  }
  subscribedChannels = [];
}

export default { connect, subscribe, unsubscribe, disconnect };
