# Résultats des Tests de l'API WhatsApp

## Date: 21 mai 2025

## 1. Résumé

Ce document présente les résultats des tests effectués sur les API WhatsApp, à la fois sur l'API Meta Cloud directe et sur notre API locale qui sert d'interface avec l'API Meta.

## 2. Tests de l'API Meta Cloud

### Premiers tests - Résultats négatifs
Les tests initiaux directs sur l'API Meta Cloud ont échoué avec des messages d'erreur similaires:

```
(#100) Tried accessing nonexisting field (message_templates) on node type (WhatsAppBusinessPhoneNumber)
```

### Tests corrigés - Résultats positifs
Après correction des endpoints (utilisation de WABA_ID au lieu de PHONE_NUMBER_ID), nous avons obtenu des résultats positifs:

1. **Récupération des informations du compte WABA**: ✅ Succès
   ```json
   {
     "id": "664409593123173",
     "name": "Oracle WhatsApp Business Account",
     "currency": "USD"
   }
   ```

2. **Récupération des templates via WABA_ID**: ✅ Succès
   ```json
   {
     "data": [
       {
         "name": "connection_check", 
         "status": "APPROVED",
         "category": "UTILITY", 
         "language": "fr"
       },
       {
         "name": "qshe_invitation1", 
         "status": "APPROVED",
         "category": "MARKETING", 
         "language": "fr"
       }
       // plus de templates...
     ]
   }
   ```

3. **Envoi de template simple**: ✅ Succès
   ```json
   {
     "messaging_product": "whatsapp",
     "contacts": [{"input": "+2250777104936", "wa_id": "22577104936"}],
     "messages": [{"id": "wamid.HBgLMjI1NzcxMDQ5MzYVAgARGBIzOTUzNjBGRjJERTA0QkEyM0YA", "message_status": "accepted"}]
   }
   ```

4. **Envoi de template avec image**: ✅ Succès
   ```json
   {
     "messaging_product": "whatsapp",
     "contacts": [{"input": "+2250777104936", "wa_id": "22577104936"}],
     "messages": [{"id": "wamid.HBgLMjI1NzcxMDQ5MzYVAgARGBI2MjlCNjg2REY4M0U5MkYzNDAA", "message_status": "accepted"}]
   }
   ```

### Problèmes identifiés
1. **Confusion d'endpoints**: Nous utilisions incorrectement PHONE_NUMBER_ID pour accéder aux templates, alors qu'il faut utiliser WABA_ID.
2. **Structure d'API différente**: La structure des endpoints varie selon l'opération:
   - Pour récupérer des templates: `/$WABA_ID/message_templates`
   - Pour envoyer des messages: `/$PHONE_NUMBER_ID/messages`
3. **Problème d'upload média**: L'API d'upload de médias rencontre des erreurs avec message "The parameter file is required".

## 3. Tests de l'API Locale

### Résultats

1. **Endpoint de santé (`/api.php?health=check`)**: Erreur fatale concernant un problème de type dans `BatchSegmentationService` (erreur de configuration DI).

2. **Endpoint des templates approuvés (`/api/whatsapp/templates/approved.php`)**: 
   - ✅ Fonctionne correctement
   - Retourne 7 templates (greeting, support, confirmation, information, promotion, greeting_en, support_en)
   - Type de retour JSON valide

3. **Endpoint des templates avec forceRefresh (`/api/whatsapp/templates/approved.php?force_refresh=true`)**: 
   - Retourne un tableau vide
   - Peut indiquer que l'API ne parvient pas à actualiser les données depuis Meta
   - `"status":"success"`, `"templates":[]`, `"count":0`

4. **GraphQL - templates utilisateurs**: 
   - ❌ Erreur d'authentification: "L'utilisateur doit être authentifié"
   - Nécessite une connexion utilisateur

5. **GraphQL - métriques d'utilisation**: 
   - ❌ Erreur "Cannot return null for non-nullable field"
   - Semble être un problème de type dans le schéma GraphQL (champ non-nullable retournant null)

6. **GraphQL - templates les plus utilisés**: 
   - ❌ Erreur "Cannot query field getMostUsedWhatsAppTemplates"
   - Suggestion d'utiliser "mostUsedWhatsAppTemplates" (erreur de nommage)

7. **GraphQL - envoi de message template**: 
   - ❌ Erreurs multiples
   - Champ "recipient" requis mais non fourni
   - Champ "phoneNumber" n'existe pas
   - Champ "templateLanguage" n'existe pas

### Problèmes identifiés

1. **Erreur fatale dans BatchSegmentationService**: Problème d'injection de dépendances. Le constructeur attend un `PhoneNumberRepositoryInterface` mais reçoit `PhoneNumberRepository`.

2. **Incohérence dans le forceRefresh**: L'API locale retourne un tableau vide lors du forceRefresh, ce qui suggère que la connexion à l'API Meta ne fonctionne pas correctement.

3. **Problèmes d'authentification GraphQL**: Les endpoints GraphQL nécessitent une authentification, ce qui n'est pas documenté ou pas correctement géré dans nos tests.

4. **Incohérences dans le schéma GraphQL**: 
   - Type non-nullable retournant null
   - Noms de méthodes incorrects ("getMostUsedWhatsAppTemplates" vs "mostUsedWhatsAppTemplates")
   - Structure d'entrée incorrecte pour l'envoi de message (recipient vs phoneNumber, templateLanguage manquant)

## 4. Tests de messages réels

### Résultats

1. **Envoi de template "connection_check"**: ✅ Succès
   - Envoyé avec succès au numéro +2250777104936
   - Message de type texte simple sans paramètres
   - ID de message reçu: `wamid.HBgLMjI1NzcxMDQ5MzYVAgARGBIzOTUzNjBGRjJERTA0QkEyM0YA`

2. **Envoi de template "qshe_invitation1" avec image**: ✅ Succès
   - Envoyé avec succès au numéro +2250777104936
   - Message avec en-tête image, corps texte et bouton
   - L'image a été fournie via URL (`image.link`)
   - ID de message reçu: `wamid.HBgLMjI1NzcxMDQ5MzYVAgARGBI2MjlCNjg2REY4M0U5MkYzNDAA`

3. **Test de la limite des 24 heures**: ⚠️ Non testé
   - Les messages templates peuvent être envoyés en dehors de la fenêtre de 24h
   - Les messages non-templates ne peuvent être envoyés que dans les 24h après la dernière interaction du client

4. **Test de templates avec variables dynamiques**: ⚠️ Partiellement testé
   - Test de remplacement de variables simples réussi
   - Test de formats complexes (listes, boutons personnalisés) non testé

## 5. Conclusions

1. **API Meta Cloud**:
   - ✅ Fonctionne correctement avec les identifiants actuels lorsque les bons endpoints sont utilisés
   - ✅ Permet la récupération de templates et l'envoi de messages
   - ⚠️ L'API d'upload média nécessite des corrections supplémentaires

2. **API REST locale**:
   - ✅ L'endpoint `/api/whatsapp/templates/approved.php` fonctionne correctement pour la récupération des templates
   - ❌ L'option forceRefresh ne parvient pas à actualiser les données depuis Meta
   - ⚠️ L'API semble utiliser des données de fallback/cache plutôt que des données fraîches de l'API Meta

3. **API GraphQL**:
   - ❌ Nombreux problèmes de schéma et d'authentification
   - ❌ Nécessite des corrections pour les noms de champs et la structure des entrées
   - ⚠️ Les mutations d'envoi de message ne sont pas testées en conditions réelles

## 6. Recommandations

1. **Correction de l'API Meta**:
   - ✅ Utiliser la structure correcte des endpoints (WABA_ID vs PHONE_NUMBER_ID selon l'opération)
   - ✅ Les identifiants actuels fonctionnent correctement
   - ⚠️ Investiguer le problème d'upload média

2. **Correction du schéma GraphQL**:
   - Corriger les incohérences de nommage
   - Résoudre les problèmes de types non-nullables
   - Mettre à jour la documentation pour la structure d'entrée correcte

3. **Correction de l'injection de dépendances**:
   - Résoudre le problème de type dans BatchSegmentationService

4. **Amélioration de la robustesse**:
   - Mettre en place un système de logging plus détaillé pour les appels d'API
   - Ajouter un monitoring des taux de succès/échec des appels WhatsApp
   - Améliorer le cache local des templates avec invalidation intelligente

5. **Documentation**:
   - Créer un guide clair sur la structure des endpoints Meta
   - Documenter les paramètres requis pour chaque type de template
   - Documenter les limitations (taille des médias, fenêtre de 24h, etc.)

6. **Tests automatisés**:
   - Mettre en place des tests unitaires pour les services WhatsApp
   - Créer des tests d'intégration pour l'API Meta avec mocks
   - Ajouter des tests end-to-end pour les scénarios critiques

7. **Interface utilisateur**:
   - Améliorer l'interface de sélection et configuration des templates
   - Ajouter une prévisualisation des templates avec leurs paramètres
   - Créer une interface de monitoring des messages WhatsApp