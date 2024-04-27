import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isToday from 'dayjs/plugin/isToday';
import SlotModel from './Slot.model';

dayjs.locale('fr');
dayjs.extend(isToday);

export default class SlotsModel {
  constructor(ApiResponse = [], timezone = null, isAggregator = false) {
    this.slots = ApiResponse
      .map((slot) => new SlotModel(slot, timezone, isAggregator))
      .sort((slotA, slotB) => (slotA.dateByTimezone - slotB.dateByTimezone));
  }

  isTodayExists() {
    return this.slots.findIndex((slot) => dayjs(slot.startDate).isToday()) > -1;
  }

  isAfterTodayExists() {
    const today = dayjs().set('hour', 23).set('minute', 59).set('second', 59);
    return this.slots.findIndex((slot) => slot.startDate > today) > -1;
  }

  getSlots() {
    return this.slots;
  }

  // Slots classified by their dateNumber.
  getColumns(firstDateOfWeek) {
    const columns = {};
    this.slots.forEach((slot) => {
      const dateNumber = slot.getDateNumber(firstDateOfWeek);
      if (columns[dateNumber]) {
        columns[dateNumber].push(slot);
      } else {
        columns[dateNumber] = [slot];
      }
    });

    Object.keys(columns).forEach((key) => {
      columns[key].sort((slotA, slotB) => (slotA.dateByTimezone - slotB.dateByTimezone));
    });
    return columns;
  }

  getFirstSlotHour() {
    return Math.min(...this.slots.map((x) => x.startDate.format('HH')));
  }
}
