<template>
  <SearchCardHeadline
    :showSasParticipation="showSasParticipation(cardData)"
    :showSasForfait="showSasForfait"
    :defaultPicto="cardData.defaultPicto"
    :isSOSMedecin="cardData.isSOSMedecin"
    :cardUrl="cardUrl"
    :cardTitle="cardData.cardTitle"
    :cardSubTitle="cardData.cardSubTitle"
    :sasParticipationLabel="cardData.sasParticipationLabel"
    :sasForfaitReuLabel="cardData.sasForfaitReuLabel"
  />

  <!--SOS Médecins PFG-->
  <template v-if="cardData.isSOSMedecin">
    <div
      v-for="(PFGname, idx) in cardData.tm_X3b_und_field_precision_type_eg"
      :key="`pfg-${idx}`"
      class="card-elm-item"
    >
      {{ PFGname }}
    </div>
  </template>

  <!--bloc activities && specialities-->
  <div v-if="specialities" class="search-card-specialities">
    <ul class="search-card-specialities-list resetul">
      <li
        v-for="(speciality, idx) in specialities"
        :key="idx"
        class="search-card-speciality"
      >
        {{ speciality }}
      </li>
    </ul>
  </div>

  <div v-if="showDistance">
    <span>{{ getDistance(cardData.dist) }}</span>
  </div>

  <!--CPTS name && tel-->
  <div
    v-if="showCptsInfos"
    class="search-card-cpts-info"
  >
    <div v-if="cptsInfos.label" class="search-card-cpts-name">
      Nom CPTS
      <span>{{ cptsInfos.label }}</span>
    </div>

    <div v-if="cptsInfos.phone" class="search-card-cpts-phone">
      Tel. CPTS
      <SafeLink :link="`tel:${cptsInfos.phone}`">
        {{ cptsInfos.phone }}
      </SafeLink>
    </div>
  </div>

  <!-- bloc conventionnement secteur -->
  <div v-if="cardData.tm_X3b_und_convention_type?.length" class="card-elm-item">
    <div class="list-specialities">
      <ul class="resetul">
        <li>
          {{ cardData.tm_X3b_und_convention_type[0] }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
  import { computed } from 'vue';

  import _isEmpty from 'lodash.isempty';
  import {
    useUserData,
    useSearchData,
    useGeolocationData,
  } from '@/stores';
  import { useSearchCard } from '@/composables';
  import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';

  import { epurate } from '@/helpers';

  import SearchCardHeadline from './SearchCardHeadline.component.vue';

  export default {
    components: {
      SafeLink,
      SearchCardHeadline,
    },
    props: {
      cardData: {
        type: Object,
        default: () => ({}),
      },
    },
    setup(props) {
      const userDataStore = useUserData();
      const searchDataStore = useSearchData();
      const geolocationStore = useGeolocationData();
      const { showSasParticipation } = useSearchCard();

      const currentUser = computed(() => (userDataStore.currentUser));
      const isBsForfaitReoChecked = computed(() => (searchDataStore.customFilters?.bs_sas_forfait_reo !== undefined));

      const showDistance = computed(() => (geolocationStore.geolocation?.type === geolocationStore.GEOLOCATION_TYPE.ADDRESS && props.cardData.dist));

      const showSasForfait = computed(() => (
        (isBsForfaitReoChecked.value && currentUser.value.isRegulateurOSNP)
        || (currentUser.value.isRegulateurIOA && !currentUser.value.isRegulateurOSNP)
      ));

      const cardUrl = computed(() => {
        if (props.cardData.ss_field_node_path_alias) {
          const base = props.cardData.ss_field_node_path_alias.replace('#q=', '');
          const query = `?sas_back=${encodeURIComponent(document.location.pathname + document.location.search)}`;

          const agregParams = [];
          if (props.cardData.isAggregator) {
            agregParams.push('&agreg=1');
            if (props.cardData.aggregatorId) {
              agregParams.push(`&location_id=${props.cardData.aggregatorId}`);
            }
          }

          return `${base}${query}${agregParams.join('')}`;
        }
          return '';
      });

      const showEditorSkills = computed(() => (
        props.cardData?.agregSpecialities?.length
        && (currentUser.value.isRegulateurOSNP || currentUser.value.isRegulateurIOA)
      ));

      const specialities = computed(() => (showEditorSkills.value
        ? [...props.cardData?.tm_X3b_und_field_specialite_name ?? [], ...props.cardData?.agregSpecialities ?? []]
        : props.cardData?.tm_X3b_und_field_specialite_name));

      /**
       * get distance between target and research
       * @param {number} value
       * @returns {string}
       */
      const getDistance = (value) => {
        if (!value) return '';
        return Math.ceil(value) > 1 ? `${Math.ceil(value)} km` : `${Math.ceil(value * 1000)} m`;
      };

      const showCptsInfos = computed(() => (
        showSasParticipation(props.cardData) && !_isEmpty(cptsInfos)
      ));

      const cptsInfos = computed(() => {
        const {
          ss_sas_cpts_phone: phone,
          ss_sas_cpts_label: label,
        } = props.cardData;

        return epurate({
          label,
          phone,
        });
      });

      return {
        showSasParticipation,
        showSasForfait,
        cardUrl,
        specialities,
        showDistance,
        showCptsInfos,
        getDistance,
        cptsInfos,
      };
    },
  };
</script>
