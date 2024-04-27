export default class RoleClass {
  constructor(role = '') {
    this.role = role;
  }

  getRole() {
    return this.role;
  }

  setRole(value) {
    this.role = value;
  }
}
