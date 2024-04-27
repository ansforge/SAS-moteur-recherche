<template>
  <button :class="classes" type="button">
    <i v-if="icon" :class="iconClasses" />
    <slot />
  </button>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'WidgetButton',
  props: {
    theme: {
      type: String,
      default: 'primary',
      validator(value) {
        // The value must match one of these strings
        return ['primary', 'secondary', 'tertiary'].includes(value);
      },
    },
    icon: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    const classes = computed(() => {
      const classesTemp = [];
        switch (props.theme) {
          case 'primary':
            classesTemp.push('btn-highlight');
            break;
          case 'secondary':
            classesTemp.push('btn-highlight-outline');
            break;
          case 'tertiary':
            classesTemp.push('btn-home');
            break;
          default:
            classesTemp.push('btn-highlight');
            break;
        }

        if (props.icon) {
          classesTemp.push('with-icon');
        }

        return classesTemp;
    });

    const iconClasses = computed(() => `icon icon-${props.icon}`);

    return {
      classes,
      iconClasses,
    };
  },
};
</script>
