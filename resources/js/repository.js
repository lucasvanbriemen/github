export default {
  init() {
    const path = window.location.pathname;
    const IfFileParam = window.location.search.includes("isFile=1") ? "?isFile=1" : "";
    window.api.get(window.location.origin + `/api${path}?${IfFileParam}`).then((data) => {
      document.querySelector(".file-list").innerHTML = data;
    });

    const fileList = document.querySelector(".file-list");
    fileList.addEventListener("click", this.updateFileList);
  },

  updateFileList(event) {
    if (event.target.classList.contains("file")) {
      event.preventDefault();
      app.setUrl(event.target.dataset.url);
      window.api.get(event.target.dataset.apiUrl).then((data) => {
        document.querySelector(".file-list").innerHTML = data;
      });
    }
  }
};