export default class FormCreateDispoSNP {
  constructor({
    date = null,
    from = '00h00',
    to = '00h00',
    typeOfAppointment = null, // null || 'single' || 'multiple
    patientNb = null,
    modalities = [],
    repeat = [],
    scope = '', // recurring || dated
  } = {
    date: null,
    from: '00h00',
    to: '00h00',
    typeOfAppointment: null, // null || 'single' || 'multiple
    patientNb: null,
    modalities: [],
    repeat: [],
    scope: '', // recurring || dated
  }) {
    this.date = date;
    this.from = from;
    this.to = to;
    this.typeOfAppointment = typeOfAppointment;
    this.patientNb = patientNb;
    this.modalities = modalities;
    this.repeat = repeat;
    this.scope = scope;
  }
}
