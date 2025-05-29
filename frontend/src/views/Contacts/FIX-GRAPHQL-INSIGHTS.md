# Correction de l'erreur GraphQL WhatsApp Insights

## ✅ Problème résolu

### Erreur
```
Field "messagesByType" of type "MessagesByTypeMap!" must have a sub selection.
```

### Cause
Les champs `messagesByType`, `messagesByStatus` et `messagesByMonth` sont des types GraphQL complexes (objets) qui nécessitent une sous-sélection des champs à récupérer.

### Solution appliquée

Dans `src/stores/contactStore.ts`, la requête GraphQL a été mise à jour :

```graphql
messagesByType {
  text
  image
  document
  video
  audio
  template
}
messagesByStatus {
  sent
  delivered
  read
  failed
}
messagesByMonth {
  month
  count
}
```

### Structure des types

**Backend (GraphQL Schema):**
- `MessagesByTypeMap` : Objet avec compteurs par type de message
- `MessagesByStatusMap` : Objet avec compteurs par statut
- `MessagesByMonthMap` : Tableau d'objets avec mois et compteur

**Frontend (TypeScript):**
- Utilise `Record<string, number>` pour une flexibilité maximale

## 🎯 Résultat

L'erreur GraphQL est maintenant corrigée et les insights WhatsApp devraient s'afficher correctement dans le module Contacts.