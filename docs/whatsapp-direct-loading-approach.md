# Approche de chargement direct pour les templates WhatsApp

## Introduction

Ce document explique l'approche de "chargement direct" implémentée dans Oracle pour la gestion des templates WhatsApp. Cette approche permet d'obtenir les templates directement depuis l'API Meta sans synchronisation avec une base de données locale.

## Concept

L'approche de chargement direct est basée sur les principes suivants:

1. **Pas de synchronisation locale**: Les templates ne sont jamais stockés dans la base de données locale de l'application.
2. **API comme source de vérité**: L'API Meta Cloud est la source unique de vérité pour les templates disponibles.
3. **Temps réel**: L'application obtient toujours la liste la plus récente des templates approuvés.

## Avantages et inconvénients

### Avantages
- **Toujours à jour**: Les templates sont toujours synchronisés avec les dernières modifications et approbations de Meta.
- **Simplicité**: Pas besoin de gérer la synchronisation ou le stockage local des templates.
- **Maintenance réduite**: Moins de code à maintenir et déployer.
- **Pas de problèmes de synchronisation**: Évite les problèmes quand les templates sont modifiés ou supprimés par Meta.

### Inconvénients
- **Dépendance API**: Dépend de la disponibilité de l'API Meta. Si l'API est indisponible, l'application ne peut pas obtenir les templates.
- **Consommation de quota**: Chaque requête pour obtenir les templates consomme du quota d'API.
- **Latence**: Chaque requête pour obtenir les templates peut introduire une latence.

## Architecture

L'architecture de l'approche de chargement direct est structurée comme suit:

```
┌─────────────────┐     ┌─────────────────┐     ┌────────────────┐
│ WhatsAppService │────>│TemplateService  │────>│  ApiClient     │
└─────────────────┘     └─────────────────┘     └────────────────┘
                                                       │
                                                       ▼
                                                ┌────────────────┐
                                                │    Meta API    │
                                                └────────────────┘
```

- **WhatsAppService**: Point d'entrée principal pour les fonctionnalités WhatsApp.
- **TemplateService**: Gère la récupération et le filtrage des templates.
- **ApiClient**: Gère les communications avec l'API Meta.

## Implémentation

### 1. Récupération des templates

```php
public function fetchApprovedTemplatesFromMeta(array $filters = []): array
{
    try {
        // Récupérer tous les templates depuis l'API Meta
        $allTemplates = $this->apiClient->getTemplates();
        
        // Filtrer pour ne garder que les templates avec statut "APPROVED"
        $approvedTemplates = array_filter($allTemplates, function($template) {
            return isset($template['status']) && $template['status'] === 'APPROVED';
        });
        
        // Appliquer des filtres supplémentaires si fournis
        if (!empty($filters)) {
            // ... application des filtres ...
        }
        
        return array_values($approvedTemplates);
    } catch (\Exception $e) {
        // Gestion des erreurs
        return [];
    }
}
```

### 2. Envoi de message template

```php
public function sendTemplateMessage(
    User $user,
    string $recipient,
    string $templateName,
    string $languageCode,
    ?string $headerImageUrl = null,
    array $bodyParams = []
): WhatsAppMessageHistory {
    // Construction du payload
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $this->normalizePhoneNumber($recipient),
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $languageCode
            ]
        ]
    ];

    // Ajout des composants (header, body, etc.)
    
    // Envoi via l'API
    $result = $this->apiClient->sendMessage($payload);
    
    // Création et enregistrement de l'historique
    $messageHistory = new WhatsAppMessageHistory();
    // ... configuration de l'historique ...
    $this->messageRepository->save($messageHistory);
    
    return $messageHistory;
}
```

## Performance et optimisations

Pour optimiser les performances avec l'approche de chargement direct:

1. **Mise en cache côté client**: Utilisez le cache du navigateur pour stocker temporairement la liste des templates côté client.
2. **Réduire la fréquence des requêtes**: Évitez de recharger les templates à chaque requête utilisateur.
3. **Préchargement**: Chargez les templates au chargement initial de la page.

## Dépannage

Si vous rencontrez des problèmes avec l'approche de chargement direct:

1. **Templates non disponibles**:
   - Vérifiez que les tokens d'accès à l'API Meta sont valides
   - Vérifiez que votre compte WhatsApp Business a des templates approuvés

2. **Erreurs lors de l'envoi de message**:
   - Vérifiez que le template existe et est approuvé
   - Vérifiez que les paramètres fournis correspondent aux attentes du template

## Conclusions

L'approche de chargement direct est une solution simple et efficace pour les applications qui n'ont pas besoin d'une gestion complexe des templates WhatsApp. Elle offre une solution "sans maintenance" qui est toujours à jour avec les dernières modifications de Meta.