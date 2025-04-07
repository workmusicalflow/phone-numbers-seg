# Plan d'Implémentation de la Fonctionnalité d'Import/Export CSV

## Objectif

Permettre aux utilisateurs d'importer des numéros de téléphone depuis un fichier CSV et d'exporter les résultats de segmentation vers des formats CSV ou Excel.

## Architecture

### Composants Principaux

1. **Interface Utilisateur**

   - Page d'import CSV
   - Intégration de l'export dans les pages existantes
   - Prévisualisation des données

2. **Services Backend**

   - Service d'import CSV
   - Service d'export CSV/Excel
   - Validation des données

3. **API Endpoints**
   - Endpoint d'import
   - Endpoint d'export

## Plan d'Implémentation

### Phase 1: Import CSV (Backend)

1. **Créer le Service d'Import**

   - Classe `CSVImportService` dans `src/Services/`
   - Méthodes pour lire et parser les fichiers CSV
   - Validation des données (format de numéro, doublons, etc.)
   - Normalisation des numéros de téléphone

2. **Créer l'API Endpoint**

   - Ajouter un endpoint `/api.php?endpoint=import-csv` dans le contrôleur
   - Gérer l'upload de fichier
   - Traiter les erreurs et renvoyer des messages appropriés

3. **Intégrer avec le Service de Segmentation**
   - Connecter l'import avec le service de segmentation existant
   - Gérer le traitement par lot des numéros importés

### Phase 2: Import CSV (Frontend)

1. **Créer la Page d'Import**

   - Nouvelle page `import.html` dans le dossier `public/`
   - Formulaire d'upload de fichier
   - Intégration avec Alpine.js pour la réactivité

2. **Ajouter la Prévisualisation**

   - Afficher un aperçu des données avant l'import final
   - Permettre la sélection des colonnes à importer
   - Validation côté client

3. **Intégrer la Navigation**
   - Ajouter un lien vers la page d'import dans la barre de navigation
   - Mettre à jour les autres pages pour inclure ce lien

### Phase 3: Export (Backend)

1. **Créer le Service d'Export**

   - Classe `ExportService` dans `src/Services/`
   - Support pour les formats CSV et Excel
   - Options de filtrage et de formatage

2. **Créer les API Endpoints**
   - Ajouter un endpoint `/api.php?endpoint=export-csv` pour l'export CSV
   - Ajouter un endpoint `/api.php?endpoint=export-excel` pour l'export Excel
   - Gérer les paramètres de filtrage

### Phase 4: Export (Frontend)

1. **Intégrer l'Export dans les Pages Existantes**

   - Ajouter des boutons d'export dans les pages de résultats
   - Permettre la sélection des données à exporter

2. **Ajouter les Options de Configuration**
   - Filtrage par opérateur, pays, etc.
   - Sélection des colonnes à exporter
   - Options de formatage

## Structure des Fichiers

```
src/
  Services/
    CSVImportService.php
    ExportService.php
  Controllers/
    ImportExportController.php
public/
  import.html
  css/
    import-export.css
  js/
    import-export.js
```

## Format CSV Attendu

Le système devra supporter plusieurs formats de fichiers CSV :

1. **Format Simple** : Une colonne contenant uniquement les numéros de téléphone

   ```
   Numéro
   +2250777104936
   002250141399354
   0546560953
   ```

2. **Format Avancé** : Plusieurs colonnes avec des informations supplémentaires
   ```
   Nom,Prénom,Numéro,Entreprise
   Doe,John,+2250777104936,ACME Inc.
   Smith,Jane,002250141399354,XYZ Corp.
   ```

## Validation des Données

1. **Validation de Format**

   - Vérifier que les numéros sont dans un format valide
   - Normaliser les numéros (ajouter le code pays si manquant, supprimer les caractères spéciaux)

2. **Validation de Contenu**

   - Détecter et gérer les doublons
   - Vérifier les limites (nombre maximum de numéros)

3. **Gestion des Erreurs**
   - Rapport détaillé des erreurs par ligne
   - Option pour ignorer les lignes en erreur ou arrêter l'import

## Interface Utilisateur

### Page d'Import

1. **Étape 1: Upload**

   - Zone de glisser-déposer pour le fichier CSV
   - Option pour télécharger un modèle de fichier CSV

2. **Étape 2: Prévisualisation et Configuration**

   - Tableau montrant les premières lignes du fichier
   - Sélection des colonnes à utiliser
   - Options de validation

3. **Étape 3: Import et Résultats**
   - Barre de progression
   - Résumé de l'import (nombre de numéros importés, erreurs)
   - Option pour segmenter immédiatement les numéros importés

### Fonctionnalité d'Export

1. **Boutons d'Export**

   - Ajouter des boutons "Exporter en CSV" et "Exporter en Excel" dans les pages de résultats

2. **Modal de Configuration**
   - Options de filtrage
   - Sélection des colonnes à exporter
   - Format de date et autres options

## Considérations Techniques

1. **Performance**

   - Traitement par lots pour les grands fichiers
   - Utilisation de streams pour minimiser l'utilisation de la mémoire
   - Indicateurs de progression pour les opérations longues

2. **Sécurité**

   - Validation des types de fichiers
   - Limitation de la taille des fichiers
   - Protection contre les injections

3. **Compatibilité**
   - Support des différents encodages (UTF-8, ISO-8859, etc.)
   - Support des différents séparateurs (virgule, point-virgule, tabulation)
   - Support des différents formats de ligne (Windows, Unix, Mac)

## Prochaines Étapes

1. Implémenter le service d'import CSV backend
2. Créer la page d'import frontend
3. Tester avec différents formats de fichiers
4. Implémenter l'export CSV/Excel
5. Intégrer l'export dans les pages existantes
