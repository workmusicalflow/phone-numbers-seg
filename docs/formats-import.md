# Guide des Formats d'Import de Numéros de Téléphone

Ce document décrit les formats acceptés pour l'import de numéros de téléphone dans l'application de segmentation.

## Import CSV

L'application prend en charge l'import de numéros de téléphone à partir de fichiers CSV (Comma-Separated Values). Voici les spécifications pour un import réussi :

### Format du fichier

- Extension : `.csv`
- Encodage : UTF-8
- Délimiteurs supportés : virgule (`,`), point-virgule (`;`), tabulation (`\t`)
- Avec ou sans ligne d'en-tête

### Colonnes

L'application détecte automatiquement les colonnes en fonction de leurs noms (si une ligne d'en-tête est présente) ou vous permet de les spécifier manuellement.

#### Colonne obligatoire

- **Numéro de téléphone** : Le numéro de téléphone à importer et segmenter.
  - Formats acceptés :
    - International avec préfixe `+` (ex: `+22507XXXXXXX`)
    - International avec préfixe `00` (ex: `0022507XXXXXXX`)
    - National (ex: `07XXXXXXX`)
  - Longueur minimale : 8 chiffres
  - Longueur maximale : 15 chiffres

#### Colonnes optionnelles

- **Civilité** : M., Mme, Mlle, etc.
- **Prénom** : Prénom du contact
- **Nom** : Nom de famille du contact
- **Entreprise** : Nom de l'entreprise associée au contact
- **Secteur** : Secteur d'activité du contact
- **Notes** : Informations supplémentaires sur le contact

### Exemple de fichier CSV

```csv
Numéro,Civilité,Prénom,Nom,Entreprise,Secteur,Notes
+2250777104936,M.,Jean,Dupont,ABC Corp,Technologie,Client depuis 2020
002250141399354,Mme,Marie,Martin,XYZ SA,Finance,Nouveau client
0546560953,Mlle,Sophie,Traoré,Indépendant,Santé,Contact prioritaire
```

### Détection automatique

L'application tente de détecter automatiquement :

1. Le délimiteur utilisé (virgule, point-virgule ou tabulation)
2. La présence d'une ligne d'en-tête
3. Les colonnes correspondant aux champs attendus (numéro, civilité, prénom, etc.)

## Import Texte

L'application permet également d'importer des numéros de téléphone à partir d'un texte brut.

### Formats acceptés

Les numéros peuvent être séparés par :

- Des virgules (`,`)
- Des points-virgules (`;`)
- Des sauts de ligne (`\n`)
- Des espaces

### Exemple d'import texte

```
+2250777104936
002250141399354
0546560953
```

ou

```
+2250777104936, 002250141399354, 0546560953
```

## Options d'import

Lors de l'import, vous pouvez configurer les options suivantes :

### Pour l'import CSV

- **Le fichier contient une ligne d'en-tête** : Indique si la première ligne du fichier contient les noms des colonnes.
- **Colonne contenant les numéros de téléphone** : Spécifie quelle colonne contient les numéros à importer.
- **Mappage des colonnes additionnelles** : Permet d'associer les colonnes du fichier aux champs de l'application.
- **Ignorer les numéros invalides** : Si activé, les numéros mal formatés seront ignorés au lieu de provoquer une erreur.
- **Segmenter immédiatement les numéros** : Si activé, les numéros seront automatiquement analysés pour identifier le code pays, l'opérateur, etc.

### Pour l'import texte

- **Ignorer les numéros invalides** : Si activé, les numéros mal formatés seront ignorés.
- **Segmenter immédiatement les numéros** : Si activé, les numéros seront automatiquement analysés.

## Validation des numéros

L'application effectue une validation des numéros de téléphone avant l'import :

- Vérification du format (présence de caractères non numériques autres que `+`)
- Vérification de la longueur (minimum 8 chiffres, maximum 15 chiffres)
- Vérification du préfixe international (si présent)

Si l'option "Ignorer les numéros invalides" est désactivée, l'import sera interrompu si des numéros invalides sont détectés. Sinon, ces numéros seront simplement ignorés et l'import continuera avec les numéros valides.

## Limites et considérations

- **Taille maximale recommandée** : 10 MB pour les fichiers CSV
- **Nombre maximal recommandé de numéros** : 50 000 par import
- **Performance** : L'import de fichiers volumineux peut prendre plusieurs minutes
- **Doublons** : Les numéros déjà présents dans la base de données seront ignorés

## Résolution des problèmes courants

### Le fichier CSV n'est pas reconnu

- Vérifiez que l'extension du fichier est bien `.csv`
- Vérifiez que l'encodage est UTF-8
- Essayez d'utiliser un délimiteur standard (virgule ou point-virgule)

### Certains numéros sont ignorés

- Vérifiez que les numéros respectent l'un des formats acceptés
- Vérifiez qu'ils ont au moins 8 chiffres
- Assurez-vous que l'option "Ignorer les numéros invalides" est activée

### L'import est lent

- Réduisez la taille du fichier en le divisant en plusieurs fichiers plus petits
- Désactivez l'option "Segmenter immédiatement les numéros" et effectuez la segmentation ultérieurement
