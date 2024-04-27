<template>
  <Teleport to="body">
    <div :id="modalId" class="wrapper-vuemodal-sas">
      <div id="backdrop" class="fade in modal-backdrop" @click="$emit('on-close-modal')" />
      <div class="ui-dialog ui-widget ui-widget-content vuemodal-sas" :class="modalClass">
        <div ref="firstFocusModal" class="firstfocusmodal" tabindex="0" />
        <div class="ui-dialog-titlebar">
          <span id="ui-id-1" class="ui-dialog-title">{{title}}</span>
          <button
            type="button"
            class="ui-dialog-titlebar-close"
            title="Close"
            @click="$emit('on-close-modal')"
          >
            <span class="ui-button-icon ui-icon ui-icon-closethick" />
            <span class="ui-button-icon-space" />
            Close
          </button>
        </div>
        <div class="ui-dialog-content">
          <slot />
        </div>
        <div ref="lastFocusModal" class="lastfocusmodal" tabindex="0" />
      </div>
    </div>
  </Teleport>
</template>

<script>
import { onMounted, onUnmounted, ref } from 'vue';

export default {
  props: {
    ariaLabelledby: {
      type: String,
      default: 'titlemodale',
    },
    title: {
      type: String,
      default: '',
    },
    modalClass: {
      type: String,
      default: '',
    },
    fallbackFocusElementSelector: {
      type: String,
      default: '',
    },
    modalId: {
      type: String,
      default: 'vuemodal-sas',
    },
  },
  emits: ['on-close-modal'],
  setup(props, { emit }) {
    const firstFocusModal = ref(null);
    const lastFocusModal = ref(null);
    const previousFocusedElement = ref(null);

    const keydownEvent = (e) => {
      if (e.code === 'Escape') {
        emit('on-close-modal');
      }

      if (e.code === 'Tab' && document.activeElement === lastFocusModal.value) {
        firstFocusModal.value.focus();
      }

      if (e.shiftKey && e.code === 'Tab' && document.activeElement === firstFocusModal.value) {
        lastFocusModal.value.focus();
      }
    };

    onMounted(() => {
      previousFocusedElement.value = document.activeElement;
      firstFocusModal.value.focus();
      document.body.addEventListener('keydown', keydownEvent);
    });

    onUnmounted(() => {
      document.body.removeEventListener('keydown', keydownEvent);

      if (previousFocusedElement.value) {
        previousFocusedElement.value.focus();
      }

      if (
        document.activeElement !== previousFocusedElement.value
        && props.fallbackFocusElementSelector
      ) { // The original element has disappeared from the DOM
        const fallbackFocusElement = document.querySelector(props.fallbackFocusElementSelector);
        if (fallbackFocusElement) {
          fallbackFocusElement.focus();
        }
      }
    });

    return {
      firstFocusModal,
      lastFocusModal,
    };
  },
};
</script>
