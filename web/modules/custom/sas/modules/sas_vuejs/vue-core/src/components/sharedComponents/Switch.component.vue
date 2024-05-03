<template>
  <slot :case="currentCase">
    {{ currentCase?.value }}
  </slot>
</template>

<script>
import { computed } from 'vue';

/**
 * This component can be used to simplify the handling of multiple possible output
 * inside a uniform HTML structure depending on a state.
 *
 * To access the current props, the component that calls this one must put the following inside the `<Switch>` tag:
 * `<template #default="props">`
 * Note that `props` in the context of this snippet could be called anything
 *
 * You can also hide the structure you passed inside the parent template
 * if your key isn't found in the `cases` array you provided as props using `v-if="props.case"`
 *
 * In the example below, `errorCode` is defined in the setup of the parent component that uses this one
 *
 * @example
 * <Switch
 *   :current="errorCode"
 *   :cases="[
 *     {
 *       id: 'divide-by-zero',
 *       value: `You can't divide by zero`,
 *     },
 *     {
 *       id: 'must-be-an-integer',
 *       value: `You can't pass a floating number`,
 *     },
 *   ]"
 * >
 *   <template #default="props">
 *     <p v-if="props.case" class="error-message">
 *       {{ props.case.value }}
 *     </p>
 *   </template>
 * </Switch>
 */
export default {
  name: 'Switch',
  props: {
    cases: {
      type: Array,
      required: true,
      validator: (cases) => (cases.every((item) => (!!Object.getOwnPropertyDescriptor(item, 'id')))),
    },
    current: {
      type: null,
      required: true,
    },
  },
  setup(props) {
    const currentCase = computed(() => (props.cases.find((c) => (c.id === props.current))));

    return { currentCase };
  },
};
</script>
