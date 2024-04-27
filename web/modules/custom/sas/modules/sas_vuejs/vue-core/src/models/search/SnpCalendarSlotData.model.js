export default class SnpCalendarSlotDataClass {
  constructor(key, limitCount, todayVal, tomorrowVal, afterTomorrowVal) {
    this.item = key;
    this.limitCount = limitCount.value;
    this.today = todayVal.value;
    this.tomorrow = tomorrowVal.value;
    this.afterTomorrow = afterTomorrowVal.value;
  }

  getDataArray() {
    let results = [];
    switch (this.item) {
      case 'today':
        results = this.limitCount === 4 ? this.today.slice(0, this.limitCount) : this.today;
        break;
      case 'tomorrow':
        results = this.limitCount === 4 ? this.tomorrow.slice(0, this.limitCount) : this.tomorrow;
        break;
      case 'afterTomorrow':
        results = this.limitCount === 4 ? this.afterTomorrow.slice(0, this.limitCount) : this.afterTomorrow;
        break;
      default: break;
    }
    return results;
  }
}
