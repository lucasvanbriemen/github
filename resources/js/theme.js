export default {
  themeUrl: "https://components.lucasvanbriemen.nl/api/colors?theme=THEME_NAME",
  selectedTheme: "auto",

  getTheme() {
    if (this.selectedTheme === "auto") {
      const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
      return darkModeMediaQuery.matches ? "dark" : "light";
    }

    return this.selectedTheme;
  },

  async applyTheme() {
    document.documentElement.setAttribute("data-theme", this.getTheme());
    const url = this.themeUrl.replace("THEME_NAME", this.getTheme());
    const colors = await api.get(url);

    colors.forEach(color => {
      document.documentElement.style.setProperty(`--${color.name}`, color.value);
    });
  },
};