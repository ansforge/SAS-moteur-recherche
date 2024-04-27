<template>
  <div v-for="(addressData, idx) in addresses" :key="`addressData-${idx}`">
    <input
      type="checkbox"
      :id="`edit-cpts-locations-${addressData.rpps_rang}`"
      class="form-checkbox"
      :name="`edit-cpts-locations-${addressData.rpps_rang}`"
      v-model="cptsLocationCheckbox"
      :value="addressData.rpps_rang"
      @change="handleAddressValue"
    />
    <label :for="`edit-cpts-locations-${addressData.rpps_rang}`"><strong>Adresse {{ idx + 1 }} : </strong>{{ addressData.address }}</label
    >
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import isEqual from 'lodash.isequal';

export default {
  emits: ['get-addresses-val'],
  props: {
    addresses: {
      type: Array,
      default: () => ([]),
    },
    checkedAddresses: {
      type: Array,
      default: () => ([]),
    },
  },
  setup(props, { emit }) {
    const cptsLocationCheckbox = ref([]);

    function handleAddressValue() {
      emit('get-addresses-val', {
        rppsRang: cptsLocationCheckbox.value,
      });
    }

    watch(
() => props.checkedAddresses,
    (newCheckedAddresses, oldCheckedAddresses) => {
      // check checkbox with data from API
      if (!isEqual(newCheckedAddresses, oldCheckedAddresses)) {
        cptsLocationCheckbox.value = [...new Set(newCheckedAddresses)];
      }
    },
);

    return {
      cptsLocationCheckbox,
      handleAddressValue,
    };
  },
};
</script>
