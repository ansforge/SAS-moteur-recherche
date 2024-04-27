<template>
  <SearchCardPolymorph
    :cardData="cardData"
    :mode="miniMap ? 'long' : 'compact'"
    :showOrientationModal="showOrientationModal"
    @open-orientation-modal="showOrientationModal = true"
    @close-orientation-modal="showOrientationModal = false"
  >
    <template #header>
      <div class="search-card-header-zone">
        <SearchCardHeader :cardData="cardData" />
        <SearchCardExtra :cardData="cardData" />
      </div>
    </template>

    <template #address>
      <div class="search-card-adress-zone">
        <!--adress-->
        <div class="search-card-doctor-address">
          <a
            v-if="!cardData.isSOSMedecin"
            href="#"
            :data-details="cardData.locs_field_geolocalisation_latlon"
            :aria-label="`Zoomer sur l\'adresse ${cardData.ss_field_address}`"
            @click.prevent="handleAdressClick(cardData.locs_field_geolocalisation_latlon)"
          >
            {{ cardData.finalAddress }}
          </a>
          <template v-else>
            {{ cardData.finalAddress }}
          </template>
        </div>

        <!--opening && closing hours-->
        <div
          v-if="cardData.scheduleData?.horaireTraite?.length"
          class="search-card-opening-time"
        >
          <TimeAccordion
            :cardId="cardData.its_nid"
            :horaires="cardData.sm_field_horaires"
            :tz="cardData.ss_sas_timezone"
            :formatData="cardData.scheduleData"
            :displayIcon="false"
          />
        </div>
      </div>
    </template>

    <template #additional v-if="showAdditionalInfo(cardData)">
      <div class="search-card-additional-info-zone">
        <AdditionalInformation
          :additionalInfo="cardData.ss_sas_additional_info"
          :cardId="cardData.its_nid"
          :triggerMode="'viewmore'"
        />
      </div>
    </template>

    <template #calendar>
      <div class="search-card-calendar-zone">
        <div class="search-card-schedule">
          <SnpCalendar
            :cardData="cardData"
            :currentIndex="cardIndex"
            :key="`card-calendar-${cardData.its_nid}`"
          />
        </div>
      </div>
    </template>

    <template #phoneNumber>
      <div class="search-card-phone">
        <SafeLink v-if="psPhoneNumber" :link="`tel:${psPhoneNumber}`">{{ psPhoneNumber }}</SafeLink>
      </div>
    </template>

    <template #surnumerary="slotSurnemaryProps" v-if="showSuperNumeraryBtn(cardData)">
      <div class="search-card-surnumerary-button">
        <button
          type="button"
          class="btn"
          @click="slotSurnemaryProps.emitButtonClick()"
        >
          {{ superNumeraryBtnLabel }}
        </button>
      </div>
    </template>

    <template #generalPractitioner v-if="cardData.isLrmSearchWithPreferredDoctor">
      <!--preferred Dr-->
      <div class="search-card-preferred-doctor">
        <p>MÉDECIN TRAITANT</p>
      </div>
    </template>
  </SearchCardPolymorph>
</template>

<script>
  import { ref, computed } from 'vue';

  import AdditionalInformation from '@/components/sharedComponents/AdditionalInformation.component.vue';
  import SnpCalendar from '@/components/searchComponents/listViewComponents/SnpCalendar.component.vue';
  import TimeAccordion from '@/components/sharedComponents/TimeAccordion.component.vue';
  import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';
  import { useSearchCard } from '@/composables';
  import { useSearchData } from '@/stores';
  import SearchCardExtra from './SearchCardExtra.component.vue';
  import SearchCardHeader from './SearchCardHeader.component.vue';
  import SearchCardPolymorph from './SearchCardPolymorph.component.vue';

  export default {
    components: {
      AdditionalInformation,
      SafeLink,
      SearchCardExtra,
      SearchCardHeader,
      SearchCardPolymorph,
      SnpCalendar,
      TimeAccordion,
    },
    props: {
      cardData: {
        type: Object,
        default: () => ({}),
      },
      cardIndex: {
        type: Number,
        default: 0,
      },
      miniMap: {
        type: Boolean,
        default: false,
      },
    },

    setup(props) {
      const searchDataStore = useSearchData();
      const {
        showAdditionalInfo,
        showSuperNumeraryBtn,
        showSasParticipation,
        superNumeraryBtnLabel,
      } = useSearchCard();

      const showOrientationModal = ref(false);

      /**
       * if surnuméraire & cpts phone, show cpts phone. Else if there are no cpts phone, show ps phone.
       */
      const psPhoneNumber = computed(() => {
        const isCPTS = props.cardData.its_sas_participation_via === 2;

        return (
          isCPTS
          && showSasParticipation(props.cardData)
          && !searchDataStore.isFiltered
        ) ? '' : props.cardData?.final_phone_number;
      });

      /**
       * get fly to animation for a given marker
       * @param {String} latlonString
       */
      function handleAdressClick(latlonString) {
        const seperatedLatLon = latlonString?.split(',');
        const evt = new CustomEvent('map::askFlyTo', {
          detail: {
            coords: {
              lat: seperatedLatLon?.[0] || null,
              lng: seperatedLatLon?.[1] || null,
            },
          },
        });
        window.dispatchEvent(evt);
      }

      return {
        psPhoneNumber,
        showAdditionalInfo,
        showSuperNumeraryBtn,
        handleAdressClick,
        superNumeraryBtnLabel,
        showOrientationModal,
      };
    },
  };
</script>
