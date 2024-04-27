<template>
  <div class="summary-grid">
    <SafeLink class="summary-name" :link="href">{{ name }}</SafeLink>
    <div class="summary-infos">
      <template v-for="(info, index) in infos">
        <template v-if="info.label">
          <span class="summary-info-label" :key="index">{{ info.label }}</span>
          <span :class="{ 'summary-info-phone': !!info.phone }" :key="index + 1">
            <SafeLink v-if="info.phone" :link="`tel:${info.phone}`">
              {{ info.phone }}
            </SafeLink>
          </span>
        </template>
      </template>
    </div>
  </div>
</template>

<script>
import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';

/**
 * This component is used inside the orientation modals.
 * It lets the end user see at a quick glance the relevant informations of the health offer associated with the selected slot.
 *
 * The `span` of the phone is always shown even if it doesn't exist to force the grid layout
 */
export default {
  name: 'HealthOfferSummary',
  components: {
    SafeLink,
  },
  props: {
    name: {
      type: String,
      required: true,
    },
    href: {
      type: String,
      default: '',
    },
    // Array of object of the form {label: String, phone: String}
    infos: {
      type: Array,
      required: true,
    },
  },
};
</script>
