# Test Manuel - WhatsApp Message List Refactorisé

## Date : 29/05/2025
## Composant : WhatsAppMessageList (refactorisé)

### ✅ Liste de contrôle des fonctionnalités

#### 1. Affichage initial
- [ ] La page se charge sans erreur
- [ ] La liste des messages s'affiche correctement
- [ ] Les statistiques sont visibles (Total, Reçus, Envoyés, etc.)
- [ ] La table affiche les colonnes correctement

#### 2. Filtres
- [ ] Filtre par numéro de téléphone fonctionne
- [ ] Filtre par statut fonctionne (Envoyé, Livré, Lu, Échoué, Reçu)
- [ ] Filtre par direction fonctionne (Entrant, Sortant)
- [ ] Filtre par date fonctionne avec le date picker
- [ ] Les chips de filtres actifs s'affichent
- [ ] Le bouton "Effacer tout" fonctionne
- [ ] Cliquer sur un numéro dans la table applique le filtre

#### 3. Pagination
- [ ] La pagination s'affiche correctement
- [ ] Navigation entre les pages fonctionne
- [ ] Le label de pagination est correct (ex: "1-20 sur 150")
- [ ] Changement du nombre de lignes par page (10, 20, 50, 100)

#### 4. Actions sur les messages
- [ ] Bouton "Répondre" visible pour les messages entrants < 24h
- [ ] Dialogue de réponse s'ouvre correctement
- [ ] Envoi de réponse fonctionne
- [ ] Bouton "Détails" ouvre le dialogue des détails
- [ ] Dialogue des détails affiche toutes les informations

#### 5. Fonctionnalités globales
- [ ] Bouton "Actualiser" rafraîchit la liste
- [ ] Bouton "Exporter" génère le fichier CSV
- [ ] Rafraîchissement automatique toutes les 30 secondes
- [ ] Les tooltips s'affichent correctement

#### 6. Affichage des différents types de messages
- [ ] Messages texte s'affichent correctement
- [ ] Messages template s'affichent avec leur nom
- [ ] Messages média (image, video, audio, document) ont les bonnes icônes
- [ ] Les statuts ont les bonnes couleurs et icônes

### 🐛 Bugs trouvés
_Liste des problèmes rencontrés pendant les tests_

### 📝 Notes
_Observations et suggestions d'amélioration_