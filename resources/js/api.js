export default {
  defaultHeaders: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  },

  get(url, headers = {}) {
    return this.makeRequest("GET", url, null, headers);
  },

  patch(url, data, headers = {}) {
    return this.makeRequest("PATCH", url, data, headers);
  },

  makeRequest(method, url, data = null, headers = {}) {
    const options = {
      method,
      headers: {
        ...this.defaultHeaders,
        ...headers,
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    return fetch(url, options)
      .then(async (response) => {
        if (response.headers.get("content-type")?.includes("application/json")) { return response.json(); }
        return response.text();
      })
      .then((data) => {
        return data;
      });
  },
};
