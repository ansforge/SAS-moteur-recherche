import { ScheduleFormatModel } from '@/models';

export default () => {
  /**
   * get distance between target and research
   * @param value
   * @returns {string}
   */
  function getDistance(value) {
    if (!value) return '';
    return Math.ceil(value) > 1 ? `${Math.ceil(value)} km` : `${Math.ceil(value * 1000)} m`;
  }

  /**
   * get the redirection url with sas, aggregator's and location id's informations
   * @param value
   * @returns {string}
   */
  function getDetailsPageUrl(value) {
    if (!value || !value.ss_field_node_path_alias) return '';
    return `${value.ss_field_node_path_alias.replace('#q=', '')}?sas_back=${encodeURIComponent(document.location.pathname + document.location.search)}${value.isAggregator ? '&agreg=1' : ''}${value.isAggregator && value.aggregatorId ? `&location_id=${value.aggregatorId}` : ''}`;
  }

  /**
   * get fly to animation to target market on click to adresse in the card
   * @param currentCoords
   */
  function getPictoPopUp(currentCoords) {
    const evt = new CustomEvent('map::askFlyTo', {
      detail: {
        coords: currentCoords,
      },
    });
    window.dispatchEvent(evt);
  }

  /**
   * Check if we show/hide the sas particpation text
   * @param card
   * @param currentUser
   * @returns {string}
   */
  function showSasParticipationText(card, currentUser) {
    return currentUser.isRegulateurOSNP && card.bs_sas_participation;
  }

  /**
   * @deprecated
   * Get sas participation text
   * @param card
   * @param sasParticipationData
   * @param currentUser
   * @returns {string}
   */
  function getSasParticipationText(card, sasParticipationData, currentUser) {
    let text = '';

    if (showSasParticipationText(card, currentUser)) {
      text = sasParticipationData.pictogram_label;
      text += (card.its_sas_participation_via === 2 ? ' via la CPTS' : '');
      text += (card.its_sas_participation_via === 3 ? ' via une MSP' : '');
    }

    return text;
  }

  /**
   * Check if we show the additional information
   * @param card
   * @param currentUser
   * @returns {boolean}
   */
  function showAdditionalInfo(card, currentUser) {
    return card.ss_sas_additional_info?.trim().length && (currentUser.isRegulateurOSNP || currentUser.isRegulateurIOA);
  }

  /**
   * Check if we show the additional information regarding specialities
   * @param card
   * @param currentUser
   * @returns {boolean}
   */
  function showEditorSkills(card, currentUser) {
    return card.agregSpecialities?.length && (currentUser.isRegulateurOSNP || currentUser.isRegulateurIOA);
  }

  function getScheduleData(horaires = [], timezone = '') {
    const scheduleFormatData = new ScheduleFormatModel(horaires, timezone);
    if (horaires.length) {
      return scheduleFormatData.getFormatScheduleData(horaires, false, null, '- Ouvre à');
    }
    return {};
  }

  return {
    getDistance,
    getDetailsPageUrl,
    getPictoPopUp,
    showSasParticipationText,
    getSasParticipationText,
    showAdditionalInfo,
    showEditorSkills,
    getScheduleData,
  };
};
