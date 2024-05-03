<template>
  <ul class="pagination-bloc">
    <PaginationButton
      v-for="(element, idx) in paginationButtonConfigFiltered"
      :class="element.class"
      :key="`pagination-button-${idx}`"
      :button-info="element"
      :current-page="currentPage"
      :total-pages="totalPages"
      @update-current-page="updateCurrentDisplayedLot"
    />
  </ul>
</template>
<script>
import { computed } from 'vue';

import PaginationButton from '@/components/chargementProgressifComponents/PaginationButton.component.vue';

export default {

  components: {
    PaginationButton,
  },
  props: {
    currentPage: {
      type: Number,
      default: 1,
    },
    previousPage: {
      type: Number,
      default: 0,
    },
    nextPage: {
      type: Number,
      default: 0,
    },
    totalPages: {
      type: Number,
      default: 0,
    },
  },
  emits: [
      'update-current-page',
  ],
  setup(props, { emit }) {
    const paginationButtonConfig = computed(() => [
      {
        action: 'back-first-page',
        class: 'p-first-page',
        type: 'icon',
        isDisabled: buttonIsDisabled('previous'),
        content: getButtonContent('back-first-page', 'icon'),
        targetPage: 1,
        altText: 'Retour à la première page',
      },
      {
        action: 'back-previous-page',
        class: 'p-previous-page',
        type: 'icon',
        isDisabled: buttonIsDisabled('previous'),
        content: getButtonContent('back-previous-page', 'icon'),
        targetPage: props.previousPage,
        altText: 'Page précédente',
      },
      {
        action: 'back-previous-page',
        class: 'p-item',
        type: 'text',
        isDisabled: buttonIsDisabled('previous'),
        content: getButtonContent('back-previous-page', 'text'),
        targetPage: props.previousPage,
        altText: 'page',
      },
      {
        action: 'none',
        class: 'p-item p-active',
        type: 'text',
        isDisabled: buttonIsDisabled('current'),
        content: getButtonContent('none', 'text'),
        altText: 'page',
      },
      {
        action: 'go-to-next-page',
        class: 'p-item',
        type: 'text',
        isDisabled: buttonIsDisabled('next'),
        content: getButtonContent('go-to-next-page', 'text'),
        targetPage: props.nextPage,
        altText: 'page',
      },
      {
        action: 'go-to-next-page',
        class: 'p-next-page',
        type: 'icon',
        isDisabled: buttonIsDisabled('next'),
        content: getButtonContent('go-to-next-page', 'icon'),
        targetPage: props.nextPage,
        altText: 'Page suivante',
      },
      {
        action: 'go-to-last-page',
        class: 'p-last-page',
        type: 'icon',
        isDisabled: buttonIsDisabled('next'),
        content: getButtonContent('go-to-last-page', 'icon'),
        targetPage: props.totalPages,
        altText: 'Aller à la dernière page',
      },
    ]);

    const paginationButtonConfigFiltered = computed(() => paginationButtonConfig.value.filter((x) => !!x.content));

    function updateCurrentDisplayedLot(newVal) {
      emit('update-current-page', newVal);
    }

    /**
     * handle disable button
     * @param {string} context
     */
    function buttonIsDisabled(context = '') {
      return context === 'current'
        || (context === 'previous' && props.currentPage === 1)
        || (context === 'next' && props.currentPage === props.totalPages);
    }

    /**
     * get button text or icon class
     * @param {string} action
     * @param {string} type
     */
    function getButtonContent(action, type) {
      if (type === 'text') {
        switch (action) {
          case 'back-previous-page':
            return props.previousPage >= 1 ? props.previousPage : '';
          case 'none':
            return props.currentPage;
          case 'go-to-next-page':
            return props.nextPage <= props.totalPages ? props.nextPage : '';
          default:
            return '';
        }
      } else {
        switch (action) {
          // return name of class in tag <i>
          case 'back-first-page':
            return 'sas-icon sas-icon-first';
          case 'back-previous-page':
            return 'icon icon-left';
          case 'go-to-next-page':
            return 'icon icon-right';
          case 'go-to-last-page':
            return 'sas-icon sas-icon-last';
          default:
            return '';
        }
      }
    }

    return {
      paginationButtonConfigFiltered,
      updateCurrentDisplayedLot,
    };
  },
};
</script>
