<template>
  <div class="search-card-title">
    <!--logo participation au SAS-->
    <i
      v-if="showSasParticipation"
      :title="sasParticipationLabel"
      class="sas-icon sas-icon-cnam"
      aria-hidden="true"
    >
      <span class="sr-only">{{ sasParticipationLabel }}</span>
    </i>

    <!--logo forfait reu-->
    <i
      v-if="showSasForfait"
      :title="sasForfaitReuLabel"
      class="sas-icon sas-icon-emergency"
      aria-hidden="true"
    >
      <span class="sr-only">{{ sasForfaitReuLabel }}</span>
    </i>

    <!--logo type card-->
    <i
      v-if="!showSasParticipation && !showSasForfait"
      class="icon"
      :class="defaultPicto.icon"
      aria-hidden="true"
    >
      <span class="sr-only">{{ defaultPicto.iconText }}</span>
    </i>

    <!--card title-->
    <div class="search-card-title-detail">
      <h2>
        <!--title link if not sos medecin-->
        <SafeLink
          v-if="!isSOSMedecin"
          :link="cardUrl"
          data-track="sas-clic-contenu-consultation"
        >
          {{ cardTitle }}
        </SafeLink>

        <!--title if sos medecin-->
        <template v-else>{{ cardTitle }}</template>
      </h2>

      <p v-if="cardSubTitle.length" class="b-position">{{ cardSubTitle }}</p>
    </div>
  </div>
</template>

<script>
import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';

export default {
  components: {
    SafeLink,
  },
  props: {
    showSasParticipation: {
      type: Boolean,
      default: false,
    },
    showSasForfait: {
      type: Boolean,
      default: false,
    },
    defaultPicto: {
      type: Object,
      default: () => ({}),
    },
    isSOSMedecin: {
      type: Boolean,
      default: false,
    },
    cardUrl: {
      type: String,
      default: '',
    },
    cardTitle: {
      type: String,
      default: '',
    },
    cardSubTitle: {
      type: String,
      default: '',
    },
    sasParticipationLabel: {
      type: String,
      default: '',
    },
    sasForfaitReuLabel: {
      type: String,
      default: '',
    },
  },
};
</script>
