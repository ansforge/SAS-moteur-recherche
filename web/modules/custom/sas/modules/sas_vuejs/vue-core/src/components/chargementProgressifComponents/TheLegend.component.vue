<template>
  <div class="legend-table">
    <!-- Today -->
    <div class="tag">
      <span class="chip chip-blue" />
      {{ legendSettings.today }}
    </div>

    <!-- Next days -->
    <div class="tag">
      <span class="chip chip-green" />
      {{ legendSettings.nextDays }}
    </div>

    <!-- Modalities -->
    <div class="legend-modalities">
      <div
        v-for="(modality, idx) in legendSettings.modalities"
        :key="`legend-modality-${idx}`"
        class="legend-modality"
      >
        <span class="tag-letter">{{ modality.initial }}</span><span class="modality-snp-label">{{ modality.text }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    settings: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    const legendSettings = {
      today: "AUJOURD'HUI",
      nextDays: 'PROCHAINS JOURS',
      modalities: [
        {
          initial: 'C',
          text: 'Consultation en cabinet',
        },
        {
          initial: 'T',
          text: 'Téléconsultation',
        },
        {
          initial: 'D',
          text: 'Visite à domicile',
        },
      ],
      ...props.settings,
    };

    return {
      legendSettings,
    };
  },
};
</script>
