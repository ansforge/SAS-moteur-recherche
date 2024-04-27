export default class CalendarSlotListClass {
  constructor(currentData) {
    this.slotData = currentData;
    this.todayVal = [];
    this.tomorrowVal = [];
    this.afterTomorrowVal = [];
  }

  getSlotList() {
    if (this.slotData.slotList) {
      this.todayVal = this.slotData?.slotList?.today || [];
      this.tomorrowVal = this.slotData?.slotList?.tomorrow || [];
      this.afterTomorrowVal = this.slotData?.slotList?.afterTomorrow || [];

      return {
        today: this.todayVal,
        tomorrow: this.tomorrowVal,
        afterTomorrow: this.afterTomorrowVal,
      };
    }
    return {};
  }
}
