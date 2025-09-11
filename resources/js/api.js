export default (() => {
  const defualtHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  };

  const get = (url, headers = {}) => {
    return fetch(url, {
      method: "GET",
      headers: {
        ...defualtHeaders,
        ...headers,
      },
    })
      .then(async (response) => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
          return response.json();
        } else {
          return response.text();
        }
      })
      .then((data) => {
        return data;
      });
  };

  const patch = (url, data, headers = {}) => {
    return fetch(url, {
      method: "PATCH",
      headers: {
        ...defualtHeaders,
        ...headers,
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        return data;
      });
  };

  return { get, patch };
})();