import parsePhoneNumber from 'libphonenumber-js';

export default {
  /**
   * @param {string} [phoneNumber] - The phone number to format.
   * @returns {string|null} [formattedPhoneNumber] - A phone number with spaces every two digits or null if the input is invalid.
   * 08 numbers needs a special treatment inside this function because the lib format them like so: 0 8XX XXX XXX
   * Explanation about why 08 phone numbers have this format: https://www.economie.gouv.fr/particuliers/tarifs-numeros-08#
   */
  formatPhoneNumber(phoneNumber) {
    if (!phoneNumber) return null;

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
