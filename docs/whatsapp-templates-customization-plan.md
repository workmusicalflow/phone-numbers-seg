# Plan d'amélioration de la personnalisation des templates WhatsApp

## Contexte et problématique

L'API Cloud de Meta fournit maintenant des informations détaillées sur les templates WhatsApp et leurs composants, comme nous l'avons vérifié via nos tests d'API. Pour offrir une meilleure expérience utilisateur, nous devons exploiter ces informations pour permettre une personnalisation proactive et complète des templates directement dans notre application, sans nécessiter de revalidation par Meta.

## Données disponibles via l'API Cloud Meta

Nos tests ont confirmé que nous pouvons récupérer les informations suivantes pour chaque template :

1. **Statut (APPROVED, PENDING, REJECTED)**
   - Permet de filtrer pour n'afficher que les templates utilisables
   - Tous nos templates actuels sont APPROVED

2. **Catégorie (UTILITY, MARKETING, AUTHENTICATION)**
   - Permet d'organiser et filtrer les templates par cas d'usage
   - Nous avons actuellement des templates dans les catégories UTILITY et MARKETING

3. **Langue (fr, en_US)**
   - Information essentielle pour l'affichage et le filtrage contextuel
   - Actuellement, nous avons des templates en français et en anglais

4. **Composants (HEADER, BODY, FOOTER, BUTTONS)**
   - Structure complète du template avec tous ses éléments
   - Types de composants disponibles par template
   - Pour les HEADER : format spécifique (TEXT, IMAGE, VIDEO, DOCUMENT)
   - Pour les BUTTONS : type et texte de chaque bouton

5. **Variables dans le corps**
   - Nombre et position des variables {{1}}, {{2}}, etc.
   - Importante pour la génération dynamique des champs de saisie

## Éléments personnalisables sans revalidation Meta

1. **Variables de corps ({{1}}, {{2}}, etc.)**
   - Remplacement des variables dans le texte du corps du message
   - Respect des limites de caractères pour chaque variable
   - Support de différents formats (texte, nombres, dates, devises)

2. **Options pour l'en-tête média**
   - Support des différents types de médias (image, vidéo, document)
   - Deux méthodes de référencement des médias:
     - Media ID (obtenu après upload via l'API)
     - URL externe (pour les médias déjà hébergés)
   - Affichage conditionnel basé sur la présence d'un HEADER de type média

3. **Aperçu en temps réel**
   - Visualisation du rendu final du message avec tous ses composants
   - Prévisualisation des médias intégrés
   - Indication visuelle des variables remplies/manquantes
   - Rendu différencié selon la catégorie du template

## Plan d'implémentation

### 1. Intégration des données de l'API Cloud Meta

- Créer un service de synchronisation périodique des templates
- Stocker les détails des templates dans la base de données locale
- Enrichir l'entity `WhatsAppTemplate` avec tous les champs disponibles
- Exposer les données complètes via GraphQL pour le frontend

### 2. Amélioration de l'interface de filtrage et sélection

- Organisation par catégorie avec filtres avancés
- Filtrage par langue, présence de médias, et nombre de variables
- Affichage des badges de status pour chaque template
- Tri intelligent (templates récemment utilisés en premier)
- Recherche textuelle dans le corps des templates

### 3. Amélioration de l'interface pour les variables de corps

- Création dynamique des champs de saisie basée sur les variables détectées
- Types de champs adaptés au contexte (texte, nombre, date, sélection)
- Inclure des compteurs de caractères avec limites appropriées
- Validation contextuelle selon le type attendu des variables
- Suggestions automatiques basées sur l'historique des valeurs utilisées

### 4. Optimisation des options d'en-tête média

#### Interface conditionnelle pour les médias

- Affichage uniquement pour les templates avec un HEADER de format média
- **Option 1**: Upload d'un fichier
  - Processus d'upload communiquant avec l'API WhatsApp
  - Capture et stockage du Media ID retourné
  - Prévisualisation du média uploadé
  - Validation du type de média (image, vidéo, document) selon le format spécifié

- **Option 2**: Saisie d'une URL externe
  - Champ pour saisir une URL accessible publiquement
  - Validation que l'URL est dans un format accepté par l'API
  - Prévisualisation du média référencé par l'URL
  - Vérification de disponibilité de la ressource

- Indication claire du type de référence utilisé (URL vs ID)
- Support des différents formats de média acceptés par WhatsApp

### 5. Amélioration de l'aperçu en temps réel

- Rendu fidèle du format WhatsApp incluant tous les composants
- Prévisualisation des médias sélectionnés (image, vidéo, document)
- Affichage des éléments de footer et des boutons
- Indication visuelle des variables remplies vs vides
- Simulation de l'apparence mobile du message selon le device de l'utilisateur

### 6. Modifications techniques nécessaires

#### Adaptation des store Pinia

- `whatsappStore.ts` : Enrichissement pour gérer les templates avec leurs détails complets
- Nouvelles actions pour la synchronisation et le filtrage avancé
- Stockage local des détails des templates pour réduire les appels API

#### Composant WhatsAppTemplateSelector.vue

- Refonte pour exploiter toutes les informations disponibles sur les templates
- Interface adaptative selon les composants disponibles dans chaque template
- Génération dynamique des champs de formulaire basée sur les variables détectées
- Amélioration de la prévisualisation avec tous les composants du template
- Support des différentes catégories avec styles visuels appropriés

#### Adaptation du payload d'envoi

- Structure complète du payload incluant tous les composants nécessaires
- Format correct pour les variables de corps, médias, et boutons
- Gestion intelligente des cas où à la fois l'ID média et l'URL sont fournis

### 7. Nouvelles fonctionnalités

- Historique des valeurs utilisées pour les templates fréquents
- Suggestions intelligentes pour les variables courantes (noms, formules de politesse)
- Modèles prédéfinis pour des types de messages récurrents
- Programmation d'envoi de templates à des moments spécifiques
- Statistiques d'utilisation et de performance par template

## Avantages de cette approche

1. **Expérience utilisateur optimisée**
   - Interface complètement adaptée aux templates disponibles
   - Exploitation de toutes les informations fournies par l'API
   - Réduction des erreurs d'envoi grâce à la validation proactive

2. **Flexibilité et performances accrues**
   - Support de tous les types de personnalisation permis par l'API
   - Adaptabilité automatique aux différents types de templates
   - Réduction des appels API par le stockage intelligent des données

3. **Meilleure gouvernance**
   - Traçabilité de l'utilisation des templates
   - Analyse des performances par type et catégorie de template
   - Optimisations basées sur des données d'utilisation réelles

## Prochaines étapes

1. Implémenter le service de synchronisation des templates avec l'API Cloud Meta
2. Enrichir les modèles de données et les resolvers GraphQL
3. Refondre le composant WhatsAppTemplateSelector.vue pour exploiter toutes les informations
4. Implémenter l'interface adaptative pour les différents types de templates
5. Développer l'aperçu avancé avec tous les composants du template
6. Tester avec chaque template disponible dans notre compte
7. Mettre en place des outils d'analyse d'utilisation des templates