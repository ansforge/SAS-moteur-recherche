import {
  ref, computed, watch, nextTick, onMounted, onUnmounted,
} from 'vue';
import { ComboboxAccessibility } from '@/models';

/**
 * This is the callback function that is called to short circuit the fetching if needed.
 * Since the self object get binded to it, you should use `this` in the body of your function
 * to manipulate the inner state returned by this composable
 * @callback shortCircuit
 * @returns {boolean} true if the getResultList should stop, false otherwise
 */

/**
 * This is the callback function that is called to fetch new results
 * @callback cancelableFetch
 * @param {string} input - The string to search from
 * @param {AbortSignal} abortSignal - The cancel token that can be used to abort the fetch call
 * @returns {Promise<string[]>} The new results
 */

/**
 * To use this composable inside a component, it (the component) MUST:
 *   - have an HTML tag with a ref attribute equal to "comboboxNode"
 *   - have another HTML tag with a ref attribute equal to "listboxWrapperNode"
 *   - return the object returned by this composable inside its setup
 *
 * @param {Object} _
 * @param {*} _.emit - Vue's emit
 * @param {cancelableFetch} _.inputHandler - The function that get called when the input change
 * @param {string} _.listItemSelector - The selector used when querying every deep children of the listboxWrapperNode (default = 'li')
 * @param {Function} _.displayResultList - You should use `this` in the body of your function to manipulate the inner state returned by this composable
 * @param {shortCircuit} _.beforeGetResultList - The function called just before fetching for results (can serve to short circuit it)
 */
// eslint-disable-next-line import/prefer-default-export
export function useCombobox({
  emit,
  inputHandler,
  listItemSelector = 'li',
  displayResultList,
  beforeGetResultList = () => (false),
}) {
  const self = {};

  /** @type {import('vue').Ref<HTMLInputElement>} */
  self.comboboxNode = ref(null);

  /** @type {import('vue').Ref<HTMLElement>} */
  self.listboxWrapperNode = ref(null);

  self.comboboxA11y = new ComboboxAccessibility();

  self.input = ref('');
  self.result = ref(null);

  self.hasNoResult = computed(() => (
    !self.result.value || self.result.value.length === 0
  ));

  self.listIsVisible = ref(false);

  self.displayList = displayResultList
    ? displayResultList.bind(self)
    : (() => {
    self.listIsVisible.value = true;
  });

  self.hideList = () => {
    self.listIsVisible.value = false;
  };

  self.confirmInput = () => {
    self.hideList();
    if (!self.comboboxA11y.keyFlag) {
      emit('confirmed-input');
    }
  };

  self.clearInput = () => {
    self.input.value = '';
  };

  self.selectListItem = (item) => {
    self.input.value = item;
  };

  /** @type { AbortController } */
  let abortController;
  const beforeGetResultListBinded = beforeGetResultList.bind(self);

  async function getResultList() {
    if (await beforeGetResultListBinded()) return;

    if (abortController) {
      abortController.abort();
    }
    abortController = new AbortController();

    self.result.value = await inputHandler(self.input.value, abortController.signal);
    self.displayList();
  }

  const resultTimeout = ref(null);
  self.getResultListDebounce = (delay = 0) => {
    clearTimeout(resultTimeout.value);
    resultTimeout.value = setTimeout(async () => {
      await getResultList();
    }, delay);
  };

  self.forceReload = () => {
    const listItems = self.listboxWrapperNode.value?.querySelectorAll(listItemSelector);
    self.comboboxA11y.update(listItems);
  };

  watch([self.listIsVisible, self.result], ([newListIsVisible]) => {
    self.comboboxA11y.reset();
    if (newListIsVisible) {
      nextTick(() => {
        self.forceReload();
      });
    }
  });

  onMounted(() => {
    nextTick(() => {
      self.comboboxA11y.init({ comboboxNode: self.comboboxNode.value, inputText: self.input });
    });
  });

  onUnmounted(() => {
    self.comboboxA11y.dispose();
  });

  return self;
}
