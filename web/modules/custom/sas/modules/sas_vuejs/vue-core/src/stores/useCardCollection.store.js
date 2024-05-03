import { defineStore } from 'pinia';
import { ref, shallowReactive } from 'vue';

/* eslint-disable import/prefer-default-export */
export const useCardCollection = defineStore('cardCollection', () => {
  /**
   * This is the sanitized card collection.
   * Each card here must contain every information needed by the component to works
   * @type {Map<string, import("@/types/Card").ICard[]>}
   */
  const collections = shallowReactive(new Map());

  /**
   * It creates the collection if it didn't exist before
   * @param {string} id
   * @param {object} _
   * @param {boolean} _.shallowReactivity - If true, the reactivity will only happen on addition/deletion of elements
   * @returns {import("vue").Ref<Map<string, import("@/types/Card").ICard>>} The reference can be shallow depending on the parameter passed
   */
  const getCollection = (id, { shallowReactivity = true } = {}) => {
    if (!collections.has(id)) {
      collections.set(id, new Map());
    }

    const rawCollection = shallowReactivity
      ? shallowReactive(collections.get(id))
      : collections.get(id);

    return ref(rawCollection);
  };

  /**
   * @param {string} id
   */
  const deleteCollection = (id) => {
    collections.delete(id);
  };

  return {
    getCollection,
    deleteCollection,

    // For debug purposes
    collections,
  };
});
