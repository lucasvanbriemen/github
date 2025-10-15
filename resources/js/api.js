export default {
  defaultHeaders: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  },

  get(url, headers = {}, background = false) {
    return this.makeRequest("GET", url, null, headers, background);
  },

  patch(url, data, headers = {}) {
    return this.makeRequest("PATCH", url, data, headers);
  },

  post(url, data, headers = {}) {
    return this.makeRequest("POST", url, data, headers);
  },

  put(url, data, headers = {}) {
    return this.makeRequest("PUT", url, data, headers);
  },

  makeRequest(method, url, data = null, headers = {}, background = false) {
    if (!background) {
      // app.setLoading(true);
    }
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
        // app.setLoading(false);
        if (response.headers.get("content-type")?.includes("application/json")) { return response.json(); }
          return response.text();
        })
        .then((data) => {
          return data;
        });
  },
};
