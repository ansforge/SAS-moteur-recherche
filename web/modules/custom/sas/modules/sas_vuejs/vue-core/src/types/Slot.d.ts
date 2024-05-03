export interface Slot {
    /** ISO-8601 */
    date: string;

    /** 1 - 7 (monday to sunday) */
    day: number;

    end: HourlySchedule;

    endDate: FullDate;
    endHours: string;

    getRealDate: (firstDateOfWeek: any) => any;
    getSlotClass: (user: any, isFromSchedulePage: any) => any;

    horaire_type: any;

    id: number;

    isAggregator: boolean;
    isSNP: boolean;
    isToday: boolean;

    max_patients: number;

    /** @type {'C' | 'T' | 'D'} */
    modalite: string[];

    modalities: string[];

    modalityTypes: {
        isConsultationEnCabinetExist: boolean;
    };

    orientation_count: number;

    /** ISO-8601 */
    real_date: string;

    schedule: {
        id: number;
        timezone: string;
    }

    slotGuid: number;

    slot_reservation_link: string;

    start: HourlySchedule;

    startDate: FullDate;

    startHours: string;

    /** Timespan displayed on screen */
    time: string;

    timezone: string;

    timezoneOffset: string;

    type: string;

}

interface FullDate {
    /** Day of the month (1-31) */
    $D: number;

    /** Hour of the day (0-23) */
    $H: number;

    /** Locale string */
    $L: string;

    /** Month of the year (0-11, January is 0) */
    $M: number;

    /** Day of the week (0-6, Sunday is 0) */
    $W: number;

    /** Full Date string */
    $d: string;

    /** Minutes of the hour (0-59) */
    $m: number;

    /** Milliseconds of the second (0-999) */
    $ms: number;

    /** Timezone offset in minutes */
    $offset: number;

    /** Seconds of the minute (0-59) */
    $s: number;

    $u: boolean;

    $x: {
        /** Timezone string */
        $timezone: string;
    };

    /** Year */
    $y: number;
}

interface HourlySchedule {
    hours: number;
    minutes: number;
}