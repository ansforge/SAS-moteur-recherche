/* eslint-disable camelcase */

export default class SnpPopinConfigModel {
  constructor({
    group1 = { nom_snp: '', sous_titre: '', titre: '' },
    group2 = { a: '', date: '', de: '' },
    group3 = { consultation: '', rendez_vous: '' },
    group4 = {
      cabinet: '',
      domicile: '',
      initial_cabinet: '',
      initial_domicile: '',
      initial_teleconsultation: '',
      teleconsultation: '',
      titre_type_consultation: '',
    },
    group5 = { jour: '', titre_recurrence: '' },
  } = {
    group1: { nom_snp: '', sous_titre: '', titre: '' },
    group2: { a: '', date: '', de: '' },
    group3: { consultation: '', rendez_vous: '' },
    group4: {
      cabinet: '',
      domicile: '',
      initial_cabinet: '',
      initial_domicile: '',
      initial_teleconsultation: '',
      teleconsultation: '',
      titre_type_consultation: '',
    },
    group5: { jours: '', titre_recurrence: '' },
  }) {
    this.group1 = { nameSnp: group1.nom_snp, subtitle: group1.sous_titre, title: group1.titre };
    this.group2 = { to: group2.a, date: group2.date, from: group2.de };
    this.group3 = { consultation: group3.consultation, meet: group3.rendez_vous };
    this.group4 = {
      office: group4.cabinet,
      home: group4.domicile,
      initialOffice: group4.initial_cabinet,
      initialHome: group4.initial_domicile,
      initialTeleconsultation: group4.initial_teleconsultation,
      teleconsultation: group4.teleconsultation,
      titleTypeConsultation: group4.titre_type_consultation,
    };
    this.group5 = { jours: group5.jours, titleRecurrence: group5.titre_recurrence };
  }
}
