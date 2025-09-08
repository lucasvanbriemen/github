export default (() => {
  const updateOrganizations = document.querySelector(".update-organizations");

  updateOrganizations.addEventListener("click", () => {
    const url = window.location.origin + "/api/organizations";

    api.patch(url).then((data) => {
      window.location.reload();
    });
  });
})();