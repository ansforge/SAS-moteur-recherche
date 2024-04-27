# Etape 3 - Chargement progressif du résultat de la recherche

Le chargement progressif est traité par la page `search-page` de l'application VueJS.
- [{Chargement et navigation}](../web/modules/custom/sas/modules/sas_vuejs/vue-core/src/pages/chargement-progressif/Search.page.vue#L469)  

Lorsque le composant est monté, il est initialisé et démarre la recherche à partir des informations contextuelles à disposition.
>**[Page] initialize()** = récupère le contexte utilisateur et adapte l'affichage en conséquence, le médecin traitant s'il y a lieu dans le cas d'une recherche émise par un LRM  
```javascript
    async function initialize() {
      // init all data before search
      await fetchAndApplyConfiguration();

      // init data if pref Dr or precise search
      await configureSearchPrefDoctor();

      adaptDisplayToCurrentUser();

      const hasCustomFilters = !_isEmpty(searchDataStore.customFilters);

      // launch search
      await fetchBatchSolr(hasCustomFilters);
    }
```

# 
| [Retour à l'accueil](../README.md) | [Page précédente](step-2.md) | [Page suivante](step-3a.md) |