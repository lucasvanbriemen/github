export default {
  init() {
    this.updateIssues();

    document.querySelectorAll(".header-filter select[name=state], .header-filter select[name=assignee]").forEach(select => {
      select.addEventListener("change", () => {
        this.updateIssues();
      });
    });

    const button = document.querySelector(".new-issue-button");
    button.addEventListener("click", (e) => {
      e.stopPropagation();
      modal.open("new-issue-modal");
    });

    const createButton = document.querySelector("#new-issue-modal #submit-new-issue");
    createButton.addEventListener("click", (e) => {
      e.stopPropagation();
      this.createIssue();
      modal.close("new-issue-modal");
    });
  },

  updateIssues() {
    const state = document.querySelector(".header-filter select[name=state]").value;
    const assignee = document.querySelector(".header-filter select[name=assignee]").value;

    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/issues?state=" + state + "&assignee=" + assignee;
    api.get(url).then((data) => {
      const issues = document.querySelector(".issues-wrapper");
      issues.innerHTML = data
    });
  },

  createIssue() {
    const title = document.querySelector("#issue-title").value;
    const body = document.querySelector("#issue-body").value;

    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/issues";
    api.post(url, {
      title: title,
      body: body
    })
  }
};
