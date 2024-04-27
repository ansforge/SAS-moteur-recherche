import { formatTimeZoneToHour } from '@/helpers';

export default () => {
  function getPayloadDate(date, systemTimezone, isStartDate = false) {
    const timezoneVal = systemTimezone === 'Europe/Paris'
        ? '+01:00'
        : formatTimeZoneToHour(systemTimezone);
    if (isStartDate) {
      let startDate = date.utc();
      startDate = startDate.tz(systemTimezone);
      return `${startDate.format('YYYY-MM-DDTHH:mm:ss')}${timezoneVal}`;
    }
    return `${date}T23:59:59${timezoneVal}`;
  }

  return {
    getPayloadDate,
  };
};
