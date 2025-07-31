# Guide d'Observabilité et Traçabilité pour le Système de Média

Ce document décrit les mécanismes d'observabilité et de traçabilité implémentés dans le système de gestion des médias pour faciliter le débogage et le suivi des erreurs.

## 1. Système de Journalisation

### 1.1 MediaLogger

Le `MediaLogger` est une classe centrale pour la journalisation des opérations liées aux médias. Il fournit:

- Journalisation structurée avec niveaux de sévérité (DEBUG, INFO, WARN, ERROR)
- Corrélation d'ID pour suivre les opérations connexes
- Stockage persistant des logs dans le localStorage
- Nettoyage automatique des anciens logs

```typescript
import { mediaLogger } from '@/services/mediaLogger';

// Créer une nouvelle session de journalisation avec ID de corrélation
const correlationId = mediaLogger.createCorrelationId();

// Journaliser les différents niveaux d'information
mediaLogger.debug('Message de débogage', { data: 'optionnel' }, 'opération');
mediaLogger.info('Message d'information', { data: 'optionnel' }, 'opération');
mediaLogger.warn('Avertissement', { data: 'optionnel' }, 'opération');
mediaLogger.error('Erreur', { error: e }, 'opération');

// Récupérer les logs
const allLogs = mediaLogger.getLogs();
const errorsOnly = mediaLogger.getLogsByLevel(LogLevel.ERROR);
const uploadLogs = mediaLogger.getLogsByOperation('uploadFile');
```

### 1.2 Fichiers journalisés

Le système enregistre des informations détaillées sur:

- Toutes les opérations d'upload (succès, échecs, reprises)
- Accès au cache et utilisation
- Optimisation d'images
- Gestion des médias récents et favoris
- Erreurs réseau et systèmes

## 2. Diagnostic et Dépannage

### 2.1 Outil de Diagnostics Média

Un composant Vue `MediaDiagnostics.vue` est disponible pour afficher les informations de diagnostic:

- Journal des événements avec filtrage par niveau
- Informations système (navigateur, plateforme, stockage)
- Statistiques média (nombre, types, distribution)
- Tests de connectivité réseau

Pour l'utiliser:

```vue
<template>
  <div>
    <q-btn @click="showDiagnostics = true" label="Diagnostics" />
    <MediaDiagnostics v-model="showDiagnostics" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import MediaDiagnostics from '@/components/media/MediaDiagnostics.vue';

const showDiagnostics = ref(false);
</script>
```

Le composant est accessible dans la galerie média via l'icône "developer_board".

### 2.2 Rapport de Diagnostic Automatique

En cas d'erreur, le système génère automatiquement un rapport de diagnostic:

```typescript
const diagnostics = mediaLogger.generateDiagnostics();
```

Ce rapport inclut:
- Informations sur le navigateur
- État du réseau
- Utilisation du stockage
- Statistiques média
- Erreurs récentes

## 3. Traçabilité des Opérations

### 3.1 ID de Corrélation

Chaque opération d'upload de média génère un ID de corrélation unique qui permet de suivre l'ensemble du cycle de vie de l'opération:

```typescript
// Dans mediaService.ts
const correlationId = mediaLogger.createCorrelationId();
// Tous les logs suivants incluront automatiquement cet ID
```

### 3.2 Suivi des Opérations

Toutes les étapes importantes sont enregistrées avec l'opération correspondante:
- Vérification du cache
- Tentatives d'upload
- Fallbacks et reprises
- Succès ou échec

## 4. Mécanismes Préventifs

Plusieurs mécanismes préventifs ont été implémentés:

1. **Vérification préalable des conditions réseau** - Détection de l'état de connexion avant l'upload
2. **Vérification du type de média** - Validation des types MIME acceptés
3. **Optimisation automatique** - Réduction de la taille des images pour améliorer la fiabilité des uploads
4. **Gestion de cache** - Prévention des uploads redondants
5. **Mécanisme de reprise** - Récupération automatique des uploads interrompus

## 5. Dépannage Courant

### 5.1 Échecs d'upload

Si un upload échoue:
1. Vérifiez les journaux avec l'outil de diagnostics (niveau ERROR)
2. Vérifiez l'état de la connexion réseau
3. Vérifiez si le type de fichier est supporté
4. Tentez d'utiliser la reprise d'upload si proposée

### 5.2 Problèmes de Cache

Si le cache ne fonctionne pas correctement:
1. Vérifiez les journaux de cache dans l'outil de diagnostics
2. Consultez l'utilisation du stockage
3. Essayez de vider le cache du navigateur

### 5.3 Problèmes de Performance

Si les performances sont lentes:
1. Vérifiez la taille des fichiers
2. Activez l'optimisation d'image automatique
3. Vérifiez l'utilisation du stockage dans l'outil de diagnostics

## 6. Recommandations pour les Tests via Navigateur

1. Activez la console développeur (F12) pour voir les logs en temps réel
2. Utilisez l'outil de diagnostics pour un aperçu complet
3. Testez avec différentes tailles de fichiers
4. Simulez des conditions réseau variables (Chrome DevTools > Network > Throttling)
5. Testez le mécanisme de reprise en interrompant les uploads