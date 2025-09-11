export default {
  init() {
    const updateOrganizations = document.querySelector(".update-organizations");
    updateOrganizations.addEventListener("click", () => {
      const url = window.location.origin + "/api/organizations";
      this.sendRequest(url);
    });

    const updateRepositories = document.querySelector(".update-repositories");
    updateRepositories.addEventListener("click", () => {
      const url = window.location.origin + "/api/organizations/repositories";
      this.sendRequest(url);
    });
  },

  sendRequest(url) {
    return api.patch(url).then((data) => {
      window.location.reload();
    });
  }
};
