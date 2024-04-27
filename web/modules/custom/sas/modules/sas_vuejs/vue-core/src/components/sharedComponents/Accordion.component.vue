<template>
  <div class="accordion box" :class="{ disable }">
    <button
      class="btn-collapse btn-accordion"
      type="button"
      :aria-expanded="open"
      aria-controls="collapseDoctors"
      data-once="collapse"
      @click="open = !open"
      :disabled="disable"
    >
      <slot name="title" />
    </button>
    <div v-show="open" class="collapse">
      <div class="collapse-container">
        <slot name="content" />
      </div>
    </div>
  </div>
</template>
<script>
import { ref } from 'vue';

export default {
  name: 'Accrodion',
  props: {
    disable: {
      type: Boolean,
      default: false,
    },
    defaultState: {
      type: Boolean,
      default: false,
    },
  },

  setup(props) {
    const open = ref(props.defaultState);

    return {
      open,
    };
  },
};
</script>

<style lang="scss" scoped>
.btn-accordion {
  @media(max-width: 768px) {
    font-size: 0.875rem;
  }
}

.directory-hours-bloc-mounted .modal-body .btn-accordion {
  @media(max-width: 768px) {
    padding: 18px;
  }
}

.directory-hours-bloc-mounted .modal-body .disable .btn-accordion {
  @media(max-width: 768px) {
    text-transform: none;
  }
}

.accordion.disable {
  background: #E6E6E6;
  color: #595959
}

button[disabled] {
  &::before,
  &::after {
    display: none;
  }
}
</style>
