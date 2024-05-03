<template>
  <li>
    <button
      type="button"
      v-bind="buttonAttribut"
      @click="goToTargetPage(buttonInfo.targetPage)"
    >
      <template v-if="buttonInfo.type === 'text'">
        <span
          v-if="
            buttonInfo.action === 'back-previous-page'
              || buttonInfo.action === 'go-to-next-page'
          "
          class="sr-only"
        >
          {{ buttonInfo.altText }}
        </span>
        {{ buttonInfo.content }}
      </template>
      <template v-else-if="buttonInfo.type === 'icon'">
        <template v-if="textForIcon">
          <span>
            <span>
              {{ textForIcon }}
            </span>
            <i aria-hidden="true" :class="buttonInfo.content" />
          </span>
        </template>
        <template v-else>
          <span class="sr-only">{{ buttonInfo.altText }}</span>
          <i aria-hidden="true" :class="buttonInfo.content" />
        </template>
      </template>
    </button>
  </li>
</template>

<script>
import { computed } from 'vue';
import { useScroll } from '@/composables';

export default {
  props: {
    buttonInfo: {
      type: Object,
      default: () => ({}),
    },
    currentPage: {
      type: Number,
      default: 1,
    },
    totalPages: {
      type: Number,
      default: 1,
    },
  },
  emits: ['update-current-page'],
  setup(props, { emit }) {
    const { scrollToTop } = useScroll();

    const textForIcon = computed(() => {
      if (
        props.buttonInfo.type === 'icon'
        && props.buttonInfo.action === 'back-previous-page'
      ) {
        return props.buttonInfo.altText;
      }
      if (
        props.buttonInfo.type === 'icon'
        && props.buttonInfo.action === 'go-to-next-page'
      ) {
        return props.buttonInfo.altText;
      }
      return '';
    });

    /**
     * Emit to parent the new current page
     * @param {number} target
     */
    function goToTargetPage(target) {
      emit('update-current-page', target);
      scrollToTop();
    }

    // construct all attributs for pagination button
    const buttonAttribut = computed(() => ({
      disabled: props.buttonInfo.isDisabled,
      'aria-label':
        props.buttonInfo.type === 'text' && props.buttonInfo.action === 'none'
          ? `${props.buttonInfo.altText} ${props.buttonInfo.content}`
          : null,
      'aria-current':
        props.buttonInfo.type === 'text' && props.buttonInfo.action === 'none'
          ? props.buttonInfo.altText
          : null,
    }));

    return {
      textForIcon,
      goToTargetPage,
      buttonAttribut,
    };
  },
};
</script>
