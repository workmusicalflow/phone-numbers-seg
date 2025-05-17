# Configuration des abonnements webhook WhatsApp

Ce document détaille les différents types d'abonnements disponibles pour les webhooks WhatsApp et leur utilité.

## Champs d'abonnement disponibles

| Nom du champ | Description | Utilité |
|--------------|-------------|---------|
| `messages` | Notifications lorsque des messages sont reçus | Essentiel pour recevoir des messages des utilisateurs |
| `message_template_status_update` | Notifications sur les changements de statut des templates | Savoir quand un template est approuvé/rejeté |
| `message_template_quality_update` | Informations sur la qualité des templates | Améliorer les templates sur la base des métriques |
| `phone_number_quality_update` | Informations sur la qualité du numéro de téléphone | Suivre la santé/réputation du numéro |
| `phone_number_name_update` | Notifications sur les changements de nom du numéro | Savoir quand un nom d'affichage est modifié |
| `account_update` | Notifications sur les mises à jour du compte | Suivre l'état général du compte |
| `message_echoes` | Copies des messages sortants | Utile pour la journalisation complète des conversations |
| `business_status_update` | Changements d'état de l'entreprise | Suivre les modifications de statut du compte business |
| `account_review_update` | Mises à jour des processus de vérification | Informations sur les processus de révision |
| `security` | Alertes de sécurité | Notifications de problèmes de sécurité potentiels |

## Recommandations pour l'implémentation

### Configuration minimale
Pour une implémentation de base, nous recommandons de s'abonner au minimum à ces champs :
- `messages` - Pour recevoir les messages des utilisateurs
- `message_template_status_update` - Pour suivre l'approbation des templates
- `phone_number_quality_update` - Pour surveiller la santé du numéro

### Configuration complète
Pour une implémentation plus complète avec suivi détaillé, nous recommandons ces champs additionnels :
- `message_echoes` - Pour une journalisation complète des conversations
- `account_update` - Pour suivre l'état du compte
- `business_status_update` - Pour être informé des changements de statut

## Implémentation technique

Pour chaque type d'abonnement, il faudra étendre le webhook controller pour gérer le type spécifique de données. Voici un exemple d'implémentation pour les différents types :

```php
// Dans WebhookController.php, méthode processChange

switch ($change['field']) {
    case 'messages':
        // Traitement des messages (déjà implémenté)
        $this->processMessages($change['value']);
        break;
    
    case 'message_template_status_update':
        $this->processTemplateStatusUpdate($change['value']);
        break;
    
    case 'phone_number_quality_update':
        $this->processPhoneNumberQualityUpdate($change['value']);
        break;
    
    // Ajouter d'autres cas selon les besoins
    
    default:
        $this->logger->info('Type de changement non géré', [
            'field' => $change['field']
        ]);
        break;
}
```

## Gestion des erreurs et limites

- **Rate Limiting** : Soyez conscient que Meta peut imposer des limites sur le nombre de webhooks envoyés par minute
- **Timeout** : Votre webhook doit répondre dans les 20 secondes, sinon Meta considérera l'appel comme échoué
- **Retry Logic** : Meta peut réessayer d'envoyer des webhooks si votre serveur ne répond pas avec un HTTP 200

## À faire

1. Configurer les abonnements dans le tableau de bord Meta en commençant par les 3 recommandés
2. Étendre le WebhookController pour gérer chaque type de notification
3. Créer des entités et repositories pour stocker les données pertinentes
4. Implémenter des mécanismes de notification interne pour alerter les administrateurs des changements importants