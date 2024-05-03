import {
  ref, computed,
} from 'vue';

/**
 * This composable is useful if you need to have multiple Pagination component working with the same underlying data.
 * You instantiate this composable inside your parent and you pass the object returned by this composable to all your children Pagination component
 *
 * @warning You must NOT mutate the refs returned by this composable. That's what the actions are for as they do validation too
 * @param {object} _
 * @param {import('vue').Ref<number> | import('vue').ComputedRef<number>} _.numberOfLots - It must be a ref as the mutation of this variable isn't managed inside this composable
 */
// eslint-disable-next-line import/prefer-default-export
export function usePagination({
  numberOfLots,
}) {
  if (!numberOfLots) {
    throw new Error('numberOfLots parameter is required to use this composable');
  }

  const self = {};

  const currentLotNumberInternal = ref(1);

  self.currentLotNumber = computed(() => (currentLotNumberInternal.value));

  self.numberOfLots = numberOfLots;

  /**
   * Emit the new current lot number
   * @param {object} _
   * @param {string} _.emit - The event to send when the current lot change
   * @param {string} _.eventName - The event to send when the current lot change
   * @param {number} _.targetLot
   */
  const goToTargetLot = ({ emit, eventName, targetLot }) => {
    if (targetLot <= 0 || targetLot === currentLotNumberInternal.value || targetLot > numberOfLots.value) return;

    currentLotNumberInternal.value = targetLot;

    if (emit) {
      emit(eventName, targetLot);
    }
  };

  self.actions = {
    goBackToFirstLot: (params) => { goToTargetLot({ targetLot: 1, ...params }); },
    goBackToPreviousLot: (params) => { goToTargetLot({ targetLot: currentLotNumberInternal.value - 1, ...params }); },
    goToNextLot: (params) => { goToTargetLot({ targetLot: currentLotNumberInternal.value + 1, ...params }); },
    goToLastLot: (params) => { goToTargetLot({ targetLot: numberOfLots.value, ...params }); },
    goToLotOfNumber: (params) => {
      if (typeof params === 'number') {
        goToTargetLot({ targetLot: params });
      } else {
        goToTargetLot(params);
      }
    },
  };

  return self;
}
