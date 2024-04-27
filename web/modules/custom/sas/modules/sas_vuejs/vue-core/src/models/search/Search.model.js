import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import isToday from 'dayjs/plugin/isToday';
import isBetween from 'dayjs/plugin/isBetween';

import SlotModel from '@/models/search/Slot.model';
import { phoneHelper } from '@/helpers';
import { useSasOrientationData } from '@/stores';
import ScheduleFormatModel from './ScheduleFormat.model';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(isToday);
dayjs.extend(isBetween);

export default class SearchClass {
  constructor(searchRequestRes = [], aggregRes = {}, sasRes = {}, popinSnpSettingsData = {}, preferredDoctorSettings = {}) {
    this.searchRequestRes = searchRequestRes;

    this.aggregRes = aggregRes;
    this.sasRes = sasRes;
    this.popinSnpSettingsData = popinSnpSettingsData;

    if (preferredDoctorSettings?.showFlag) {
      preferredDoctorSettings.data.map((x) => this.searchRequestRes.unshift(x));
    }
  }

  getSearchResultsData() {
    let result = [];

    this.searchRequestRes.forEach((card) => {
      if (card.its_nid) {
        // Every card could have multiple action returned from the api
        const cardAggregatorItems = [];

        // Variable to check if the aggregator is enabled in the back office
        let aggregatorEnabled = false;
        if (!card.bs_sas_editor_disabled) {
          aggregatorEnabled = true;
        }

        // if aggregator enabled, get all aggregator items for the card
        if (aggregatorEnabled) {
          Object.entries(this.aggregRes).forEach(([key, aggregItem]) => {
            if (key.toString() === card.its_nid.toString()) {
              cardAggregatorItems.push(aggregItem);
            } else if (aggregItem.nid && aggregItem.nid.toString() === card.its_nid.toString() && aggregItem.action === 'create') {
               // Random integer id is for the actions create because of the event with the map (on hover on the card and click on the marker)
              cardAggregatorItems.push({
                ...aggregItem,
                its_nid: Math.floor(100000 + Math.random() * 900000),
              });
            }
          });
        }

        const sasItem = this.sasRes[card.its_nid] || {};
        if (cardAggregatorItems.length > 0) {
          cardAggregatorItems.forEach((aggregatorItem) => {
            if (aggregatorItem.action !== 'delete') {
              result.push(this.#resolveCardSlots(card, aggregatorItem, sasItem));
            }
          });
        } else {
          result.push(this.#resolveCardSlots(card, {}, sasItem));
        }
      }
    });

    result = this.sortCards(result);

    return result;
  }

  // eslint-disable-next-line class-methods-use-this
  #setAgregFields = (agregConfig) => {
    let skillsEditor = [];
    let configFields = {};

    if (agregConfig.isAggreg) {
      configFields.isAggregator = true;
      configFields.action = agregConfig.aggregAction;
      configFields.final_phone_number = agregConfig?.aggregItem?.practitioner?.phone ?? '';

      // complete ps specialities from agreg
      skillsEditor = agregConfig?.aggregItem?.practitioner?.specialities || [];
    } else {
      configFields.doNotShowSlots = false;
      configFields.isSasApi = true;
    }

    if (agregConfig.aggregAction === 'create') {
      configFields = {
        ...configFields,
        its_nid: agregConfig?.aggregItem.its_nid,
        ss_field_address: agregConfig?.aggregItem.address?.line ?? '',
        ss_field_codepostal: agregConfig?.aggregItem.address?.cp ?? '',
        tm_X3b_und_field_ville: agregConfig?.aggregItem.address?.city ?? '',
        locs_field_geolocalisation_latlon: agregConfig?.aggregItem.address?.latitude
          ? (`${agregConfig?.aggregItem?.address?.latitude },${ agregConfig?.aggregItem?.address?.longitude}`)
          : agregConfig?.card?.locs_field_geolocalisation_latlon,
      };

      configFields.ss_field_address = `${configFields.ss_field_address }, ${ agregConfig?.aggregItem.address.cp } ${ agregConfig?.aggregItem.address.city}`;
    }

    return {
      configFields,
      skillsEditor,
    };
  };

  // eslint-disable-next-line class-methods-use-this
  #getFinalPhoneNumber = (card, agregFields, slotsList) => {
    const hasSlots = (
      slotsList.today?.length
      || slotsList.tomorrow?.length
      || slotsList.afterTomorrow?.length
    );

    if (
      agregFields.isAggreg
      && hasSlots
      && agregFields.final_phone_number
    ) {
      return agregFields.final_phone_number;
    }

    return (
      card.tm_X3b_und_field_phone_number?.[0]
      || card.tm_X3b_und_telephones?.[0]
      || card.tm_X3b_und_etb_telephones?.[0]
      || ''
    );
  };

  // eslint-disable-next-line class-methods-use-this
  #resolveCardSlots(card, aggregItem, sasItem) {
    let newCard = {};
    // Get aggregator action
    const aggregAction = aggregItem.action || '';
    const isAggreg = aggregAction === 'create' || aggregAction === 'update';

    // Get slots items from aggregtor or sas api
    const slots = isAggreg ? aggregItem : sasItem;
    const isPfg = (
      card.tm_X3b_und_establishment_type_names?.length
      && card.itm_establishment_types?.length
      && card?.tm_X3b_und_field_precision_type_eg?.length > 0
      && card?.tm_X3b_und_establishment_type_names.some((name) => name.toLowerCase().includes('sos médecin'))
    );

    // get timezone for slot calculations
    const slotTz = this.getSlotTimeZone(card, sasItem);

    const agregFields = this.#setAgregFields({
      isAggreg,
      aggregAction,
      aggregItem,
      card,
    });

    const slotsListAndTable = this.getSlotsTime(
      slots,
      isAggreg,
      slotTz,
    );

    const scheduleData = this.#getScheduleData(card.sm_field_horaires, slotTz);
    const cardTitles = this.#setCardTitleAndSubtitle(card);
    const cardIcon = this.#setCardPictoAndText(card);
    const sasParticipationLabel = this.#setSasParticipationLabel(card.its_sas_participation_via);
    const sasForfaitReuLabel = this.#setSasForfaitReuLabel();
    const finalPhoneNumber = this.#getFinalPhoneNumber(card, agregFields, slotsListAndTable.slotList);
    const finalAddress = this.#getFinalAddress({
      isPfg,
      aggregItem,
      aggregAction,
      isAggreg,
      card,
    });

    // add cpts flag to itm_establishment_types
    const isCPTS = (card.its_sas_participation_via === 2 && card.ss_sas_cpts_finess);
    if (isCPTS) {
      card.itm_establishment_types.push(222605);
    }

    newCard = {
      ...card,
      final_phone_number: finalPhoneNumber,
      isSOSMedecin: isPfg,
      scheduleData,
      ...slotsListAndTable,
      ...agregFields.configFields,
      sasParticipationLabel,
      sasForfaitReuLabel,
      finalAddress,
      defaultPicto: cardIcon,
      cardTitle: cardTitles.title,
      cardSubTitle: cardTitles.subtitle,
      calculatedTimeZone: slotTz,
      agregSpecialities: agregFields.skillsEditor,
      isCPTS,
      isMSP: card.its_sas_participation_via === 3,
    };

    newCard.final_phone_number = phoneHelper.formatPhoneNumber(newCard.final_phone_number);

    // SAS-4046
    if (aggregAction === 'create') {
      delete newCard.dist;
    }

    return newCard;
  }

  /**
   * Ordey by the cards list
   */
   sortCards(list) {
    const lrmList = this.sortCardsByFirstSlot(list.filter((x) => x.isLrmSearchWithPreferredDoctor));
    const searchList = this.sortCardsByFirstSlot(list.filter((x) => !x.isLrmSearchWithPreferredDoctor));

    return lrmList.concat(searchList);
  }

  /**
   * Order by the cards by the first available slot && sas participation
   * Niveau 1 : LRM
   * Niveau 2 : Par participation au SAS par disponibilité
   * Niveau 3 : reste de l'offre de soins par disponibilité
   */
  sortCardsByFirstSlot = (cards) => {
    const cardsWithSlots = cards.filter((card) => (
      card.slotList
      && (
        card.slotList.today?.length
        || card.slotList.tomorrow?.length
        || card.slotList.afterTomorrow?.length
      )
    ));

    const cardsWithoutSlots = cards.filter((card) => (
      card.slotList
      && (
        card.slotList.today?.length === 0
        && card.slotList.tomorrow?.length === 0
        && card.slotList.afterTomorrow?.length === 0
      )
    ));

    // sort by slot && with sas participation
    const cardsWithSasParticipation = cardsWithSlots.filter((card) => card.bs_sas_participation).sort(this.#sortCardsBySlot);
    // sort by slot && without sas participation
    const cardsWithoutSasParticipation = cardsWithSlots.filter((card) => !card.bs_sas_participation).sort(this.#sortCardsBySlot);

    // sort with sas participation
    const cardsNoSlotsWithSasParticipation = cardsWithoutSlots.filter((card) => card.bs_sas_participation);
    // sort without sas participation
    const cardsNoSlotsWithoutSasParticipation = cardsWithoutSlots.filter((card) => !card.bs_sas_participation);

    return cardsWithSasParticipation.concat(cardsWithoutSasParticipation, cardsNoSlotsWithSasParticipation, cardsNoSlotsWithoutSasParticipation);
  };

  getSlotsTime(data, isAggregator, slotTimeZone) {
    let result = {
      slotList: {
        today: [],
        tomorrow: [],
        afterTomorrow: [],
      },
      slotTable: {
        next4Hours: [],
        next4to8Hours: [],
        next8to12Hours: [],
        next12to24Hours: [],
        next24to48Hours: [],
        next48to72Hours: [],
      },
    };

    let slots = [];
    if (isAggregator) {
      // Getting slots from aggregator
      if (typeof (data.slot) === 'object') {
        Object.keys(data.slot).forEach((aggregKey) => {
          data.slot[aggregKey].forEach((slot) => {
            slots.push(new SlotModel(slot, slotTimeZone, isAggregator, this.popinSnpSettingsData));
          });
        });
      } else {
        slots = [];
        result.showEmptyAggregator = true;
      }
    } else if (Array.isArray(data.slots)) {
      slots = [...data.slots.map((slot) => new SlotModel(slot, slotTimeZone, isAggregator, this.popinSnpSettingsData))];
    } else {
      slots = [];
      result.showEmptySas = true;
    }

    if (slots && slots.length > 0) {
      slots.forEach((slotItem) => {
        const currentDateRef = dayjs().tz(slotTimeZone);
        let isTimeInThePast = false;

        if (isAggregator) {
          isTimeInThePast = currentDateRef.toDate() > slotItem.startDate.toDate();
        }

        if (isTimeInThePast) return;

        const newSlotItem = {
          ...slotItem,
          slotGuid: Math.floor(Math.random() * 1000),
        };

        result = { ...result, ...slotItem.modalityTypes };
        const slotTableKeys = this.getSlotTableKeys(slotItem);
        slotTableKeys.forEach((tableKey) => {
          if (result.slotTable[tableKey]) {
            result.slotTable[tableKey].push(newSlotItem);
          }
        });

        if (result.slotList[this.getSlotListKey(slotItem)]) {
          result.slotList[this.getSlotListKey(slotItem)].push(newSlotItem);
        }

        result.isTodayExist = result.slotList.today.length > 0;
      });

      const slotOrderByDateTime = (itemA, itemB) => itemA.dateByTimezone - itemB.dateByTimezone;
      const listKeys = ['today', 'tomorrow', 'afterTomorrow'];
      const tableKeys = ['next4Hours', 'next4to8Hours', 'next8to12Hours', 'next12to24Hours', 'next24to48Hours', 'next48to72Hours'];

      listKeys.forEach((key) => result.slotList[key].sort(slotOrderByDateTime));
      tableKeys.forEach((key) => result.slotTable[key].sort(slotOrderByDateTime));

      result.isSansRdvExist = !!slots.find((x) => x.horaire_type && x.horaire_type === 6);
      result.isSnpExist = !!slots.find((x) => !x.horaire_type || x.horaire_type !== 6);
      result.aggregatorId = isAggregator ? data.id : null;
    }

    return result;
  }

  // eslint-disable-next-line class-methods-use-this
  getSlotTimeZone = (currentCard, sasItem) => {
    let defaultTimezone = '';
    // if has slots && timezone is in slots
    if (sasItem.slots?.length) {
      const slotTimeZone = sasItem.slots.find((slot) => slot.schedule?.timezone && slot.schedule.timezone !== '');
      defaultTimezone = slotTimeZone?.schedule?.timezone;
    }

    // else if get solr timezone
    if (!defaultTimezone && currentCard.ss_sas_timezone) {
      defaultTimezone = currentCard.ss_sas_timezone;
    }

    // else get current user timezone || default timezone
    if (!defaultTimezone) {
      defaultTimezone = 'Europe/Paris';
    }

    return defaultTimezone;
  };

  /**
   *
   * @param {dayjs} slotDate
   * @param {dayjs} rangeStart
   * @param {dayjs} rangeEnd
   * @returns Boolean
   */
  // eslint-disable-next-line class-methods-use-this
  #dateIsInRange = (slotDate, rangeStart, rangeEnd) => (
      slotDate.startDate.isBetween(rangeStart, rangeEnd)
      || slotDate.endDate.isBetween(rangeStart, rangeEnd)
      || (slotDate.startDate <= rangeStart && slotDate.endDate >= rangeEnd)
    );

  /**
   * Get slotTable key
   * Check the time of the slot to be in which column
   */
  getSlotTableKeys = (slotItem) => {
    const currentDate = dayjs();

    const getDateRangeKeys = (slotDate) => {
      const ranges = [
        { start: 0, end: 4, key: 'next4Hours' },
        { start: 4, end: 8, key: 'next4to8Hours' },
        { start: 8, end: 12, key: 'next8to12Hours' },
        { start: 12, end: 24, key: 'next12to24Hours' },
        { start: 24, end: 48, key: 'next24to48Hours' },
        { start: 48, end: 72, key: 'next48to72Hours' },
      ];

      return ranges
        .filter((range) => this.#dateIsInRange(slotDate, currentDate.add(range.start, 'h'), currentDate.add(range.end, 'h')))
        .map((range) => range.key);
    };

    return [...new Set(getDateRangeKeys(slotItem))];
  };

  /**
   * Get SlotList key
   */
  // eslint-disable-next-line class-methods-use-this
  getSlotListKey = (slotItem) => {
    const currentDate = new Date();
    const todayDay = currentDate.getDate();
    const tomorrowDay = dayjs().add(1, 'day').date();
    const afterTomorrowDay = dayjs().add(2, 'day').date();

    if (slotItem.startDate.date() === todayDay) {
      return 'today';
    }
    if (slotItem.startDate.date() === tomorrowDay) {
      return 'tomorrow';
    }
    if (slotItem.startDate.date() === afterTomorrowDay) {
      return 'afterTomorrow';
    }
      return '';
  };

  /**
   *
   * @param {Slot} itemA
   * @param {Slot} itemB
   * @returns Boolean
   */
  // eslint-disable-next-line class-methods-use-this
  #sortCardsBySlot = (itemA, itemB) => {
    let aTimeStamps = [];
    let bTimeStamps = [];

    const aToday = itemA.slotList.today;
    const aTomorrow = itemA.slotList.tomorrow;
    const aAfterTomorrow = itemA.slotList.afterTomorrow;

    const bToday = itemB.slotList.today;
    const bTomorrow = itemB.slotList.tomorrow;
    const bAfterTomorrow = itemB.slotList.afterTomorrow;

    aTimeStamps = aToday.map((v) => new Date(v.dateByTimezone));
    aTimeStamps = aTimeStamps.concat(aTomorrow.map((v) => new Date(v.dateByTimezone)));
    aTimeStamps = aTimeStamps.concat(aAfterTomorrow.map((v) => new Date(v.dateByTimezone)));

    bTimeStamps = bToday.map((v) => new Date(v.dateByTimezone));
    bTimeStamps = bTimeStamps.concat(bTomorrow.map((v) => new Date(v.dateByTimezone)));
    bTimeStamps = bTimeStamps.concat(bAfterTomorrow.map((v) => new Date(v.dateByTimezone)));

    return Math.min(...aTimeStamps) - Math.min(...bTimeStamps);
  };

  /**
   * formats opening/closing hours
   * @param {Array} horaires
   * @param {String} slotTimezone
   * @returns {Object} formatted schedule data
   */
  // eslint-disable-next-line class-methods-use-this
  #getScheduleData = (horaires = [], slotTimezone = '') => {
    const scheduleFormatData = new ScheduleFormatModel(horaires, slotTimezone);

    return horaires.length
    ? scheduleFormatData.getFormatScheduleData(horaires, false, null, '- Ouvre à')
    : {};
  };

  /**
   * sets the title && subtitle for the card
   * @param {Object} card
   * @returns {Object} title && subtitle
   */
  // eslint-disable-next-line class-methods-use-this
  #setCardTitleAndSubtitle = (card) => {
    const cardTitles = {
      title: card?.tm_X3b_und_title?.length ? card.tm_X3b_und_title[0] : '',
      subtitle: '',
    };

    switch (card.ss_type) {
      case 'professionnel_de_sante':
        cardTitles.subtitle = card?.tm_X3b_und_field_profession_name?.length
        ? card.tm_X3b_und_field_profession_name[0]
        : '';
        break;

      case 'service_de_sante':
      case 'finess_institution':
      case 'entite_geographique':
      case 'health_institution':
      case 'care_deals':
        cardTitles.subtitle = card?.tm_X3b_und_establishment_type_names?.length
        ? card.tm_X3b_und_establishment_type_names[0]
        : '';
        break;

      default:
        break;
    }

    return cardTitles;
  };

  /**
   * sets card icon type and icon text
   * @param {Object} card
   * @returns {Object} card icon && icon text
   */
  // eslint-disable-next-line class-methods-use-this
  #setCardPictoAndText = (card) => {
    const picto = {
      icon: '',
      iconText: '',
    };

    switch (card.ss_type) {
      case 'professionnel_de_sante':
        picto.icon = 'icon-stethoscope';
        picto.iconText = 'Professionel de santé';
        break;

      case 'service_de_sante':
      case 'finess_institution':
        picto.icon = 'icon-pharmacie';
        picto.iconText = 'Service de santé';
        break;

      case 'entite_geographique':
      case 'health_institution':
      case 'care_deals':
        picto.icon = 'icon-hospital';
        picto.iconText = 'Établissement de soins';
        break;

      default:
        break;
    }

    return picto;
  };

  /**
   * sets sas participation label for the card
   */
  // eslint-disable-next-line class-methods-use-this
  #setSasParticipationLabel = (sasParticipationVia) => {
    const sasOrientationData = useSasOrientationData();
    let suffix = '';

    switch (sasParticipationVia) {
      case 2:
        suffix = ' via la CPTS';
        break;

      case 3:
        suffix = ' via une MSP';
        break;

      default:
        break;
    }

    return sasOrientationData.sasParticipationLabel + suffix;
  };

  // eslint-disable-next-line class-methods-use-this
  #setSasForfaitReuLabel = () => {
    const sasOrientationData = useSasOrientationData();
    return sasOrientationData.sasForfaitReuLabel;
  };

  // eslint-disable-next-line class-methods-use-this
  #getFinalAddress = (adressData) => {
      if (
        adressData.isAggreg
        && adressData.aggregAction === 'create'
      ) {
        return adressData.isPfg
        ? adressData.aggregItem?.address?.city
        : `${ adressData.aggregItem?.address.line }, ${ adressData.aggregItem?.address.cp } ${ adressData.aggregItem?.address.city}`;
      }

      return adressData.isPfg
      ? adressData.card?.tm_X3b_und_field_ville?.shift()
      : adressData.card?.ss_field_address;
  };
}
