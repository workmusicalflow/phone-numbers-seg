# Oracle Project - Design System & Style Guide

## Vue d'ensemble

Ce document définit le système de design moderne du projet Oracle, une plateforme de gestion SMS et WhatsApp avec Vue.js 3 et Quasar Framework. L'approche adoptée privilégie une interface moderne, épurée et cohérente avec des interactions fluides.

## 🎨 Identité Visuelle

### Palette de Couleurs

#### Couleurs Principales

- **WhatsApp Green**: `#25d366` (primary), `#128c7e` (dark)
- **Purple Accent**: `#8b5cf6` (primary), `#7c3aed` (dark)
- **Blue System**: `#3b82f6` (primary), `#1d4ed8` (dark)

#### Couleurs Fonctionnelles

- **Success**: `#10b981` → `#059669`
- **Warning**: `#f59e0b` → `#d97706`
- **Error**: `#ef4444` → `#dc2626`
- **Info**: `#0ea5e9` → `#0284c7`

#### Couleurs Neutres

- **Text Primary**: `#1f2937`
- **Text Secondary**: `#374151`
- **Text Muted**: `#6b7280`
- **Border**: `#e5e7eb`
- **Background**: `#f8fafc`

### Gradients Signature

```scss
// Gradients principaux utilisés dans l'interface
$gradient-whatsapp: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
$gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
$gradient-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
$gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
$gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
$gradient-error: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);

// Gradients de fond
$gradient-card: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
$gradient-banner-purple: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%);
$gradient-banner-blue: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
```

## 🏗️ Architecture des Composants

### Structure de Carte Moderne

#### Template de Base

```vue
<template>
  <q-card class="modern-card">
    <q-card-section class="card-header">
      <div class="header-content">
        <div class="header-icon-wrapper">
          <q-icon name="icon_name" size="md" />
        </div>
        <div class="header-text">
          <h3 class="card-title">Titre Principal</h3>
          <p class="card-subtitle">Description secondaire</p>
        </div>
      </div>
    </q-card-section>

    <q-separator />

    <q-card-section class="content-section">
      <!-- Contenu principal -->
    </q-card-section>
  </q-card>
</template>
```

#### Styles SCSS Associés

```scss
.modern-card {
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(229, 231, 235, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
  }
}

.card-header {
  background: $gradient-whatsapp; // ou autre gradient selon le contexte
  color: white;
  padding: 24px;

  .header-content {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .header-icon-wrapper {
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .card-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
  }
}

.content-section {
  padding: 32px 24px;
}
```

### Système de Formulaires

#### Groupes d'Entrée

```vue
<div class="input-group">
  <label class="input-label">
    <q-icon name="icon_name" class="q-mr-xs" />
    Label du champ
  </label>
  <q-input
    v-model="value"
    placeholder="Placeholder text..."
    outlined
    class="modern-input"
  >
    <template v-slot:prepend>
      <q-icon name="prepend_icon" color="green" />
    </template>
  </q-input>
</div>
```

#### Styles d'Entrée Modernes

```scss
.input-group {
  margin-bottom: 24px;

  .input-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.95rem;
  }
}

.modern-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;

    &:hover {
      border-color: #d1d5db;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:focus-within {
      border-color: #25d366;
      box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
    }
  }

  :deep(.q-field__native) {
    padding: 12px 16px;
  }
}
```

### Boutons d'Action

#### Types de Boutons

```vue
<!-- Bouton Principal -->
<q-btn
  class="action-btn primary-btn"
  color="green"
  icon="icon_name"
  label="Action Principale"
  @click="handleAction"
/>

<!-- Bouton Secondaire -->
<q-btn
  class="action-btn secondary-btn"
  color="grey-7"
  outline
  icon="icon_name"
  label="Action Secondaire"
  @click="handleSecondaryAction"
/>
```

#### Styles de Boutons

```scss
.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #f3f4f6;

  .action-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 24px;
    text-transform: none;
    transition: all 0.2s ease;
    min-width: 160px;

    &.primary-btn {
      background: $gradient-whatsapp;
      box-shadow: 0 4px 16px rgba(37, 211, 102, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
      }
    }

    &.secondary-btn {
      border: 2px solid #e5e7eb;
      color: #6b7280;

      &:hover {
        border-color: #d1d5db;
        background: #f9fafb;
      }
    }
  }
}
```

## 📊 Cartes de Statut et Feedback

### Cartes d'État Colorées

```scss
.status-card {
  border-radius: 12px;
  overflow: hidden;
  border: none;
  color: white;

  &.upload-status {
    background: $gradient-blue;
  }

  &.success-status {
    background: $gradient-success;
  }

  &.error-status {
    background: $gradient-error;
  }

  &.warning-status {
    background: $gradient-warning;
  }

  .status-content {
    padding: 20px 24px;
  }

  .status-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;

    .status-icon {
      width: 48px;
      height: 48px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
  }
}
```

### Banners Informatifs

```vue
<q-banner class="info-banner">
  <template v-slot:avatar>
    <q-icon name="info" color="blue" />
  </template>
  <div class="text-body2">
    <strong>Information importante</strong><br>
    Description détaillée du message informatif.
  </div>
</q-banner>
```

```scss
.info-banner {
  background: $gradient-banner-blue;
  border: 1px solid #0ea5e9;
  border-radius: 12px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;

  &::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #0ea5e9;
  }
}
```

## 🎯 Système de Navigation

### Tabs Modernes

```scss
.modern-tabs {
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 24px;

  :deep(.q-tab) {
    padding: 16px 24px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s ease;
    border-radius: 8px 8px 0 0;
    margin-right: 4px;
    min-height: auto;

    &.q-tab--active {
      background: white;
      color: #25d366;
      border-bottom: 2px solid #25d366;
    }

    &:hover:not(.q-tab--active) {
      background: #f1f5f9;
      color: #475569;
    }
  }
}
```

## 📱 Design Responsif

### Breakpoints Standards

```scss
// Système de breakpoints cohérent
@media (max-width: 1024px) {
  // Tablette large
}

@media (max-width: 768px) {
  // Tablette / Mobile large
  .card-header {
    padding: 20px 16px;

    .header-content {
      flex-direction: column;
      text-align: center;
      gap: 12px;
    }
  }

  .action-buttons {
    flex-direction: column;

    .action-btn {
      width: 100%;
      min-width: auto;
    }
  }
}

@media (max-width: 480px) {
  // Mobile
  .content-section {
    padding: 20px 12px;
  }
}
```

## 🎨 Animations et Transitions

### Transitions Standards

```scss
// Transitions cohérentes utilisées dans tout le projet
$transition-standard: all 0.2s ease;
$transition-slow: all 0.3s ease;

// Hover effects pour les cartes
.hover-card {
  transition: $transition-slow;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
  }
}

// Hover effects pour les boutons
.hover-button {
  transition: $transition-standard;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
  }
}
```

## 🏗️ Structure des Composants par Type

### 1. Pages de Vue Principales

- **Header avec titre et badges**
- **Navigation par onglets**
- **Contenu en cartes modernes**
- **Sections de statistiques**

### 2. Composants de Formulaire

- **Cartes avec headers gradients**
- **Groupes d'entrée avec labels iconographiques**
- **Boutons d'action centtrés**
- **Feedback visuel avec cartes d'état**

### 3. Composants de Liste/Grille

- **CSS Grid pour layouts responsifs**
- **Cartes uniformes avec hauteur égale**
- **Hover effects subtils**
- **États vides avec icônes et messages**

### 4. Composants de Redirection

- **Headers avec identité visuelle claire**
- **Listes de fonctionnalités avec icônes**
- **Boutons d'action proéminents**
- **Design cohérent avec la couleur de la fonctionnalité**

## 📋 Checklist pour Nouveaux Composants

### ✅ Design

- [ ] Utilise les couleurs de la palette définie
- [ ] Applique les border-radius standards (12px-16px)
- [ ] Inclut les hover effects appropriés
- [ ] Respecte les espacements cohérents (16px, 24px, 32px)
- [ ] Utilise les gradients signature quand approprié

### ✅ Structure

- [ ] Suit la structure de carte moderne si applicable
- [ ] Utilise les classes CSS cohérentes (.modern-card, .input-group, etc.)
- [ ] Implemente le responsive design aux breakpoints standards
- [ ] Inclut les états de chargement/erreur si nécessaire

### ✅ Interaction

- [ ] Transitions fluides pour tous les éléments interactifs
- [ ] Feedback visuel approprié (hover, focus, active)
- [ ] États accessibles (disabled, loading)
- [ ] Navigation intuitive

### ✅ Code

- [ ] Styles SCSS bien organisés et documentés
- [ ] Classes réutilisables privilégiées
- [ ] Variables CSS/SCSS utilisées pour les valeurs récurrentes
- [ ] Code TypeScript type-safe

## 🔄 Évolution et Maintenance

### Principes d'Évolution

1. **Cohérence avant innovation** - Maintenir la cohérence visuelle existante
2. **Amélioration progressive** - Éviter les refactorisations massives
3. **Documentation continue** - Mettre à jour ce guide pour chaque nouveau pattern
4. **Tests d'interaction** - Vérifier les transitions et animations sur différents devices

### Patterns à Éviter

- ❌ Mélange de styles anciens et nouveaux dans le même composant
- ❌ Couleurs en dur dans le CSS (utiliser les variables)
- ❌ Transitions trop rapides (<0.1s) ou trop lentes (>0.5s)
- ❌ Border-radius incohérents
- ❌ Espacements non-standards

### Patterns Recommandés

- ✅ Réutilisation des classes de base (.modern-card, .input-group, etc.)
- ✅ Gradients pour les éléments d'action principaux
- ✅ Icônes avec couleurs sémantiques
- ✅ Feedback visuel cohérent
- ✅ Mobile-first responsive design

## 📚 Ressources et Références

### Outils de Design

- **Couleurs**: [Coolors.co](https://coolors.co) pour les palettes
- **Icônes**: Material Design Icons (via Quasar)
- **Inspirations**: Modern dashboard designs, WhatsApp Business interface

### Documentation Technique

- [Quasar Framework](https://quasar.dev) - Framework UI
- [Vue 3 Composition API](https://vuejs.org) - Framework JavaScript
- [SCSS Documentation](https://sass-lang.com) - Préprocesseur CSS

---

**Note**: Ce guide évoluera avec le projet. Chaque nouveau pattern ou amélioration doit être documenté ici pour maintenir la cohérence et faciliter la collaboration.

_Dernière mise à jour: Décembre 2024_
