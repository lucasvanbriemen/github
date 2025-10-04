export default {
  IS_EDITING: false,

  init() {
    const resolveButtons = document.querySelectorAll(".resolve-comment");
    const unresolveButtons = document.querySelectorAll(".unresolve-comment");

    unresolveButtons.forEach(button => {
      button.addEventListener("click", () => this.updateComment(button.getAttribute("data-comment"), button.getAttribute("data-url")));
    });

    resolveButtons.forEach(button => {
      button.addEventListener("click", () => this.updateComment(button.getAttribute("data-comment"), button.getAttribute("data-url")));
    });

    const allCommentsHeaders = document.querySelectorAll(".issue-comment .comment-header");
    allCommentsHeaders.forEach(commentHeader => {
      commentHeader.addEventListener("click", () => this.openResolvedComments(commentHeader.closest(".issue-comment").getAttribute("data-comment")));
    });

    this.getLinkedIssues(window.pullRequestNumber);

    // Initialize visibility based on data-editing attributes
    document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "block");
    document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "none");

    [".edit-pr", ".cancel-edit", ".save-edit"].forEach((selector, i) => {
      const el = document.querySelector(selector);
      // i === 2 is the save button (we want to save)
      el.addEventListener("click", () => this.toggleEditMode(i === 2));
    });
  },

  updateComment(id, url) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    if (!comment) {
      return;
    }

    api.patch(url).then(data => {
      comment.classList.toggle("resolved", data.resolved);
      comment.querySelector(".button-primary").innerHTML = data.resolved ? "Mark as unresolved" : "Mark as resolved";

      const newURL = data.resolved ? url.replace("/resolve", "/unresolve") : url.replace("/unresolve", "/resolve");
      comment.querySelector(".button-primary").setAttribute("data-url", newURL);
    });
  },

  openResolvedComments(id) {
    const comment = document.querySelector(`.issue-comment[data-comment="${id}"]`);
    comment.classList.toggle("resolved");
  },

  getLinkedIssues(id) {
    const url = window.location.origin + "/api/organization/" + window.organizationName + "/" + window.repositoryName + "/pull_requests/" + id + "/linked_issues";
    api.get(url, {}, true).then((data) => {
      const issues = document.querySelector(".linked-issues");
      issues.innerHTML = data
    });
  },

  toggleEditMode(triggerSave = false) {
    if (triggerSave) {
      this.updatePullRequest();
    }

    this.IS_EDITING = !this.IS_EDITING;

    const displayTitle = document.getElementById("pr-title");
    const displayBody = document.getElementById("pr-body");

    const editTitle = document.getElementById("edit-pr-title");
    const editBody = document.getElementById("edit-pr-body");

    editTitle.value = displayTitle.getAttribute("data-raw");
    editBody.innerHTML = displayBody.getAttribute("data-raw");

    if (this.IS_EDITING) {
      document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "none");
      document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "block");

      // Auto-resize textarea
      this.autoResizeTextarea(editBody);
      editBody.addEventListener("input", () => this.autoResizeTextarea(editBody));
    } else {
      document.querySelectorAll('*[data-editing="0"]').forEach(el => el.style.display = "block");
      document.querySelectorAll('*[data-editing="1"]').forEach(el => el.style.display = "none");
    }
  },

  autoResizeTextarea(textarea) {
    textarea.style.height = "25rem";
    const scrollHeight = textarea.scrollHeight;
    const minHeight = 25 * 16; // 25rem in pixels (assuming 1rem = 16px)
    textarea.style.height = Math.max(scrollHeight, minHeight) + "px";
  },

  updatePullRequest() {
    const title = document.getElementById("edit-pr-title").value;
    const body = document.getElementById("edit-pr-body").value;

    fetch(`/api/organization/${window.organizationName}/${window.repositoryName}/pull_requests/${window.pullRequestNumber}/edit`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        title,
        body
      })
    })
    .then(result => {
      // Update data-raw attributes for next edit
      displayTitle.setAttribute("data-raw", title);
      displayBody.setAttribute("data-raw", body);
    });
  }
};
