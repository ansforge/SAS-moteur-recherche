export default class LocalStoragePlugin {
  static get(key) {
    return localStorage.getItem(key);
  }

  static set(key, value) {
    localStorage.setItem(key, value);
  }

  static clear(key) {
    localStorage.removeItem(key);
  }
}
