<template>
  <component
    :is="componentName"
    :cardData
    :mode="miniMap ? 'long' : 'compact'"
    :showOrientationModal
    @open-orientation-modal="showOrientationModal = true"
    @close-orientation-modal="showOrientationModal = false"
  >
    <template #header>
      <div class="search-card-header-zone">
        <SearchCardHeader :cardData />
        <SearchCardExtra :cardData />
      </div>
    </template>

    <template #address>
      <div class="search-card-address-zone">
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
            :cardData
            :currentIndex="cardIndex"
            :key="`card-calendar-${cardData.its_nid}`"
          />
        </div>
      </div>
    </template>

    <template #phoneNumber="phoneNumberProps">
      <div class="search-card-phone">
        <SafeLink v-if="phoneNumberProps.phoneNumber" :link="`tel:${phoneNumberProps.phoneNumber}`">{{ phoneNumberProps.phoneNumber }}</SafeLink>
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
  </component>
</template>

<script>
  import { ref, computed } from 'vue';

  import { useSearchCard } from '@/composables';
  import AdditionalInformation from '@/components/sharedComponents/AdditionalInformation.component.vue';
  import SnpCalendar from '@/components/searchComponents/listViewComponents/SnpCalendar.component.vue';
  import TimeAccordion from '@/components/sharedComponents/TimeAccordion.component.vue';
  import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';

  import SearchCardExtra from '@/components/chargementProgressifComponents/searchComponents/cardComponents/SearchCardExtra.component.vue';
  import SearchCardHeader from '@/components/chargementProgressifComponents/searchComponents/cardComponents/SearchCardHeader.component.vue';

  import SearchCardPolymorph from '@/components/chargementProgressifComponents/searchComponents/cardComponents/SearchCardPolymorph.component.vue';
  import SearchCardPolymorphCpts from '@/components/search/cards/SearchCardPolymorphCpts.component.vue';

  /**
   * @typedef {object} Props
   * @property {import('@/types').ICard} cardData
   * @property {number} cardIndex
   * @property {boolean} miniMap
   */

  /**
   * The reason this component is written like this is because
   * we need to adapt the layout for a card based on two distinct factors:
   *  - The type of the card - managed by the `<component :is>`)
   *  - The mode (either compact/long) - managed via the slots system inside each child component
   * This system is advantageous because each child can implement specialized treatment if necessary.
   * Although it may introduce some redundancies in the way Polymorph cards are written,
   * the benefits we get from it (mostly in term of flexibility) far outweigh the drawbacks
   */
  export default {
    components: {
      AdditionalInformation,
      SafeLink,
      SearchCardExtra,
      SearchCardHeader,
      SearchCardPolymorph,
      SearchCardPolymorphCpts,
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

    setup(/** @type {Props} */props) {
      const {
        showAdditionalInfo,
        showSuperNumeraryBtn,
        superNumeraryBtnLabel,
      } = useSearchCard();

      const showOrientationModal = ref(false);

      const componentName = computed(() => {
        switch (props.cardData.type) {
          case 'cpts': return SearchCardPolymorphCpts.name;
          default: return SearchCardPolymorph.name;
        }
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
        showAdditionalInfo,
        showSuperNumeraryBtn,
        handleAdressClick,
        superNumeraryBtnLabel,
        showOrientationModal,
        componentName,
      };
    },
  };
</script>
