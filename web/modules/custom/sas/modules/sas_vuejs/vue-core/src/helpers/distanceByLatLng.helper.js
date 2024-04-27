// Earth radius in meters
const R = 6371e3;
const { PI } = Math;

export default {
  /**
   * transform degrees into radian value
   * @param {int} degrees
   * @returns {int}
   */
  toRadians(degrees) {
    return degrees * (PI / 180);
  },

  /**
   * calculates distance between 2 points with given
   * latitude and longitude
   * returned value is in meters
   * @param {Object} originPoint
   * @param {Object} currentPoint
   * @returns {int}
   */
  calculateDistance(originPoint, currentPoint) {
    // transform to spherical coordinates
    const phi1 = this.toRadians(originPoint.latitude);
    const phi2 = this.toRadians(currentPoint.latitude);

    const deltaPhi = this.toRadians(currentPoint.latitude - originPoint.latitude);
    const deltaLambda = this.toRadians(currentPoint.longitude - originPoint.longitude);

    // the haversine
    const haversine = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2)
      + Math.cos(phi1)
      * Math.cos(phi2)
      * Math.sin(deltaLambda / 2)
      * Math.sin(deltaLambda / 2);

    // length of the imaginary number
    const complexeModule = 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));

    // distance in m
    return R * complexeModule;
  },

};
