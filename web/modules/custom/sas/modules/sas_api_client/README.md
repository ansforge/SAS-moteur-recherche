SAS-API Client
==================

Ce module fournit le core pour les appels du SAS-API.

=================================================================================
### Appel du service
Le login est fait automatiquement avec les paramètres renseignés dans SAS-API Settings.

Chaque requête accepte un array de paramètres pouvant contenir les clés suivantes :
`tokens` :  Remplace les tokens présents dans les clés d'annotations "endpoint" entre {}.
            le token {version} est automatiquement remplacé avec des valeurs de constantes de classes API_VERSION.
`query` : Array de la query_string.
`body` : Array de data en POST, PUT, PATCH, DELETE si nécessaire.
* Exemple :
  ```php
    $apiClientService = \Drupal::service('sas_api_client.service');

    /**
     * Requête SAS-API
     * Voir les plugins disponibles dans le dossier :
     * web/modules/custom/sas/modules/sas_api_client/src/Plugin/ClientEndpoint/SasApi
     * Exemple:
     */

    $request = $apiClientService->sas_api('get_slot', [
      'tokens' => ['id' => '5'],
    ]);

    /**
     * Requête SAS Config
     * Voir les plugins disponibles dans le dossier :
     * web/modules/custom/sas/modules/sas_api_client/src/Plugin/ClientEndpoint/SasApiConfig
     * Exemple:
     */

    $request = $apiClientService->sas_api_config('config', [
      'tokens' => ['id' => 'snp_options'],
    ]);

    /**
     * Requête Aggregator
     * Voir les plugins disponibles dans le dossier :
     * web/modules/custom/sas/modules/sas_api_client/src/Plugin/ClientEndpoint/Aggregator
     * Exemple:
     */

    $request = $apiClientService->aggregator('practitioner', [
      'tokens' => ['id' => '810001238574'],
    ]);

    $request = $apiClientService->aggregator('token', []);


  ```
### Accès
  Les accès sont gérés pour chaque endpoint via la méthode `access(array $params)`
  avec en arguments les paramètres de la requête pour checker par id par ex.

  L'objet currentUser est disponible pour affiner la gestion des accès si nécessaire.

