# Guide des Formats d'Export de Numéros de Téléphone

Ce document décrit les formats disponibles pour l'export de numéros de téléphone depuis l'application de segmentation.

## Formats d'Export Disponibles

L'application prend en charge l'export des numéros de téléphone et de leurs segments dans deux formats principaux :

### 1. CSV (Comma-Separated Values)

- Format texte simple avec valeurs séparées par des virgules
- Compatible avec la plupart des tableurs (Excel, Google Sheets, LibreOffice Calc)
- Idéal pour le traitement ultérieur des données ou l'import dans d'autres systèmes

### 2. Excel (XLSX)

- Format natif Microsoft Excel
- Permet une mise en forme avancée (couleurs, formatage conditionnel)
- Meilleure lisibilité pour les utilisateurs finaux
- Support des feuilles multiples pour organiser les données

## Structure des Fichiers Exportés

Les fichiers exportés contiennent les informations suivantes :

### En-têtes de colonnes

1. **ID** : Identifiant unique du numéro dans la base de données
2. **Numéro** : Numéro de téléphone au format international (+XXX...)
3. **Civilité** : Civilité du contact (M., Mme, Mlle, etc.)
4. **Prénom** : Prénom du contact
5. **Nom** : Nom de famille du contact
6. **Entreprise** : Nom de l'entreprise associée au contact
7. **Secteur** : Secteur d'activité du contact
8. **Code Pays** : Code du pays identifié (ex: 225 pour la Côte d'Ivoire)
9. **Pays** : Nom du pays identifié
10. **Opérateur** : Nom de l'opérateur téléphonique identifié
11. **Type** : Type de numéro (mobile, fixe)
12. **Segments Personnalisés** : Segments personnalisés associés au numéro
13. **Date d'Ajout** : Date à laquelle le numéro a été ajouté à la base de données
14. **Notes** : Informations supplémentaires sur le contact

### Exemple de fichier CSV exporté

```csv
ID,Numéro,Civilité,Prénom,Nom,Entreprise,Secteur,Code Pays,Pays,Opérateur,Type,Segments Personnalisés,Date d'Ajout,Notes
1,+2250777104936,M.,Jean,Dupont,ABC Corp,Technologie,225,Côte d'Ivoire,Orange,Mobile,Client VIP,2025-03-15,Client depuis 2020
2,+2250141399354,Mme,Marie,Martin,XYZ SA,Finance,225,Côte d'Ivoire,MTN,Mobile,Prospect,2025-03-16,Nouveau client
3,+2250546560953,Mlle,Sophie,Traoré,Indépendant,Santé,225,Côte d'Ivoire,Moov,Mobile,Client;Santé,2025-03-17,Contact prioritaire
```

### Structure du fichier Excel

Le fichier Excel exporté contient les mêmes données que le fichier CSV, mais avec les améliorations suivantes :

- Formatage des en-têtes en gras
- Coloration alternée des lignes pour une meilleure lisibilité
- Ajustement automatique de la largeur des colonnes
- Filtres automatiques sur les en-têtes pour faciliter le tri et le filtrage
- Feuille supplémentaire avec des statistiques (nombre de numéros par opérateur, par pays, etc.)

## Options de Filtrage pour l'Export

L'application permet de filtrer les numéros à exporter selon plusieurs critères :

### Filtres de Base

- **Recherche textuelle** : Filtrer les numéros contenant un texte spécifique
- **Limite** : Nombre maximum de numéros à exporter
- **Offset** : Point de départ pour l'export (pour la pagination)

### Filtres Avancés

- **Par opérateur** : Exporter uniquement les numéros d'un opérateur spécifique (Orange, MTN, Moov, etc.)
- **Par pays** : Exporter uniquement les numéros d'un pays spécifique
- **Par date d'ajout** : Exporter les numéros ajoutés dans une plage de dates spécifique
- **Par segment personnalisé** : Exporter uniquement les numéros associés à un segment personnalisé spécifique

## Accès à la Fonctionnalité d'Export

### Via l'Interface Web

1. Accédez à la page "Import/Export" depuis le menu principal
2. Sélectionnez l'onglet "Export"
3. Configurez les options de filtrage selon vos besoins
4. Choisissez le format d'export (CSV ou Excel)
5. Cliquez sur le bouton "Exporter"

### Via l'API GraphQL

L'application expose également des requêtes GraphQL pour l'export programmatique :

#### Export CSV

```graphql
mutation ExportCSV($filters: PhoneNumberFilters) {
  exportCSV(filters: $filters) {
    success
    message
    fileUrl
    totalCount
  }
}
```

#### Export Excel

```graphql
mutation ExportExcel($filters: PhoneNumberFilters) {
  exportExcel(filters: $filters) {
    success
    message
    fileUrl
    totalCount
  }
}
```

#### Structure des filtres

```graphql
input PhoneNumberFilters {
  search: String
  limit: Int
  offset: Int
  operator: String
  country: String
  dateFrom: String
  dateTo: String
  segment: String
}
```

## Limites et Considérations

- **Taille maximale** : L'export est limité à 50 000 numéros par opération pour des raisons de performance
- **Temps d'export** : L'export de grands volumes de données peut prendre plusieurs minutes
- **Encodage** : Les fichiers CSV sont exportés en UTF-8 pour assurer la compatibilité avec les caractères spéciaux
- **Compatibilité Excel** : Les fichiers CSV peuvent nécessiter une configuration spécifique lors de l'ouverture dans Excel pour gérer correctement les caractères UTF-8

## Résolution des Problèmes Courants

### Le fichier CSV ne s'ouvre pas correctement dans Excel

- Dans Excel, utilisez "Données" > "À partir d'un texte" au lieu d'ouvrir directement le fichier
- Spécifiez l'encodage UTF-8 et le délimiteur virgule lors de l'import

### L'export est trop lent

- Réduisez le nombre de numéros à exporter en utilisant des filtres plus restrictifs
- Divisez l'export en plusieurs opérations plus petites en utilisant les paramètres limit et offset

### Certaines données sont manquantes dans l'export

- Vérifiez que les numéros ont bien été segmentés avant l'export
- Assurez-vous que les filtres appliqués ne sont pas trop restrictifs
