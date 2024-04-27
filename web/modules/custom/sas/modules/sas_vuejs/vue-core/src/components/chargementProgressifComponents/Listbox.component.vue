<template>
  <div>
    <div
      v-if="header"
      class="autocomplete-title"
      :aria-label="header"
      :id="listId"
    >{{ header }}</div>
    <ul
      :id="`${listId}-listbox`"
      class="autocomplete-items"
      role="listbox"
      :aria-labelledby="listId"
    >
      <li
        v-for="item in items"
        :class="itemClass"
        :key="item"
        role="option"
      >
        <i v-if="iconClass" :class="iconClass" />
        <a
          href="#"
          @mousedown="clickedOnListItem(item)"
          v-html="$sanitize(item)"
        />
      </li>
    </ul>
  </div>
</template>

<script>
import { htmlStripper } from '@/helpers';

/**
 * WCAG: The `<input>` that is responsible of this component MUST have
 *       an `aria-controls` attribute equal to the `id` of the `<ul>`
 */
export default {
  name: 'Listbox',
  props: {
    header: {
      type: String,
      default: '',
    },
    listId: {
      type: String,
      default: '',
    },
    itemClass: {
      type: String,
      default: 'search-item',
    },
    iconClass: {
      type: String,
      default: 'icon-search',
    },
    items: {
      type: [Array, null],
      required: true,
    },
  },
  emits: ['clicked-on-list-item'],
  setup(props, { emit }) {
    function clickedOnListItem(item) {
      emit('clicked-on-list-item', htmlStripper(item));
    }

    return {
      clickedOnListItem,
    };
  },
};
</script>
