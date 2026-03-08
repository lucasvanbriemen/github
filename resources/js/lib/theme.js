import api from "./api.js";

export default {
  themeUrl: "https://components.lucasvanbriemen.nl/api/colors?theme=THEME_NAME",
  selectedTheme: "auto",

  custom_colors: [
    {
      name: "merged-color",
      light: "#9d46e0",
      dark: "#8a19e0",
    },
    {
      name: "draft-color",
      light: "#e0e0e0",
      dark: "#e0e0e0",
    },
  ],

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

    this.custom_colors.forEach(color => {
      const name = `--${color.name}`;
      const value = this.getTheme() === "dark" ? color.dark : color.light;
      document.documentElement.style.setProperty(name, value);
    });
  },
};