export default class FaqClass {
  constructor({ role, themes } = {}) {
    this.role = role;
    this.themes = themes;
  }

  getRole() {
    return this.role;
  }

  setRole(value) {
    this.role = value;
  }

  getThemes() {
    return this.themes;
  }

  setThemes(value) {
    this.role = value;
  }
}
