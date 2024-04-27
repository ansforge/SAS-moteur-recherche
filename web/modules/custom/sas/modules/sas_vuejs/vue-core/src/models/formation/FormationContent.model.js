export default class FormationContentClass {
  constructor(nodeContent = {}) {
    const nodeType = this.#setContentType(nodeContent);
    this.type = nodeType.type;
    this.component = nodeType.component;
    this.content = this.#setContentData(nodeContent);
  }

  getContentComponent() {
    return this.component;
  }

  getContentType() {
    return this.type;
  }

  getContentData() {
    return this.content;
  }

  // eslint-disable-next-line class-methods-use-this
  #setContentType = (nodeContent) => {
    switch (nodeContent.composant) {
      case 'sas_formation_titre':
        return { component: 'TitleBloc', type: 'title' };

      case 'sas_formation_text':
        return { component: 'TextBloc', type: 'text' };

      case 'sas_formation_media':
        return { component: 'ImgVidBloc', type: 'imgvid' };

      case 'sas_formation_information':
        return { component: 'InformationBloc', type: 'info' };

      case 'sas_formation_text_media':
        return { component: 'TextBloc', type: 'txtimgvid' };

      case 'sas_formation_file':
        return { component: 'FileBloc', type: 'file' };

      default:
        return { component: '', type: '' };
    }
  };

  // eslint-disable-next-line class-methods-use-this
  #setContentData = (content) => {
    switch (this.type) {
      case 'title':
        return {
          title: content.title,
        };

      case 'text':
        return {
          title: content.title,
          text: content.text,
        };

      // img or video
      case 'imgvid':
        if (content.selector === 'image') {
          return {
            title: content.title,
            img: content.image,
            type: 'img',
          };
        }
          return {
            title: content.title,
            vid: content.video,
            type: 'vid',
          };

      // txt + img or video
      case 'txtimgvid':
        if (content.selector === 'image') {
          return {
            title: content.title,
            img: content.image,
            pos: content.position,
            text: content.text,
            type: 'img',
          };
        }
          return {
            title: content.title,
            vid: content.video,
            pos: content.position,
            text: content.text,
            type: 'vid',
          };

      case 'info':
        return {
          icon: content.icon,
          text: content.text,
        };

      case 'file':
        return {
          file: content.file,
        };

      default:
        return {};
    }
  };
}
