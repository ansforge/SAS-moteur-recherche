import { nextTick } from 'vue';

export default () => {
  const scrollToTop = () => {
    nextTick(() => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      });
    });
  };

  /**
   * scroll to given element, if no element scroll to top
   * @param {HTMLElement} elem
   */
  const scrollToElement = (elem) => {
    const topOffset = elem?.offsetTop || 0;

    nextTick(() => {
      window.scrollTo({
        top: topOffset,
        behavior: 'smooth',
      });
    });
  };

  return {
    scrollToTop,
    scrollToElement,
  };
};
