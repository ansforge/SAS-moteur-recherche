<template>
  <div class="formation-article-content">
    <div class="formation-content">
      <p class="formation-rubrique">{{ themeTitle }}</p>
      <h1>{{ currentTitle }}</h1>

      <component
        v-for="(item, idx) in currentPageItems"
        :is="item.component"
        v-bind="item"
        :key="`formation-content-${idx}`"
      />
    </div>

    <FormationPagination
      :hasNext="hasNext"
      :hasPrevious="hasPrevious"
      @go-to-previous="goToPrevious"
      @go-to-next="goToNext"
    />
  </div>
</template>

<script>
import { computed } from 'vue';
import { FormationContentModel } from '@/models';

import TitleBloc from '@/components/formationComponents/FormationTitleBloc.component.vue';
import TextBloc from '@/components/formationComponents/FormationTextBloc.component.vue';
import ImgVidBloc from '@/components/formationComponents/FormationImgVidBloc.component.vue';
import InformationBloc from '@/components/formationComponents/FormationInformationBloc.component.vue';
import FileBloc from '@/components/formationComponents/FormationFileBloc.component.vue';
import FormationPagination from '@/components/formationComponents/FormationPagination.component.vue';

export default {
  props: {
    currentPageData: {
      type: Object,
      default: () => ({}),
    },
    themeTitle: {
      type: String,
      default: '',
    },
    currentAvailablePages: {
      type: Array,
      default: () => ([]),
    },
    currentMenuData: {
      type: Object,
      default: () => ({}),
    },
  },
  components: {
    TitleBloc,
    TextBloc,
    ImgVidBloc,
    InformationBloc,
    FileBloc,
    FormationPagination,
  },
  setup(props) {
    const currentTitle = computed(() => props.currentPageData.title);
    const currentPageItems = computed(() => (props.currentPageData.paragraphs
        ? props.currentPageData.paragraphs.map((item) => new FormationContentModel(item)) : []));

    const hasNext = computed(() => {
      const currentPageIndex = props.currentAvailablePages.findIndex((x) => x.key === props.currentMenuData.currentPage);
      return !!props.currentAvailablePages[currentPageIndex + 1];
    });

    const hasPrevious = computed(() => {
      const currentPageIndex = props.currentAvailablePages.findIndex((x) => x.key === props.currentMenuData.currentPage);
      return !!props.currentAvailablePages[currentPageIndex - 1];
    });

    function goToNext() {
      const currentPageIndex = props.currentAvailablePages.findIndex((x) => x.key === props.currentMenuData.currentPage);

      if (hasNext.value) {
        window.location.href = props.currentAvailablePages[currentPageIndex + 1]?.relative;
      }
    }

    function goToPrevious() {
      const currentPageIndex = props.currentAvailablePages.findIndex((x) => x.key === props.currentMenuData.currentPage);

      if (hasPrevious.value) {
        window.location.href = props.currentAvailablePages[currentPageIndex - 1]?.relative;
      }
    }

    return {
      currentTitle,
      currentPageItems,
      hasNext,
      hasPrevious,
      goToNext,
      goToPrevious,
    };
  },
};
</script>
