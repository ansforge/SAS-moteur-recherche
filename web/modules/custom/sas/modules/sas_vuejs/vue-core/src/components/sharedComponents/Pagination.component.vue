<template>
  <ul class="pagination-bloc">
    <li
      v-for="(button, index) in buttons"
      :key="index"
      :class="button.placeholderClass"
      v-html="button.content"
      @click="button.action"
    />
  </ul>
</template>
<script>
import { watch, ref } from 'vue';

/**
 * This component construct the list of buttons related to the pagination based on its props and nothing else
 * The whole logic must be managed outside this component.
 */
export default {
  props: {
    adapter: {
      type: Function,
      required: true,
    },
    actions: {
      type: Object,
      default: () => {},
    },
    currentLotNumber: {
      type: Number,
      default: 1,
    },
    numberOfLots: {
      type: Number,
      required: true,
    },
    eventName: {
      type: String,
      default: 'go-to-lot',
    },
  },
  emits: ['go-to-lot'],
  setup(props, { emit }) {
    const buttons = ref([]);

    watch([() => props.currentLotNumber, () => props.numberOfLots], ([newCurrentLotNumber, newNumberOfLots]) => {
      buttons.value = props.adapter.buildButtons({
        currentLotNumber: newCurrentLotNumber,
        totalNumberOfLots: newNumberOfLots,
        actions: props.actions,
        emit,
        eventName: props.eventName,
      });
    }, { immediate: true });

    return {
      buttons,
    };
  },
};
</script>
