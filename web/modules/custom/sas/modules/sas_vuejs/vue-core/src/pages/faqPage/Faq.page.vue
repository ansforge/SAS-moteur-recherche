<template>

  <div v-if="isMobile" class="faq-sub-header--navigation">
    <div id="select-faq--wrapper" class="select-faq--container">
      <div class="form-group">
        <select v-model="selectMobileIndex" @change="onThemeChange(selectMobileIndex)">
          <option
            v-for="(theme, index) in themes"
            :key="theme.thematic_key"
            :value="index"
          >
            {{theme.thematic}}
          </option>
        </select>
      </div>
    </div>
  </div>

  <div class="faq-header container-full">
    <h1>Vous avez besoin d'aide ?</h1>
    <p class="subtitle">Foire aux questions</p>
  </div>
  <div v-if="!isLoading" class="temp-column container-full">
    <ul v-if="!isMobile" class="faq-column-theme">
      <li class="faq-theme" :class="{ active: indexSelectedTheme === index }" v-for="(theme, index) in themes" v-bind:key="theme.thematic_key" @click="onThemeChange(index)">
        <div class="faq-theme-text">
          {{ theme.thematic }}
        </div>
        <i class="icon-right" aria-hidden="true" />
      </li>
    </ul>

    <div class="faq-column-quest">
      <div class="faq-column-wrapper" v-for="(question, index) in themes[indexSelectedTheme].faqs" v-bind:key="themes[indexSelectedTheme].thematic_key + index" @click="showCollapse(index)">
        <div class="faq-question">
          {{ question.question }}

          <i class="icon" aria-hidden="true" :class="[{ 'icon-up': question.isActive }, { 'icon-right': !question.isActive }]">
            <span class="sr-only">consulter les horaires</span>
          </i>
        </div>

        <SlideUpDownRgaa class="faq-response-content" :id="`collapse-answer-${themes[indexSelectedTheme].thematic_key}`" :active="question.isActive" :duration="0">
          <div class="faq-response" v-html="question.answer" />
        </SlideUpDownRgaa>
      </div>

      <div class="faq-block-contact">
        <div class="faq-contact">
          <p>
            Si vous n’avez pas trouvé la réponse à votre question cliquez sur le lien suivant :
          </p>
          <a href="/sas/faq/contact">[Besoin d’assistance - formulaire de contact]</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useFaq, useUser, useMobileDetection } from '@/composables';
import SlideUpDownRgaa from '@/components/sharedComponents/plugins/SlideUpDownRgaa.vue';

export default {
  components: { SlideUpDownRgaa },
  setup() {
    const { currentUser, getCurrentUser, isPscUser } = useUser();
    const { getThemes } = useFaq();
    const indexSelectedTheme = ref(0);
    const themes = ref([]);
    const isLoading = ref(true);
    const isShow = ref(true);
    const selectMobileIndex = ref(0);
    const { isScreenMdMin: isMobile } = useMobileDetection();

    onMounted(async () => {
      await getCurrentUser();
      let role = currentUser.value.getRoles();
      if (!role) role = 'anonymous';
      if (isPscUser()) role = [{ role: 'sas_effecteur' }];
      const themesModel = await getThemes(role);
      themes.value = themesModel.getThemes();
      isLoading.value = false;
    });

    function onThemeChange(index) {
      themes.value.forEach((theme) => {
        theme.faqs.forEach((faq) => {
          // eslint-disable-next-line
          faq.isActive = false;
        });
      });
      indexSelectedTheme.value = index;
      selectMobileIndex.value = index;
    }

    function showCollapse(index) {
      themes.value[indexSelectedTheme.value].faqs[index].isActive = !themes.value[indexSelectedTheme.value].faqs[index].isActive;
    }

    return {
      themes,
      indexSelectedTheme,
      selectMobileIndex,
      isLoading,
      isShow,
      showCollapse,
      onThemeChange,
      isMobile,
    };
  },
};
</script>
