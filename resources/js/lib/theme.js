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
    },

    "background-color": {
      light: "#FAF8FF",
      dark: "#11131B",
    },
    "background-color-one": {
      light: "#ECEDF9",
      dark: "#191B24",
    },
    "background-color-two": {
      light: "#E7E7F3",
      dark: "#272A32",
    },
    "modal-background-color": {
      light: "#F2F3FF",
      dark: "#1D1F28",
    },
    "success-color": {
      light: "#61c980",
      dark: "#4a824b",
    },
    "error-color": {
      light: "#4a824b",
      dark: "#a12a34",
    },
    "starred-color": {
      light: "#A442C7",
      dark: "#EEB0FF",
    },

    "text-color": {
      light: "#191B24",
      dark: "#E1E2ED",
    },
    "text-color-secondary": {
      light: "#424655",
      dark: "#C2C6D7",
    },
    "border-color": {
      light: "#727786",
      dark: "#424655",
    },

    "primary-color": {
      light: "#004FC3",
      dark: "#1266F1",
    },
    "primary-color-dark": {
      light: "#1266F1",
      dark: "#0056D2",
    },
    "primary-color-light": {
      light: "#1b5fc2",
      dark: "#1b5fc2",
    },

    "secondary-color": {
      light: "#4ab035",
      dark: "#2b8f17",
    },
    "secondary-color-dark": {
      light: "#459e33",
      dark: "#18520c",
    },
    "secondary-color-light": {
      light: "#306e23",
      dark: "#36b31d",
    },

    "font-family": {
      light: "Roboto, sans-serif",
      dark: "Roboto, sans-serif",
    },
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