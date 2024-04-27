/**
 * directive to handle outside clicks from the element
 */
export default {
  beforeMount(el, binding) {
    // check if dragging to do not trigger the event if dragging in mobile
    const tempEL = el;
    tempEL.eventSetDrag = () => {
      tempEL.setAttribute('data-dragging', 'yes');
    };
    tempEL.eventClearDrag = () => {
      tempEL.removeAttribute('data-dragging');
    };
    tempEL.eventOnClick = (event) => {
      const dragging = tempEL.getAttribute('data-dragging');
      // Check that the click was outside the tempEL and its children, and wasn't a drag
      if (!document.elementsFromPoint(event.clientX, event.clientY).includes(tempEL) && !dragging) {
        // call method provided in attribute value
        binding.value(event);
      }
    };
    document.addEventListener('touchstart', tempEL.eventClearDrag);
    document.addEventListener('touchmove', tempEL.eventSetDrag);
    document.addEventListener('click', tempEL.eventOnClick);
    document.addEventListener('touchend', tempEL.eventOnClick);
  },
  unmounted(el) {
    const tempEL = el;
    document.removeEventListener('touchstart', tempEL.eventClearDrag);
    document.removeEventListener('touchmove', tempEL.eventSetDrag);
    document.removeEventListener('click', tempEL.eventOnClick);
    document.removeEventListener('touchend', tempEL.eventOnClick);
    el.removeAttribute('data-dragging');
  },
};
