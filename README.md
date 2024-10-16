# Présentation de la plateforme numérique SAS

Le Service d’accès aux soins est un nouveau service d'orientation de la population dans leur parcours de soins.

Pour le patient confronté à un besoin de soins urgents ou non programmés et lorsque l'accès à son médecin traitant n'est pas possible, le SAS doit permettre d'accéder, à toute heure et à distance à un professionnel de santé.

La plateforme numérique SAS est un outil dédié aux professionnels de la chaîne de régulation médicale pour faciliter l'orientation vers la médecine de ville.

# Socle technique

 1. Front Drupal + VueJS
 2. API métiers (géolocalisation, calendrier, etc.)
 3. Agrégateur de créneaux (API FHIR éditeurs)

Remarque :
Ce repository ne contient pas l'intégralité du code permettant de cloner et déployer l'applicatif, en particulier des éléments mutualisés du socle ou des services tiers qui n'impactent pas la logique de l'algorithme de recherche.
Il n'y a donc pas de documentation relative à l'installation de l'application.

# Algorithme du moteur de recherche

Le présent document a pour but de faciliter la navigation au sein du code source de la plateforme numérique SAS. Le lecteur pourra suivre le déroulé de l'algorithme et accéder directement au module du code en charge des différentes étapes.

[<img src="./doc/img/sas-algorithme-recherche.svg" title="Diagramme de l'algorithme" />](./doc/img/sas-algorithme-recherche.png)

Pour accéder au détail de l'algorithme, cliquer sur les étapes ci-après :
- [Etape 1](./doc/step-1.md) - L'utilisateur renseigne les champs [Spécialité] et [Localisation]
- [Etape 2](./doc/step-2.md) - Exécution de la recherche
- [Etape 3](./doc/step-3.md) - Chargement progressif du résultat de la recherche
- [Etape 3a](./doc/step-3a.md) - Récupération de l'offre de soins selon géolocalisation
- [Etape 3b](./doc/step-3b.md) - Récupération des disponibilités selon géolocalisation

# Conventions
L'accès au code correspondant à la fonctionnalité décrite se fait par le lien entre accolade :  
- [{fonctionnalité}](#)  

Un extrait du code peut être fourni dans le contexte avec référence aux principales propriétés, méthodes, classes, composants, etc. :  
>**[Component] Class.method()**  
```
Extrait du code source
```

# Crédits

Le code applicatif a été réalisé par Klee Conseil et Intégration, piloté par l'Agence du numérique en santé sur délégation du Ministère de la Santé et de la Prévention (Direction Générale de l'Offre de Soins).