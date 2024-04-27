export default class ScheduleFormatModelClass {
  constructor(scheduleData, getTz) {
    this.daysInWeek = 7;
    this.daysTable = [
      'Dimanche',
      'Lundi',
      'Mardi',
      'Mercredi',
      'Jeudi',
      'Vendredi',
      'Samedi',
    ];
    this.daysTableISO = [
      'dimanche',
      'lundi',
      'mardi',
      'mercredi',
      'jeudi',
      'vendredi',
      'samedi',
    ];
    this.frenchOrder = [1, 2, 3, 4, 5, 6, 0, 1, 2, 3, 4, 5, 6, 0]; // two arr trick :)
    this.startingDayIndex = 0;
    this.currentDate = new Date();
    this.timezone = getTz;
    this.scheduleData = scheduleData;
    this.orderScheduleData = [];
  }

  getCurrentDay() {
    this.currentDay = this.currentDate.toLocaleString('fr-FR', {
      weekday: 'long',
      timeZone: this.timezone.value,
    });
    return this.currentDay;
  }

   getCurrentHour() {
     this.currentHour = parseInt(this.currentDate.toLocaleString('fr-FR', {
         hour: '2-digit',
         hour12: false,
         timeZone: this.timezone.value,
       }), 10);
     return this.currentHour;
   }

   getCurrentMinute() {
     this.currentMinute = parseInt(this.currentDate.toLocaleString('fr-FR', {
         minute: '2-digit',
         hour12: false,
         timeZone: this.timezone.value,
       }), 10);
     return this.currentMinute;
   }

   getCurrentTime() {
     this.currentTime = this.getCurrentHour() + ((this.getCurrentMinute < 10 ? '0' : '') + this.getCurrentMinute());
     return this.currentTime;
   }

   getCurrentTimeInt() {
    this.currentTimeInt = parseInt(this.getCurrentTime(), 10);
    return this.currentTimeInt;
   }

   getStartingDayIndex() {
    this.startingDayIndex = this.frenchOrder.indexOf(this.daysTableISO.indexOf(this.getCurrentDay()));
    return this.startingDayIndex;
   }

   getFormatScheduleData(currentHoraires, open, nextTime, nextTimeMsg) {
    this.currentHoraires = currentHoraires;
    this.open = open;
    this.nextTime = nextTime;
    this.nextTimeMsg = nextTimeMsg;
    this.result = {};
    if (this.currentHoraires && this.currentHoraires.length) {
      this.horaireTable = [];
      this.horaireTableOrdered = [];
      this.currentHoraires.forEach((horaire) => {
        const h = horaire.split('|');

        if (h.length === 5) {
          if (h[4] === '1') {
            if (!this.horaireTable[parseInt(h[0], 10)]) {
              this.horaireTable[parseInt(h[0], 10)] = [];
            }

            this.horaireTable[parseInt(h[0], 10)].push(h);
          }
        }
      });

      if (this.horaireTable.length) {
        for (
          let dayIndex = this.getStartingDayIndex();
          dayIndex < this.daysInWeek + this.getStartingDayIndex();
          dayIndex += 1
        ) {
          const currentDayIndex = this.frenchOrder[dayIndex];
          const hT = this.horaireTable[currentDayIndex];

          if (hT) {
            const pushedIndex = this.horaireTableOrdered.push([]) - 1;

            hT.forEach((t) => {
              for (let i = 1; i < 3; i += 1) {
                if (t[i].length < 3) {
                  const itemT = t;
                  itemT[i] = (itemT[i].length < 2 ? '00' : '0') + itemT[i];
                }
              }
              this.horaireTableOrdered[pushedIndex].push(t.join('|'));
            });

            // Sorting horaire
            this.horaireTableOrdered[pushedIndex].sort((a, b) => {
              const a1 = parseInt(a.split('|')[1], 10);
              const b1 = parseInt(b.split('|')[1], 10);

              return a1 - b1;
            });
          }
        }

        const presentDay = this.daysTableISO.indexOf(this.getCurrentDay());

        const currentDayCompareFunc = (byDay) => {
          byDay.forEach((_t) => {
            if (!this.open || this.nextTime === null) {
              const $t = _t.split('|');

              if ($t.length === 5) {
                const a = parseInt($t[1], 10);
                const b = parseInt($t[2], 10);

                if (a <= this.getCurrentTimeInt() && b >= this.getCurrentTimeInt()) {
                  this.open = true;
                  this.nextTime = $t[2];
                  this.nextTimeMsg = '- Ferme à';
                } else if (this.nextTime === null) {
                  if (a > this.getCurrentTimeInt()) {
                    this.nextTime = $t[1];
                  }
                }
              }
            }
          });
        };

        const nextDayCompareFunc = (byDay) => {
          byDay.forEach((_t) => {
            if (this.nextTime === null) {
              const $t = _t.split('|');

              if ($t.length === 5) {
                this.nextTime = $t[1];
                this.nextTimeMsg = `- Ouvre ${this.daysTable[parseInt($t[0], 10)]}  à`;
              }
            }
          });
        };

        // check if open
        this.horaireTableOrdered.forEach((byDay) => {
          if (!this.open || this.nextTime === null) {
            const currentDayIndex = parseInt(byDay[0].split('|')[0], 10);

            if (presentDay === currentDayIndex) {
              currentDayCompareFunc(byDay);
            } else {
              // next day available
              nextDayCompareFunc(byDay);
            }
          }
        });

        if (this.nextTime === null) {
          if (this.horaireTableOrdered && this.horaireTableOrdered.length) {
            nextDayCompareFunc(this.horaireTableOrdered[0]);
          }
        }
      }
    }
   this.result = {
     horaireTraite: this.horaireTableOrdered,
     open: this.open,
     nextTime: this.nextTime,
     nextTimeMsg: this.nextTimeMsg,
   };
   return this.result;
   }
}
