import api from "./api.js";

export default {
  themeUrl: "https://components.lucasvanbriemen.nl/api/colors",

  custom_colors: {
    "merged-color": {
      light: "#9d46e0",
      dark: "#8a19e0",
    },
    "draft-color": {
      light: "#e0e0e0",
      dark: "#e0e0e0",
    },
    "waiting-color": {
      light: "#ffaa00",
      dark: "#ffaa00",
    }
  },

  getTheme() {
    const darkModeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    return darkModeMediaQuery.matches ? "dark" : "light";
  },

  async applyTheme() {
    document.documentElement.setAttribute("data-theme", this.getTheme());
    const colors = await api.get(this.themeUrl);

    const mergedColors = { ...this.custom_colors, ...colors };

    Object.entries(mergedColors).forEach(([name, color]) => {
      const cssVarName = `--${name}`;
      const value = this.getTheme() === "dark" ? color.dark : color.light;
      document.documentElement.style.setProperty(cssVarName, value);
    });
  },

  init() {
    this.applyTheme();

    window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
      this.applyTheme();
    });
  }
};