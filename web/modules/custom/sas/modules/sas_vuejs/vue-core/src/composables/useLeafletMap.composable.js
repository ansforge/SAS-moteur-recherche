/**
 * This file exposes functions that interact with a leaflet map.
 * This composable should be used only once per leaflet map
 * as it tracks the different drawn layers in its internal closure level
 * @param {import('leaflet')} L
 */
// eslint-disable-next-line import/prefer-default-export
export const useLeafletMap = (L) => {
  const layers = new Map();

  /**
   * @param {import('leaflet').Map} map
   * @returns {number} The radius expressed in meters
   */
  const computeRadiusOfCircleThatFitTheMap = (map) => {
    const size = map.getSize();
    const center = map.getCenter();
    const centerPoint = map.latLngToContainerPoint(center);
    const circlePoint = [centerPoint.x - Math.min(size.x, size.y) / 2, centerPoint.y];

    const centerLatLng = map.containerPointToLatLng(centerPoint);
    const circleLatLng = map.containerPointToLatLng(circlePoint);

    return centerLatLng.distanceTo(circleLatLng);
  };

  /**
   * Creates and add a blue circle marker on the map
   * @param {import('leaflet').Map} map
   * @param {object} options
   * @param {number} options.latitude
   * @param {number} options.longitude
   * @param {string} options.streetLabel
   * @param {string} options.cityLabel
   */
  const drawBluePoint = (map, {
 latitude, longitude, streetLabel, cityLabel,
}) => {
    if (!map || !latitude || !longitude) return;

    deleteBluePoint(map);

    const bluePoint = L.divIcon({
      class: 'leaflet-marker-icon',
      iconSize: null,
    });

    const streetDiv = `<div class="address-label">${streetLabel}</div>`;
    const cityDiv = `<div class="adrress-postcode-city">${cityLabel}</div>`;

    const adressLabelContent = `
    <div class="card-popup">
      <div class="popup--wrapper">
        <div class="popup--content">
          Adresse de recherche
        </div>
        ${streetLabel ? streetDiv : ''}
        ${cityLabel ? cityDiv : ''}
      </div>
    </div>
    `;

    // eslint-disable-next-line new-cap
    const popup = new L.popup({
      maxWidth: 250,
      closeButton: false,
    }).setContent(adressLabelContent);

    // eslint-disable-next-line new-cap
    const bluePointMarker = new L.marker(
      [latitude, longitude],
      { icon: bluePoint },
    ).bindPopup(popup).addTo(map);

    layers.set('blue-point', bluePointMarker);

    // eslint-disable-next-line no-underscore-dangle
    const customBluePoint = bluePointMarker._icon;
    customBluePoint.className = `sas-address-position-marker ${customBluePoint.className}`;
  };

  /**
   * @param {import('leaflet').Map} map
   */
  const deleteBluePoint = (map) => {
    if (map.hasLayer(layers.get('blue-point'))) {
      map.removeLayer(layers.get('blue-point'));
    }
  };

  /**
   * Draws a circle that fit inside the map. It removes any previously drawn circle
   * @param {import('leaflet').Map} map
   * @param {number} marginPercentage - how much do you want the computed circle to touch the borders (`100` does nothing, .i.e it lets the circle touch the closest border)
   * @returns {{latitude: number, longitude: number, radius: number}} the coordinates computed to draw the circle (the radius is expressed in kilometer)
   */
  const fitCircleInMap = (map, marginPercentage = 90) => {
    const {
      lat: latitude,
      lng: longitude,
    } = map.getCenter();

    // We artificially reduce the radius size to avoid the circle to touch the border of the map
    // eslint-disable-next-line no-mixed-operators
    const radius = computeRadiusOfCircleThatFitTheMap(map) * (marginPercentage / 100) / 1000;

    drawCircleInMap(map, { latitude, longitude, radius });
    return { latitude, longitude, radius };
  };

  /**
   * It removes any previously drawn circle
   * @param {import('leaflet').Map} map
   * @param {object} options
   * @param {number} options.latitude
   * @param {number} options.longitude
   * @param {number} options.radius - expressed in kilometer
   * @param {...*} rest - the options that can be sent to the leaflet function that draw the circle
   */
  const drawCircleInMap = (map, {
  latitude, longitude, radius, ...rest
  }) => {
    if (map.hasLayer(layers.get('circle'))) {
      map.removeLayer(layers.get('circle'));
    }

    layers.set('circle', L.circle([latitude, longitude], {
      radius: radius * 1000,
      fillOpacity: 0.1,
      ...rest,
    }).addTo(map));
  };

  /**
   *
   * @param {string} name
   * @returns {import('leaflet').Layer | null}
   */
  const getLayer = (name) => (layers.get(name) ?? null);

  return {
    deleteBluePoint,
    drawBluePoint,
    drawCircleInMap,
    fitCircleInMap,
    getLayer,
  };
};
