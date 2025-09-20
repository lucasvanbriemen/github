export default {
  init() {
    this.updateIssues();
  },

  updateIssues() {
    const state = document.querySelector(".filters select[name=state]").value;
    const assignee = document.querySelector(".filters select[name=assignee]").value;

    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/issues?state=" + state + "&assignee=" + assignee;
    api.get(url).then((data) => {
      const issues = document.querySelector(".issues-wrapper");
      issues.innerHTML = data
    });
  }
};
