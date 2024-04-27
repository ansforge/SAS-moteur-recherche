import parsePhoneNumber from 'libphonenumber-js';

export default {
  /**
   * @param {string} phoneNumber
   * @returns A phone number with spaces every two digits
   * 08 numbers needs a special treatment inside this function because the lib format them like so: 0 8XX XXX XXX
   * Explanation about why 08 phone numbers have this format: https://www.economie.gouv.fr/particuliers/tarifs-numeros-08#
  */
  formatPhoneNumber(phoneNumber) {
    const parsedPhoneNumber = parsePhoneNumber(phoneNumber, 'FR');

    if (!parsedPhoneNumber?.isValid()) {
        return null;
    }

    const formattedPhoneNumber = parsedPhoneNumber.formatNational();

    if (formattedPhoneNumber[2] !== '8') {
      return formattedPhoneNumber;
    }

    // from 0 8XX XX XX XX
    // to   08 XX XX XX XX
    return `${formattedPhoneNumber.slice(0, 3).replace(' ', '')} ${formattedPhoneNumber.slice(3)}`;
  },
};
