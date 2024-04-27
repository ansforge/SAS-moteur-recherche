export default class AddressClass {
  constructor({ lat, lng, addressLabel } = {}) {
    this.lat = lat;
    this.lng = lng;
    this.addressLabel = addressLabel;
  }

  getAddressCoordinates() {
    return {
      lat: this.lat,
      lng: this.lng,
      addressLabel: this.addressLabel,
    };
  }
}
