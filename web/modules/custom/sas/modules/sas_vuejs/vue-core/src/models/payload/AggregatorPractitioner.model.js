export default class AggregatorPractitioner {
  constructor(ps) {
    this.nid = ps.nid ?? '';
    this.line = ps.line ?? '';
    this.zipcode = ps.zipcode ?? '';
    this.phone = ps.phone ?? [];
    this.latitude = ps.latitude ?? null;
    this.longitude = ps.longitude ?? null;
    this.siret = ps.siret ?? '';
    this.finess = ps.finess ?? '';
    this.rppsRang = ps.rppsRang ?? '';
    this.rpps = ps.rpps ?? '';
    this.adeli = ps.adeli ?? '';
  }
}
