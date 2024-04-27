import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import { OrientationService } from '@/services';

dayjs.extend(utc);
dayjs.extend(timezone);

export default () => {
  /**
   * Format hours
   */
  function hoursSlotFormatted(hours) {
    let hoursFormat = hours;
    if (hours.length === 3) {
      hoursFormat = `0${ hours}`;
    }
    return `${hoursFormat.substring(0, 2)}h${hoursFormat.substring(2, 4)}`;
  }

  /**
   * @description write a new orientation
   */
  const write = () => {
    OrientationService.write();
  };

  /**
   * return null value if we don't have value (SQL error)
   */
  function checkNullValue(val) {
    return val || null;
  }

  /**
   * Format date and time to post to server side with GMT+00:00
   * need date string
   * @returns {string}
   */
  function formatDateTime(dateValue = null) {
    // Get timezone
    const date = dateValue ? dayjs(dateValue).tz('GMT') : dayjs();

    // date yyyy-mm-dd
    const currrentDate = date.toISOString().split('T')[0];

    // to get 2 digit hh
    const hour = date.get('hour').toString().padStart(2, '0');

    // to get 2 digit mm
    const minute = date.get('minute').toString().padStart(2, '0');

    // to get 2 digit ss
    const seconde = date.get('second').toString().padStart(2, '0');

    // concat date and time and set timezone to GMT+00:00
    return `${currrentDate}T${hour}:${minute}:${seconde}+00:00`;
  }

   async function setOrientationRegistration(orientationPayload) {
    const data = await OrientationService.postOrientation(orientationPayload);
    const errorMessage = {
      status: 'error',
      message: 'Une erreur est survenue, veuillez sélectionner un autre créneau',
    };
    const successMessage = {
      status: 'success',
      // keep message empty because is handle in NotificationTab.component.vue with Notification.vue slot
      message: '',
    };
    let notificationContent = {};
    if (
      data
      && Object.keys(data).length
      && Object.getPrototypeOf(data) === Object.prototype
      && !data.error
    ) {
      notificationContent = successMessage;
    } else if (data?.error?.code === 'slot_full') {
      notificationContent = {
        status: 'error',
        message: 'Une erreur est survenue, veuillez sélectionner un autre créneau',
      };
    } else {
      notificationContent = errorMessage;
    }

    return {
      notification: notificationContent,
      currentData: data,
    };
  }

  return {
    write,
    hoursSlotFormatted,
    checkNullValue,
    formatDateTime,
    setOrientationRegistration,
  };
};
