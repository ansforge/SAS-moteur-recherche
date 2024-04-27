import { DICTIONARY } from '@/const';

import { useFilterDictionnary } from '@/stores';

export default class FiltersClass {
  constructor(filtersCategoriesData) {
    this.filters = [];

    // convert object to key's array
    const categoriesFilter = Object.keys(filtersCategoriesData);

    // add custom hour filters
    const hourSlices = [
      {
        value: 'next4Hours',
        label: 'Sous 0h et 4h',
      },
      {
        value: 'next4to8Hours',
        label: 'Sous 4h et 8h',
      },
      {
        value: 'next8to12Hours',
        label: 'Sous 8h et 12h',
      },
      {
        value: 'next12to24Hours',
        label: 'Sous 12h et 24h',
      },
      {
        value: 'next24to48Hours',
        label: 'Sous 24h et 48h',
      },
      {
        value: 'next48to72Hours',
        label: 'Sous 48h et 72h',
      },
    ];

    const customHoursFilters = this.#createCustomFilter('available_hours', 'Disponible sous', hourSlices);
    this.filters.push(customHoursFilters);

    categoriesFilter.forEach((keyCategory) => {
      const filterList = [];
      const titleCategory = DICTIONARY.FILTER.title_categories[keyCategory];

      // categories we won't display in the filters
      const categoriesForbidden = [
        'sm_field_custom_label_temporaire_label',
        'sm_field_custom_label_permanent_label',
      ];

      // if the category title exist and the category is not forbidden
      if (titleCategory && !categoriesForbidden.includes(keyCategory)) {
        const oneCategory = Object.keys(filtersCategoriesData[keyCategory]);
        // loop in the filters of the category and map the filter data
        oneCategory.forEach((id) => {
          filterList.push(this.#getFilterItemFromDictionary(keyCategory, id, filtersCategoriesData[keyCategory][id]));
        });

        if (filterList.length) {
          this.filters.push({
            name: titleCategory,
            key: keyCategory,
            label: titleCategory,
            nbr: filterList.length,
            position: (keyCategory === 'itm_establishment_types') ? 1 : 3,
            items: filterList,
            isVisible: false,
          });
        }
      }
    });

    this.filters.sort((filterA, filterB) => filterA.position - filterB.position);
  }

  /**
   * Create one item filter
   * @param nbrItems total of items for one filter
   * @param label the label of the filter
   * @param idItems the id of the filter
   * @param isChecked {boolean} true or false to know if the filter is applied
   * @param position {number} position of filter
   * @return {{label, nbrItems, idItems, checked, position}}
   */
  // eslint-disable-next-line class-methods-use-this
  #createFilterItem = (nbrItems, label, idItems, parentKey, isChecked = false, position = 0) => ({
    nbrItems,
    label,
    idItems,
    isChecked,
    position,
    parentKey,
    isVisible: false,
  });

  #createCustomFilter = (nameCat, titleCat, filtersCustom) => {
    const items = [];

    filtersCustom.forEach((item) => {
      items.push(this.#createFilterItem(1, item.label, item.value, nameCat, false, 0));
    });

    return {
      name: nameCat,
      key: nameCat,
      label: titleCat,
      position: 2,
      isVisible: false,
      items,
    };
  };

  /**
   * @description Create one item for one filter category
   * @param category {string} is the current category type of the filter
   * @param id {number|string} id of the filter
   * @param count {number} total doctors who are available
   */
  #getFilterItemFromDictionary = (category, id, count) => {
    const filterLabelsStore = useFilterDictionnary();
    return this.#createFilterItem(
      count,
      filterLabelsStore.filterTypeLabels[category] ? filterLabelsStore.filterTypeLabels[category][id] : id,
      id,
      category,
      false,
      1,
    );
  };
}
