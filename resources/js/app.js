export default {
  setUrl(url) {
    history.pushState({}, "", url);
  },

  setLoading(isLoading) {
    const loadingIndicator = document.querySelector(".loading-wrapper");
    if (isLoading) {
      loadingIndicator.classList.remove("hidden");
      console.log('hide');
    } else {
      loadingIndicator.classList.add("hidden");
      console.log('show');
    }
  }
};