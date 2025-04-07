# Frontend - Application de Segmentation de Numéros de Téléphone

Ce projet est le frontend de l'application de segmentation de numéros de téléphone, développé avec Vue.js 3, Quasar Framework, TypeScript et Apollo Client pour GraphQL.

## Technologies utilisées

- **Vue.js 3** : Framework JavaScript progressif pour la construction d'interfaces utilisateur
- **Quasar Framework** : Framework basé sur Vue.js pour créer des applications responsive
- **TypeScript** : Superset typé de JavaScript
- **Pinia** : Gestionnaire d'état pour Vue.js
- **Vue Router** : Routeur officiel pour Vue.js
- **Apollo Client** : Client GraphQL complet pour Vue.js
- **Vite** : Outil de build moderne pour le développement web

## Structure du projet

```
frontend/
├── public/             # Fichiers statiques
├── src/
│   ├── assets/         # Images, polices, etc.
│   ├── components/     # Composants Vue réutilisables
│   ├── router/         # Configuration de Vue Router
│   ├── stores/         # Stores Pinia
│   ├── views/          # Composants de page
│   ├── App.vue         # Composant racine
│   └── main.ts         # Point d'entrée de l'application
├── .eslintrc.js        # Configuration ESLint
├── .prettierrc.js      # Configuration Prettier
├── index.html          # Page HTML principale
├── package.json        # Dépendances et scripts
├── tsconfig.json       # Configuration TypeScript
└── vite.config.ts      # Configuration Vite
```

## Installation

```bash
# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev

# Compiler pour la production
npm run build
```

## Fonctionnalités

- Segmentation individuelle de numéros de téléphone
- Traitement par lot de numéros de téléphone
- Gestion des segments personnalisés
- Envoi de SMS
- Import/Export de données

## Communication avec le backend

L'application communique avec le backend via GraphQL, en utilisant Apollo Client. Les requêtes et mutations sont définies dans les composants Vue correspondants.
