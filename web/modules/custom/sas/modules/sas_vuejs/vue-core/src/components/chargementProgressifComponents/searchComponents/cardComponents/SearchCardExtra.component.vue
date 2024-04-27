<template>
  <!--HealthService-->
  <div v-if="showRendezVousLink" class="card-elm-item">
    <SafeLink
      class="btn-highlight"
      :link="cardData.ss_field_site_internet"
      :aria-label="`${cardData.ss_field_site_internet_title}(nouvelle fenêtre)`">
      Prendre rendez-vous en ligne
    </SafeLink>
  </div>

  <!-- HealthInstit :- service etb case all service-->
  <div v-if="showEtbCaseAllService" class="card-elm-item card-function">
    <template v-for="(value, name, index) in cardData.mpecs" :key="`mpecs-${index}`">
      <template v-if="name === 'Voir tous les services' && (!cardData.labels || cardData.labels.length === 0)">
        <SafeLink :link="value.etb_lien" :title="value.os_title">
          {{ value.os_title }}
        </SafeLink>
      </template>

      <template
        v-if="name !== 'Voir tous les services'
          && Object.keys(cardData.mpecs).length === 1
          && (!cardData.labels || cardData.labels.length === 0)
        ">
        <strong v-if="index === 0" :key="`mpecs-strong${index}`">En fonction de votre recherche :</strong>

        <SafeLink v-if="value.total === 1" :key="`mpecs-unique-link${index}`" :link="value.os_lien">
          {{ value.os_title }}
        </SafeLink>

        <SafeLink v-else :key="`mpecs-link${index}`" :link="value.etb_lien">
          {{ value.service_title }} ({{ value.total }})
        </SafeLink>

        <ul :key="index">
          <li v-for="(uo, idx) in value.uo" :key="`uo-${idx}`">{{ uo }}</li>
        </ul>
      </template>

      <!-- start  label highlight-->
      <template v-if="cardData.labels?.length !== 0">
        <!--       eslint-disable-next-line         -->
        <template v-for="(label, index) in cardData.labels">
          <span v-if="label.highlight > 0" :key="index">
            <strong>En fonction de votre recherche :</strong>
            <SafeLink v-if="label.qty > 1" :link="label.etb_lien">{{ label.titre }}</SafeLink>
            <SafeLink v-else-if="label.qty === 1" :link="label.os_lien">{{ label.titre }}</SafeLink>
          </span>
        </template>
      </template>
      <!-- end label highlight-->

      <template v-if="name !== 'Voir tous les services' && Object.keys(cardData.mpecs).length > 1">
        <strong v-if="index === 0" :key="`mpecs-strong${index}`">En fonction de votre recherche :</strong>

        <SafeLink v-if="value.total === 1" :key="`mpecs-unique-${index}`" :link="value.os_lien">
          {{ value.service_title }} ({{ value.total }})
        </SafeLink>

        <SafeLink v-else :key="`mpecs-${index}`" :link="value.etb_lien">
          {{ value.service_title }} ({{ value.total }})
        </SafeLink>
      </template>
    </template>
  </div>
  <!-- end bloc service etb case all service-->

  <!-- start bloc service etb case mpecs vide-->
  <div v-else-if="showEtbCaseMpecsVide" class="card-elm-item card-function">
    <template v-for="(value, name, index) in cardData.os">

      <SafeLink v-if="name === 'Voir tous les services'" :key="index" :link="value.etb_lien" :title="name">
        {{ name }}
      </SafeLink>

      <template v-else>
        <strong :key="`mpecs-strong${index}`">En fonction de votre recherche : </strong>
        <SafeLink v-if="value.length && value[0].os_lien" :key="index" :link="value[0].os_lien">
          {{ value[0].os_title }}
        </SafeLink>
      </template>

    </template>
  </div>
  <!-- end bloc service etb case mpecs vide-->

  <!-- start bloc case maternité-->
  <div v-else-if="showCaseMaternite" class="card-elm-item card-function">
    <template v-for="(value, name, index) in cardData.mpecs" :key="index">
      <strong v-if="index === 0" :key="`mpecs-strong${index}`">En fonction de votre recherche : </strong>
      <SafeLink :link="value.etb_lien">{{ value.service_title }}</SafeLink>
    </template>
  </div>
  <!-- end bloc link case maternité-->

  <!-- start bloc case urgence-->
  <div v-else-if="showCaseUrgence" class="card-elm-item card-function">
    <template v-for="(value, name, index) in cardData.mpecs">
      <strong v-if="index === 0" :key="`mpecs-strong${index}`">En fonction de votre recherche : </strong>
      <div v-if="value.total === 1 && value.os_telephone !== ''" :key="`mpecs-unique-${index}`">
        <SafeLink :link="value.os_lien">{{ value.urgence_title }} </SafeLink> :
        <SafeLink :link="`tel:${value.os_telephone}`">{{ value.os_telephone }}</SafeLink>
      </div>

      <div v-else :key="`mpecs-${index}`">
        <SafeLink :link="value.os_lien">{{ value.urgence_title }} </SafeLink> :
        <SafeLink :link="`tel:${value.etb_telephone}`"> {{ value.etb_telephone }}</SafeLink>
      </div>
    </template>
  </div>
  <!-- end bloc case urgence-->
</template>

<script>
import { computed } from 'vue';

import SafeLink from '@/components/sharedComponents/SafeLink.component.vue';

export default {
  components: {
    SafeLink,
  },
  props: {
    cardData: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    const showRendezVousLink = computed(() => props.cardData?.ss_type === 'service_de_sante'
      && props.cardData?.ss_field_site_internet
      && props.cardData?.ss_field_site_internet_title?.toLowerCase().trim() === 'prise de rendez-vous');

    const showEtbCaseAllService = computed(() => props.cardData?.type_what === 'normal'
      && props.cardData?.mpecs?.length !== 0);

    const showEtbCaseMpecsVide = computed(() => props.cardData?.type_what === 'normal'
      && props.cardData?.os
      && Object.keys(props.cardData?.os).length !== 0
      && props.cardData?.mpecs.length === 0);

    const showCaseMaternite = computed(() => props.cardData?.type_what === 'maternite'
      && props.cardData?.mpecs[props.cardData?.type_what] !== undefined
      && props.cardData?.mpecs[props.cardData?.type_what].service_title
      && props.cardData?.mpecs?.length !== 0);

    const showCaseUrgence = computed(() => props.cardData?.type_what?.toLowerCase() === 'urgence'
      && props.cardData?.mpecs?.length !== 0);

    return {
      showRendezVousLink,
      showEtbCaseAllService,
      showEtbCaseMpecsVide,
      showCaseMaternite,
      showCaseUrgence,
    };
  },
};
</script>
