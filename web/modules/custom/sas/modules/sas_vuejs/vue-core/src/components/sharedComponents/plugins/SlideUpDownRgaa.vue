<template>
  <div
    :id="accordionId"
    :style='style'
    :aria-labelledby="ariaLabelledby"
    ref="container"
    @transitionend="onTransitionEnd"
  >
    <slot />
  </div>
</template>

<script>

import {
  ref,
  computed,
  watch,
  nextTick,
  onActivated,
  onMounted,
} from 'vue';

export default {
  name: 'SlideUpDownRgaa',
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    duration: {
      type: Number,
      default: 500,
    },
    tag: {
      type: String,
      default: 'div',
    },
    useHidden: {
      type: Boolean,
      default: true,
    },
    ariaLabelledby: {
      type: String,
      default: '',
    },
    accordionId: {
      type: String,
      default: '',
    },
    initialHeight: {
      type: String,
      default: '0',
    },
  },
  emits: [
    'open-start',
    'close-start',
    'open-end',
    'close-end',
  ],
  setup(props, { emit }) {
    const style = ref({});
    const initial = ref(false);
    const hidden = ref(false);
    const container = ref(null);
    const tempScrollHeight = ref(0);

    const attrs = computed(() => {
      const elemAttrs = {
        'aria-hidden': !props.active,
      };

      if (props.useHidden) {
        elemAttrs.hidden = hidden.value;
      }

      return elemAttrs;
    });

    watch(() => [props.active, props.initialHeight], () => { layout(); });

    function layout() {
      if (props.active) {
        hidden.value = false;
        emit('open-start');

        if (initial.value) {
          setHeight(`${props.initialHeight}px`, () => `${container?.value?.scrollHeight}px`);
        }
      } else {
        emit('close-start');
        setHeight(`${container?.value?.scrollHeight}px`, () => `${props.initialHeight}px`);
      }
    }

    function asap(callback) {
      if (!initial.value) {
        callback();
      } else {
        nextTick(callback);
      }
    }

    function setHeight(temp, afterRelayout) {
      style.value = { height: temp };

      asap(() => {
        // force relayout so the animation will run
        tempScrollHeight.value = container?.value?.scrollHeight;

        style.value = {
          height: afterRelayout(),
          overflow: props.initialHeight === '0' ? 'hidden' : '',
          'transition-property': 'height',
          'transition-duration': `${props.duration}ms`,
          'transition-timing-function': `${props.initialHeight === '0' ? '' : 'ease-out'}`,
        };
      });
    }

    function onTransitionEnd(event) {
      // Don't do anything if the transition doesn't belong to the container
      if (event.target !== container.value) return;

      if (props.active) {
        style.value = {};
        emit('open-end');
      } else {
        style.value = {
          height: `${props.initialHeight}`,
          overflow: props.initialHeight === '0' ? 'hidden' : '',
        };
        hidden.value = true;
        emit('close-end');
      }
    }

    onActivated(() => {
      hidden.value = !props.active;
    });

    onMounted(() => {
      layout();
      initial.value = true;
    });

    return {
      style,
      initial,
      hidden,
      container,
      attrs,
      layout,
      asap,
      setHeight,
      onTransitionEnd,
    };
  },
};
</script>
