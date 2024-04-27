export default class HomeContentClass {
  constructor({
 isConnected, description, subDescription, objectives, bgImage, bgImageMobile,
} = {}) {
    this.isConnected = isConnected || false;
    this.objectives = objectives || [];
    this.description = description;
    this.subDescription = subDescription;
    this.bgImage = bgImage;
    this.bgImageMobile = bgImageMobile;
  }

  getIsConnected() {
    return this.isConnected;
  }

  getDisconnectedData() {
    return {
      objectives: this.objectives,
      description: this.description,
      bgImage: this.bgImage,
    };
  }

  getConnectedData() {
    return {
      description: this.description,
      subDescription: this.subDescription,
      bgImage: this.bgImage,
      bgImageMobile: this.bgImageMobile,
    };
  }
}
