# Test Manuel - Envoi en Masse WhatsApp

## Prérequis
- Serveurs backend et frontend déjà en cours d'exécution
- Au moins un template WhatsApp approuvé dans la base
- Quelques contacts de test dans la base
- Compte utilisateur avec crédits WhatsApp

## Tests à effectuer

### Test 1 : Ouverture du dialog
- [ ] Aller sur la page WhatsApp
- [ ] Cliquer sur "Envoi en masse"
- [ ] Vérifier que le dialog s'ouvre correctement

### Test 2 : Sélection du template
- [ ] Sélectionner un template dans la liste
- [ ] Vérifier que les paramètres apparaissent si nécessaire

### Test 3 : Ajout de destinataires (Saisie manuelle)
- [ ] Onglet "Saisie manuelle"
- [ ] Entrer 2-3 numéros valides (+225XXXXXXXXXX)
- [ ] Vérifier que le compteur se met à jour

### Test 4 : Import CSV
- [ ] Onglet "Import CSV"
- [ ] Créer un fichier CSV avec quelques numéros
- [ ] Importer le fichier
- [ ] Vérifier que les numéros sont détectés

### Test 5 : Envoi avec succès
- [ ] Ajouter 2-3 destinataires
- [ ] Sélectionner un template
- [ ] Cliquer "Envoyer"
- [ ] Vérifier la barre de progression
- [ ] Vérifier le message de succès

### Test 6 : Test de la limite
- [ ] Essayer d'ajouter plus de 500 destinataires
- [ ] Vérifier que l'erreur s'affiche correctement

### Test 7 : Options avancées
- [ ] Ouvrir les options avancées
- [ ] Modifier la taille du batch
- [ ] Vérifier que les options sont prises en compte

## 3. Points de vérification

- ✅ Interface responsive
- ✅ Messages d'erreur clairs
- ✅ Progression visible
- ✅ Statistiques finales correctes
- ✅ Possibilité de fermer et rouvrir

## 4. Cas d'erreur à tester

1. **Sans connexion** : Vérifier le message d'erreur
2. **Numéros invalides** : Vérifier la validation
3. **Template manquant** : Impossible d'envoyer
4. **Crédits insuffisants** : Message d'erreur approprié