<template>
  <div>
    <div class="fake-header" />
    <main class="home-wrapper">
      <section class="home-img" :style="backgroundImage" />
      <section class="home-connexion">
        <div class="home-title">
          <h1>{{ description }}</h1>
        </div>
        <div class="home-list">
          <ul>
            <li v-for="(objective, index) in objectives" v-bind:key="index"><div>{{ objective }}</div></li>
          </ul>
        </div>
        <div class="home-btn">
          <WidgetButton @click.prevent="triggerSignInHeaderButton" theme="tertiary">Se connecter</WidgetButton>
        </div>

      </section>
    </main>
    <footer />
  </div>
</template>

<script>
import { computed } from 'vue';
import WidgetButton from '@/components/sharedComponents/WidgetButton.component.vue';

export default {
  name: 'HomeDisconnected',
  components: { WidgetButton },
  props: {
    description: { type: String, default: '' },
    bgImage: { type: String, default: '' },
    objectives: { type: Array, default: () => ([]) },
  },
  setup(props) {
    const backgroundImage = computed(() => (props.bgImage ? `background-image: url(${props.bgImage});` : ''));

     /**
     * trigger click on drupal login btn
     */
    function triggerSignInHeaderButton() {
      const headerEl = document.querySelector('.main-header #block-saslogin');
      const accountPanelEl = headerEl.querySelector('.account-panel-opener a');
      const loginKeyclockEl = headerEl.querySelector('button#edit-openid-connect-client-sas-login');

      if (accountPanelEl) {
        accountPanelEl.click();
      } else {
        loginKeyclockEl.click();
      }
    }

    return {
      backgroundImage,
      triggerSignInHeaderButton,
    };
  },
};
</script>
