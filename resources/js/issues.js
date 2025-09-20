export default {
  init() {
    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/issues";
    api.get(url).then((data) => {
      const issues = document.querySelector(".issues-wrapper");
      issues.innerHTML = data
    });
  }
};
