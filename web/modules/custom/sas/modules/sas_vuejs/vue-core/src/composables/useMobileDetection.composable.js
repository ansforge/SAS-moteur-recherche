import {
 onMounted, onBeforeUnmount, provide, ref,
} from 'vue';

export default () => {
  const isLessThanDesktop = ref(window.innerWidth < 1200);
  const isLessThanLargeDesktop = ref(window.innerWidth < 1280);
  const isDesktop = ref(window.innerWidth >= 1200);
  const isLargeDesktop = ref(window.innerWidth >= 1280);
  const isScreenMdMin = ref(window.innerWidth < 992);

  function onResize() {
    isLessThanDesktop.value = window.innerWidth < 1200;
    isLessThanLargeDesktop.value = window.innerWidth < 1280;
    isDesktop.value = window.innerWidth >= 1200;
    isLargeDesktop.value = window.innerWidth >= 1280;
    isScreenMdMin.value = window.innerWidth < 992;
  }

  onMounted(() => {
    window.addEventListener('resize', onResize);
  });

  provide('isLessThanDesktop', isLessThanDesktop);
  provide('isLessThanLargeDesktop', isLessThanLargeDesktop);
  provide('isDesktop', isDesktop);
  provide('isLargeDesktop', isLargeDesktop);
  provide('isScreenMdMin', isScreenMdMin);

  onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize);
  });

  return {
    isLessThanDesktop,
    isLessThanLargeDesktop,
    isDesktop,
    isLargeDesktop,
    isScreenMdMin,
  };
};
