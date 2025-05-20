**Charger les templates directement depuis l'API Meta à chaque fois** que l'utilisateur souhaite en sélectionner un, cela simplifie encore le backend (en termes de gestion de données persistantes) mais déplace la charge vers des appels API plus fréquents.

Cela a des implications sur la performance (chaque sélection de template nécessitera un appel API à Meta) et potentiellement sur les limites de taux de l'API Meta si cette fonctionnalité est utilisée très intensivement. Cependant, pour Oracle, cela peut être une approche viable pour démarrer rapidement.

**pas de stockage local des templates, chargement direct depuis Meta.**

## Plan : Utilisation des Templates WhatsApp (Chargement Direct depuis Meta)

**Objectif Principal :** Permettre aux utilisateurs d'Oracle de sélectionner (en chargeant la liste depuis Meta), configurer (variables de corps et médias de header), et envoyer des messages basés sur les templates WhatsApp approuvés par Meta.

**Prérequis Essentiels (Confirmés Fonctionnels) :**

- `WhatsAppApiClient` fonctionnel pour les appels API Meta (y compris l'appel pour lister les templates du WABA).
- Templates conçus et approuvés sur la console Meta.

---

### **Phase 1 : Backend - Accès et Envoi des Templates (Priorité Haute)**

1.  **Service d'Accès aux Templates Meta (Pas de stockage local) :**
    - **Action (`WhatsAppTemplateService` ou intégré dans `WhatsAppService`) :**
      - Développer une fonction `public function fetchApprovedTemplatesFromMeta(array $filters = []): array`.
        - Utilisera `WhatsAppApiClient` pour appeler l'endpoint Meta listant les templates du WABA (associé à votre App ID/compte).
        - Filtrera la liste retournée par Meta pour ne conserver que les templates avec `status == 'APPROVED'`.
        - Appliquera des filtres supplémentaires si fournis (ex: `name`, `language`, `category` - bien que le filtrage avancé se fera probablement mieux côté client sur la liste complète des approuvés pour MVP).
        - Retournera un tableau de structures de templates (contenant `id` de Meta, `name`, `language`, `category`, `components`).
2.  **Service d'Envoi de Messages Templates :**
    - **Action (`WhatsAppService` ou service dédié) :**
      - Développer/Adapter une fonction `public function sendConfiguredTemplateMessage(int $oracleUserId, string $recipientPhoneNumber, string $templateName, string $templateLanguage, array $templateComponentsFromMeta, array $templateDynamicData): array`.
        - **Modification clé :** Au lieu de `metaTemplateId` et de récupérer de la DB locale, on reçoit directement le `templateName`, `templateLanguage`, et la structure des `templateComponentsFromMeta` (obtenue lors de la sélection par l'utilisateur via `fetchApprovedTemplatesFromMeta`).
        - `$templateDynamicData`: Structure pour variables de corps et infos header média (type `link`/`id` et `value` URL/MediaID).
        - **Logique :**
          - Vérifier le type de header attendu à partir des `$templateComponentsFromMeta`.
          - Construire le payload API Meta en utilisant `$templateName`, `$templateLanguage`, et en mappant `$templateDynamicData` aux `parameters` des composants.
          - Appeler `WhatsAppApiClient->sendMessage()`.
          - Enregistrer dans `whatsapp_message_history`.
3.  **Exposition API GraphQL :**
    - **Action :** Query `fetchApprovedWhatsAppTemplates(filter: TemplateFilterInput): [WhatsAppTemplateGraphQLType]`
      - Resolver : Appellera `WhatsAppTemplateService->fetchApprovedTemplatesFromMeta()`.
      - `WhatsAppTemplateGraphQLType`: Exprimera `id` (de Meta), `name`, `language`, `category`, `components` (la structure JSON brute des composants).
    - **Action :** Mutation `sendWhatsAppTemplate(input: SendLiveTemplateInput!): WhatsAppSentMessageGraphQLType`
      - `SendLiveTemplateInput`: Contiendra `recipientPhoneNumber`, `templateName`, `templateLanguage`, `templateComponentsJsonString` (la structure JSON brute des composants du template sélectionné, que le frontend aura récupéré), et la structure pour `templateDynamicData` (header média et variables du corps).
      - Resolver : Appellera `WhatsAppService->sendConfiguredTemplateMessage()`.

---

### **Phase 2 : Frontend - Sélection et Utilisation des Templates (Priorité Haute)**

1.  **Interface de Sélection & Configuration des Templates :**
    - **Action (dans `WhatsAppSendMessage.vue` ou dédié) :**
      - Proposer un mode "Envoyer via Template".
      - **Au clic ou à l'activation de ce mode :** Appeler la query GraphQL `fetchApprovedWhatsAppTemplates` pour charger la liste des templates disponibles directement depuis Meta.
      - Utiliser un sélecteur pour choisir un template parmi la liste chargée. Le frontend peut appliquer des filtres (nom, catégorie) sur cette liste.
      - Analyser les `components` (JSON brut) du template sélectionné pour générer dynamiquement :
        - Des champs de saisie pour chaque variable `{{N}}` du `BODY`.
        - Si header média (ex: `IMAGE`) : des champs pour fournir l'URL publique du média ou un ID de média (prioriser URL pour MVP).
      - Rassembler les données saisies, le `templateName`, `templateLanguage`, et les `templateComponentsJsonString` du template choisi, et appeler la mutation GraphQL `sendWhatsAppTemplate`.

---

**Impacts de cette approche "sans stockage local" :**

- **Avantages :**
  - Moins de complexité backend (pas de table de templates).
  - Toujours la liste la plus à jour des templates.
- **Inconvénients potentiels :**
  - Performance : Le chargement de la liste des templates à chaque fois peut être plus lent pour l'utilisateur (dépend du nombre de templates et de la latence de l'API Meta).
  - Moins de contrôle/visibilité : Vous n'avez pas de vue d'ensemble des templates dans votre propre base de données pour des analyses ou des rapports internes (mais ce n'est pas un objectif MVP).

Ce plan est extrêmement focalisé sur l'utilisation directe des templates via l'API Meta.
