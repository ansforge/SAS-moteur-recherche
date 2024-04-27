<template>
  <div>
    <SearchEngine source="header" />
  </div>
</template>

<script>
import SearchEngine from '@/components/chargementProgressifComponents/searchComponents/SearchEngine.component.vue';

export default {
  name: 'HeaderSearchBar',
  components: {
    SearchEngine,
  },
};
</script>
