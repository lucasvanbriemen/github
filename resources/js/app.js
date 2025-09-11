export default {
  setUrl(url) {
    history.pushState({}, "", url);
  },

  setLoading(isLoading) {
    const loadingIndicator = document.querySelector(".loading-wrapper");
    if (isLoading) {
      loadingIndicator.classList.add("hidden");
    } else {
      loadingIndicator.classList.remove("hidden");
    }
  }
};