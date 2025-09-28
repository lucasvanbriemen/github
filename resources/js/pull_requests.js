export default {
  init() {
    this.updateIssues();

    document.querySelectorAll(".filters select[name=state], .filters select[name=assignee]").forEach(select => {
      select.addEventListener("change", () => {
        this.updateIssues();
      });
    });
  },

  updateIssues() {
    const state = document.querySelector(".filters select[name=state]").value;
    const assignee = document.querySelector(".filters select[name=assignee]").value;

    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/pull_requests?state=" + state + "&assignee=" + assignee;
    api.get(url).then((data) => {
      const issues = document.querySelector(".pullrequest-wrapper");
      issues.innerHTML = data
    });
  }
};
