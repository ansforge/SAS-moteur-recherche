import { hashGenerator } from '@/helpers';

export default class SolrPageDataModel {
  constructor() {
    this.page = 1;
    this.qty = 100;
    this.rand_id = hashGenerator();
    this.location = {};
  }

  setPaginationQty(qty) {
    this.qty = qty;
  }

  setPaginationPage(page) {
    this.page = page;
  }

  setPaginationHash() {
    this.rand_id = hashGenerator();
  }

  setPaginationLocation(location) {
    this.location = location;
  }

  setPaginationData(pagination) {
    this.setPaginationQty(pagination.qty || 100);
    this.setPaginationPage(pagination.page || 1);
    this.setPaginationHash();
    this.setPaginationLocation(pagination.location || {});
  }
}
