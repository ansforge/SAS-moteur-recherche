import dayjs from 'dayjs';

export default class SnpCalendarDayNameClass {
  constructor(day) {
    this.tabJour = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    this.todayValue = new Date();
    this.tomorrowValue = new Date(this.todayValue);
    this.afterTomorrowValue = new Date(this.todayValue);
    this.days = {
      today: this.todayValue,
      tomorrow: this.tomorrowValue,
      afterTomorrow: this.afterTomorrowValue,
    };
    this.dayName = day;
  }

  getDayNameAndDate() {
    this.tomorrowValue.setDate(this.tomorrowValue.getDate() + 1);
    this.afterTomorrowValue.setDate(this.afterTomorrowValue.getDate() + 2);
    const date = dayjs(this.days[this.dayName]).get('D');
    return `${this.tabJour[this.days[this.dayName].getDay()]} ${date}`;
  }
}
