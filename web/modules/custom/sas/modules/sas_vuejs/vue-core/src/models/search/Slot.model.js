/* eslint-disable camelcase */
import dayjs from 'dayjs';

import 'dayjs/locale/fr';
import isoWeek from 'dayjs/plugin/isoWeek';
import isToday from 'dayjs/plugin/isToday';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import SNP_CONST from '@/const/snp.const';

dayjs.locale('fr');
dayjs.extend(isoWeek);
dayjs.extend(isToday);
dayjs.extend(timezone);
dayjs.extend(utc);

export default class SlotModel {
  constructor(
    slotItem = {},
    timezoneLabel = 'Europe/Paris',
    isAggregator = false,
    popinSnpSettingsData = {},
  ) {
    this.isAggregator = isAggregator;
    this.isSNP = !isAggregator;

    this.timezone = timezoneLabel;
    this.timezoneOffset = this.#formatTimeZoneToHour();

    // If data coming from sas api, we should update the hour based on start_hours and end_hours
    const currentSlot = this.isAggregator
      ? this.#getAggregSlotData(slotItem)
      : this.#getSnpSlotData(slotItem);

    this.type = currentSlot.type;
    this.id = currentSlot.id;

    this.date = currentSlot.date;
    this.real_date = currentSlot.real_date;

    this.startHours = currentSlot.start_hours;
    this.endHours = currentSlot.end_hours;
    this.start = {
      hours: Number(currentSlot.start_hours.slice(0, -2).padStart(2, '0')),
      minutes: Number(currentSlot.start_hours.slice(-2).padStart(2, '0')),
    };
    this.end = {
      hours: Number(currentSlot.end_hours.slice(0, -2).padStart(2, '0')),
      minutes: Number(currentSlot.end_hours.slice(-2).padStart(2, '0')),
    };

    this.max_patients = currentSlot.max_patients;
    this.orientation_count = currentSlot.orientation_count;
    // When there is no modalities, API send an object with an empty string inside.
    // So, in order to be logical, we remove those empty strings.
    const modality = this.#setModalities(
      currentSlot.modalities,
      this.isAggregator,
      popinSnpSettingsData,
    );
    this.modalityTypes = { ...modality.modalityTypes };
    this.modalite = [...modality.modalities];
    this.modalities = currentSlot.modalities.filter((e) => e.trim());

    const shouldAdjustDate = Number(this.startHours) > Number(this.endHours);
    this.day = this.#getSlotDay(currentSlot);
    this.startDate = this.#setDateInTimeZone(this.startHours);
    this.endDate = this.#setDateInTimeZone(this.endHours, shouldAdjustDate);
    this.dateByTimezone = this.#setDateInTimeZone(this.startHours);
    this.time = `${this.startDate.format('HH')}h${this.startDate.format('mm')}`;
    this.slot_reservation_link = currentSlot.slot_reservation_link;
    this.schedule = currentSlot.schedule;
    this.isToday = this.startDate.isToday();
    this.horaire_type = currentSlot.horaire_type;
    this.orientation_count = currentSlot.orientation_count;

    if (!this.isAggregator) {
      this.time += ` - ${this.endDate.format('HH')}h${this.endDate.format(
        'mm',
      )}`;
    }
  }

  // handle all rules for CSS class for a slot
  getSlotClass = (user, isFromSchedulePage) => (
    !isFromSchedulePage
    ? [
        this.#getRdvClass(),
        this.#getClassIsToday(),
        this.#getInactiveCLass(user, isFromSchedulePage),
        this.#getSlotClass(isFromSchedulePage),
      ]
    : [
        this.#getSlotClass(isFromSchedulePage),
        this.#getBookedSlotBackground(),
      ]
  );

  // add class to display slot of the day
  #getClassIsToday = () => (this.isToday ? 'availabilities-slot-today' : '');

  // handle class of slot from SAS-API and Aggregator-API
  #getRdvClass = () => {
    // check if is a SAS slot or Aggreg Slot for hover effect
    if (
      !this.slot_reservation_link
      && !this.isAggregator
    ) {
      return 'creneaux__liste sas-slot-hour-wrapper';
    }
    return 'creneaux__liste';
  };

  // inactive class disable click
  #getInactiveCLass = (user) => ((!user.isRegulateurOSNPorIOA || this.isAggregator)
    ? 'slot-inactive'
    : '');

  // slot class of schedule or other page
  // eslint-disable-next-line class-methods-use-this
  #getSlotClass = (isFromSchedulePage) => (isFromSchedulePage ? 'slotBox' : 'availabilities-slot');

  // fetch if a plage is full or creneau is booked
  #getBookedSlotBackground = () => {
    if (
      (
        this.max_patients !== -1 && this.orientation_count === this.max_patients
        )
      || (
        this.max_patients === -1 && this.orientation_count === 1
        )
      ) {
      return 'slot-is-full';
    }
    return '';
  };

  // eslint-disable-next-line class-methods-use-this
  #getAggregSlotData = (slotItem) => {
    const currentDateStart = slotItem.start
      ? dayjs(slotItem.start).utc()
      : dayjs().utc();
    const currentDateEnd = slotItem.end
      ? dayjs(slotItem.end).utc()
      : dayjs().utc();

    return {
      id: null,
      type: '',
      date: currentDateStart.format('YYYY-MM-DDTHH:mm:ssZ'),
      real_date: currentDateStart.format('YYYY-MM-DDTHH:mm:ssZ'),
      start_hours: `${currentDateStart.format('Hmm')}`,
      end_hours: `${currentDateEnd.format('Hmm')}`,
      day: null,
      max_patients: -1,
      orientation_count: -1,
      modalities: slotItem.consultation_type || slotItem.consultationTypes || [],
      schedule: {},
      slot_reservation_link: slotItem.slot_reservation_link || slotItem.reservationLink || '',
    };
  };

  // eslint-disable-next-line class-methods-use-this
  #getSnpSlotData = (slotItem) => ({
    id: slotItem.id || null,
    type: slotItem.type || '',
    date: slotItem.date || '',
    real_date: slotItem.real_date || '',
    start_hours: slotItem.start_hours || '',
    end_hours: slotItem.end_hours || '',
    day: slotItem.day || null,
    max_patients: slotItem.max_patients || -1,
    orientation_count: slotItem.orientation_count || 0,
    modalities: slotItem.modalities || [],
    schedule: slotItem.schedule || {},
    slot_reservation_link: '',
  });

  /**
   * transforms a timezone to offset
   * 'Europe/Paris' => +01:00
   * @returns String
   */
  #formatTimeZoneToHour = () => {
    const testDate = dayjs(new Date()).tz(this.timezoneLabel);
    let hour = Math.trunc(testDate.utcOffset() / 60);
    let min = `${Math.abs(testDate.utcOffset() % 60)}`;
    const sign = Math.sign(hour);

    hour = `${Math.abs(hour)}`;
    hour = (sign < 0 ? '-' : '+') + hour.padStart(2, '0');
    min = min.padStart(2, '0');

    return `${hour}:${min}`;
  };

  /**
   * calculates a date in timezone from date utc
   * @param {*} timeVal
   * @returns dayjs
   */
  #setDateInTimeZone = (timeVal = '00:00', shouldAdjustDate = false) => {
    const dateSlot = this.type === 'recurring' ? this.real_date : this.date;
    const dateString = dateSlot ? dateSlot.split('T') : [];

    const dateLabel = dateString.length > 0
        ? dateString[0]
        : dayjs().utc().format('YYYY-MM-DD');
    const slotHour = timeVal.slice(0, -2);
    const slotMinute = timeVal.slice(-2);

    let slotDate = dayjs(`${dateLabel} ${slotHour}:${slotMinute}`).utc(true);
    // adjust end date in tz
    if (shouldAdjustDate) {
      slotDate = slotDate.add(1, 'day');
    }
    slotDate = slotDate.tz(this.timezone);

    if (
      this.isSNP
      && this.timezone === 'Europe/Paris'
      && slotDate.utcOffset() === 120
    ) {
      slotDate = dayjs(`${dateLabel} ${slotHour}:${slotMinute}:00+01:00`).utc(true);
    }

    return slotDate;
  };

  /**
   * calculates day of the week
   * @returns Number
   */
  #getSlotDay = () => {
    const dateSlot = this.type === 'recurring' ? this.real_date : this.date;
    const startDateString = dateSlot ? dateSlot.split('T') : [];
    const startDateLabel = startDateString.length > 0
        ? startDateString[0]
        : dayjs().utc().format('YYYY-MM-DD');
    const slotStartHour = this.startHours.slice(0, -2);
    const slotStartMinute = this.startHours.slice(-2);
    let startDate = dayjs(`${startDateLabel} ${slotStartHour}:${slotStartMinute}`).utc(
      true,
    );
    startDate = startDate.tz(this.timezone);

    if (
      this.isSNP
      && this.timezone === 'Europe/Paris'
      && startDate.utcOffset() === 120
    ) {
      startDate = dayjs(`${startDateLabel} ${slotStartHour}:${slotStartMinute}:00+01:00`).utc(true);
    }

    return startDate.day() % 7;
  };

  // firstDateOfWeek need to be a dayjs object
  getRealDate = (firstDateOfWeek) => firstDateOfWeek.add(this.day - 1, 'day');

  getDateNumber(firstDateOfWeek) {
    return this.getRealDate(firstDateOfWeek).date();
  }

  getModalities() {
    // Because we could have old and unsed modalities returned by API, ex: "over-booked"
    const modalitiesKeys = Object.keys(SNP_CONST.modalities);
    return this.modalities.filter((e) => modalitiesKeys.includes(e));
  }

  getFullStartDate(firstDateOfWeek) {
    const dateWithoutTime = this.getRealDate(firstDateOfWeek).format('YYYY-MM-DD');

    return dayjs(dateWithoutTime)
      .add(this.startDate.hour(), 'hour')
      .add(this.startDate.minute(), 'minute')
      .format('YYYY-MM-DDTHH:mm:ss');
  }

  getFullEndDate(firstDateOfWeek) {
    const dateWithoutTime = this.getRealDate(firstDateOfWeek).format('YYYY-MM-DD');

    return dayjs(dateWithoutTime)
      .add(this.endDate.hour(), 'hour')
      .add(this.endDate.minute(), 'minute')
      .format('YYYY-MM-DDTHH:mm:ss');
  }

  /**
   * Check the modalities and push them in the order C T D to the new item modality
   * SlotItem.modalities comes from sas api, slotItem.consultation_type comes from aggregator
   */
  #setModalities = (slotModalities, popinSnpSettingsData) => {
    const cabinetText = this.isAggregator ? 'AMB' : 'physical';
    const teleconsultationText = this.isAggregator ? 'VR' : 'teleconsultation';
    const domicileText = this.isAggregator ? 'HH' : 'home';
    const modalities = [];
    const modalityTypes = {};

    if (slotModalities.indexOf(cabinetText) > -1) {
      modalities.push(popinSnpSettingsData.group4?.initial_cabinet || 'C');
      modalityTypes.isConsultationEnCabinetExist = true;
    }

    if (slotModalities.indexOf(teleconsultationText) > -1) {
      modalities.push(
        popinSnpSettingsData.group4?.initial_teleconsultation || 'T',
      );
      modalityTypes.isTeleconsultationExist = true;
    }

    if (slotModalities.indexOf(domicileText) > -1) {
      modalities.push(popinSnpSettingsData.group4?.initial_domicile || 'D');
      modalityTypes.isVisiteDomicileExist = true;
    }

    return {
      modalities,
      modalityTypes,
    };
  };
}
