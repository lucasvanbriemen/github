export default {
  init() {
    document.querySelectorAll(".modal-container").forEach((container) => {
      // If its notwithin the modal, close it
      document.addEventListener("click", (event) => {
        if (container.classList.contains("open") && !container.querySelector(".modal-wrapper").contains(event.target) && !event.target.classList.contains("modal-wrapper")) {
          this.close(container.id);
        }
      });
    });

    document.querySelectorAll(".modal-wrapper .close-button").forEach((button) => {
      button.addEventListener("click", () => {
        this.close(button.closest(".modal-container").id);
      });
    });
  },

  open(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("open");
    }
  },

  close(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove("open");
    }
  },
};
