/* eslint-disable no-unused-vars */
export default class PaginationAdapter {
  /**
   * Construct an array of objects containing HTML content and the action for each button.
   * This method should be overridden by subclasses to provide a specific implementation.
   * @param {Object} _
   * @param {number} _.currentLotNumber
   * @param {number} _.totalNumberOfLots
   * @param {*} _.actions
   * @param {Function} _.emit - Vue's emit
   * @param {string} _.eventName
   * @returns {Object[]}
   */
  static buildButtons({
    currentLotNumber, totalNumberOfLots, actions, emit, eventName,
  }) {
    throw new Error('buildButtons method must be implemented by subclass');
  }
}
