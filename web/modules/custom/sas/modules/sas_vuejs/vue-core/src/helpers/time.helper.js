import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';

dayjs.extend(timezone);
dayjs.extend(utc);

// eslint-disable-next-line import/prefer-default-export
export function convertToSeconds(hours, minutes) {
  return (hours * 3600) + (minutes * 60);
}

export function generateHoursOptions() {
  const res = [{ label: '00h00', value: '00h00' }];
  let end = false;
  while (!end) {
    const previous = {
      h: Number(res[res.length - 1].value.slice(0, 2)),
      m: Number(res[res.length - 1].value.slice(3)),
    };
    let { h } = previous;
    let { m } = previous;
    if (previous.m < 45) {
      m += 15;
    } else if (h === 23 && m === 45) {
      m = 59;
      end = true;
    } else {
      h += 1;
      m = 0;
    }
    res.push({
      label: `${String(h).padStart(2, '0')}h${String(m).padStart(2, '0')}`,
      value: `${String(h).padStart(2, '0')}h${String(m).padStart(2, '0')}`,
    });
  }
  return res;
}

/**
 * transforms a timezone in string to hours
 * @param {string} timezoneLabel
 * @example formatTimeZoneToHour('Europe/Paris') === '+01:00'
*/
export function formatTimeZoneToHour(timezoneLabel) {
  const currentTimeZone = timezoneLabel || 'Europe/Paris';
  const testDate = dayjs(new Date()).tz(currentTimeZone);
  let hour = Math.trunc(testDate.utcOffset() / 60);
  let min = `${Math.abs(testDate.utcOffset() % 60)}`;
  const sign = Math.sign(hour);

  hour = `${Math.abs(hour)}`;
  hour = (sign < 0 ? '-' : '+') + hour.padStart(2, '0');
  min = min.padStart(2, '0');

  return `${hour}:${min}`;
}

/**
* @param {string} str - A string formatted like so: HH[h]mm
*/
export function convertStringHourToNbSeconds(str) {
 const [hours, minutes] = str.split('h');
 return convertToSeconds(parseInt(hours, 10), parseInt(minutes, 10));
}

/**
 * Get value of the date with utc() or not to handle daylight saving time
 *
 * @param {string} timezoneValue
 * @param {Date} date
 * @param {string} direction
 * @param {number} dayCount
 * @param {string} dateFormat
 *
 * @returns {string} The adjusted date in the specified format.
 */
export function handleDateWithOffset(timezoneValue, date, direction, dayCount, dateFormat, isStartDate = false) {
  if (
    !isStartDate
    && timezoneValue === 'Europe/Paris'
    && dayjs(date).utcOffset() === 120
  ) {
    let dateValue = dayjs(date);
    dateValue = direction === 'forward'
      ? dateValue.add(dayCount, 'day')
      : dateValue.subtract(dayCount, 'day');

    return dateValue.utc().format(dateFormat);
  }
  return direction === 'forward'
    ? dayjs(date).add(dayCount, 'day').format(dateFormat)
    : dayjs(date).subtract(dayCount, 'day').format(dateFormat);
}
