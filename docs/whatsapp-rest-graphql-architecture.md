# Architecture WhatsApp : Migration vers REST

## Évolution de l'architecture

### Architecture initiale (mixte REST-GraphQL)

L'intégration initiale avec l'API Cloud de Meta pour WhatsApp Business utilisait une approche hybride :

1. **REST pour le chargement des templates** : Endpoints dédiés pour récupérer les templates approuvés
2. **GraphQL pour l'envoi des templates** : Mutation `sendWhatsAppTemplateV2` pour personnaliser et envoyer les templates

Cette approche mixte a créé plusieurs problèmes :
- Erreurs GraphQL difficiles à déboguer
- Inconsistances entre les modèles REST et GraphQL
- Erreur récurrente : `Call to undefined method App\GraphQL\Types\WhatsApp\SendTemplateInput::getHeaderMediaId()`
- Complexité accrue pour les développeurs

### Nouvelle architecture (REST exclusive)

Suite aux problèmes rencontrés, nous avons migré vers une architecture **entièrement REST** pour la gestion des templates WhatsApp :

1. **REST pour tout** : Aussi bien pour le chargement des templates que pour leur envoi
2. **Client TypeScript dédié** : `whatsAppRestClient.ts` pour toutes les opérations liées aux templates
3. **Endpoints REST spécialisés** : `/api/whatsapp/send-template-v2.php` optimisé pour l'envoi de templates

## Endpoints REST WhatsApp

L'architecture actuelle s'appuie sur les endpoints REST suivants :

```
GET    /api/whatsapp/templates/approved.php      # Récupérer tous les templates approuvés
POST   /api/whatsapp/send-template-v2.php        # Envoyer un template WhatsApp avec médias
POST   /api/whatsapp/upload.php                  # Uploader un média pour WhatsApp
```

## Client REST TypeScript

Le client REST est implémenté dans `frontend/src/services/whatsappRestClient.ts` et offre :

```typescript
// Client REST pour l'API WhatsApp
export class WhatsAppRestClient {
  // Récupérer les templates
  async getApprovedTemplates(filters?: TemplateFilters): Promise<ApprovedTemplatesResponse>;
  
  // Envoyer un template
  async sendTemplateMessageV2(data: {
    recipientPhoneNumber: string;
    templateName: string;
    templateLanguage: string;
    headerMediaUrl?: string;
    headerMediaId?: string;
    bodyVariables?: string[];
    buttonVariables?: string[];
  }): Promise<{
    success: boolean;
    messageId?: string;
    timestamp?: string;
    error?: string;
  }>;
}

// Instance exportée
export const whatsAppClient = new WhatsAppRestClient();
```

## Avantages de l'approche REST exclusive

1. **Simplicité** :
   - Approche architecturale cohérente
   - Meilleure clarté pour les développeurs
   - Élimination des redondances entre GraphQL et REST

2. **Robustesse** :
   - Réduction des erreurs liées aux inconsistances de modèles
   - Meilleure gestion des erreurs et récupération
   - Modèle de données unique entre frontend et backend

3. **Maintenabilité** :
   - Réduction du code à maintenir
   - Plus facile à déployer et tester
   - Documentation centralisée

4. **Performance** :
   - Réduction de la taille du bundle GraphQL
   - Optimisation des échanges client-serveur
   - Possibilité de mise en cache HTTP standard

## Implementation backend

### Endpoint REST pour l'envoi de templates

```php
<?php
// Fichier: /api/whatsapp/send-template-v2.php

// Récupération des données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validation des champs requis
if (!isset($input['recipientPhoneNumber']) || !isset($input['templateName']) || !isset($input['templateLanguage'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs requis manquants', 'success' => false]);
    exit;
}

// Récupérer les services WhatsApp
$container = require __DIR__ . '/../../src/bootstrap.php';
$whatsappService = $container->get(WhatsAppServiceInterface::class);
$systemUser = $container->get(EntityManager::class)->getRepository(User::class)->findOneBy(['email' => 'system@oracle.ci']);

// Envoyer le message avec le service approprié
try {
    // Gestion des médias (URL ou ID)
    $headerMediaUrl = $input['headerMediaUrl'] ?? null;
    $headerMediaId = $input['headerMediaId'] ?? null;
    $bodyVariables = $input['bodyVariables'] ?? [];
    
    // Préparer les composants si fournis
    $components = null;
    if (!empty($input['templateComponentsJsonString'])) {
        $components = json_decode($input['templateComponentsJsonString'], true);
    }
    
    $result = null;
    if ($headerMediaId) {
        // Avec Media ID
        $result = $whatsappService->sendTemplateMessageWithComponents(
            $systemUser,
            $input['recipientPhoneNumber'],
            $input['templateName'],
            $input['templateLanguage'],
            $components,
            $headerMediaId
        );
    } else {
        // Avec URL d'image ou sans média
        $result = $whatsappService->sendTemplateMessage(
            $systemUser,
            $input['recipientPhoneNumber'],
            $input['templateName'],
            $input['templateLanguage'],
            $headerMediaUrl,
            $bodyVariables
        );
    }
    
    echo json_encode([
        'success' => true,
        'messageId' => $result->getWabaMessageId(),
        'timestamp' => (new DateTime())->format('c')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Erreur lors de l\'envoi du template: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

## Migration depuis GraphQL

Les éléments GraphQL suivants ont été supprimés ou dépréciés :

1. **Mutation `sendWhatsAppTemplateV2`** : Supprimée du contrôleur GraphQL
2. **Type `SendTemplateInput`** : Simplifié et marqué comme déprécié
3. **Type `SendTemplateResult`** : Simplifié et marqué comme déprécié
4. **Méthode `sendTemplateV2`** : Retirée du store WhatsApp

## Guide d'utilisation pour les développeurs

### Dans les composants Vue

```typescript
// Importez le client REST
import { whatsAppClient } from '@/services/whatsappRestClient';

// Fonction pour envoyer un template
async function sendTemplate() {
  try {
    const response = await whatsAppClient.sendTemplateMessageV2({
      recipientPhoneNumber: '+XXXXXXXXXXXX',
      templateName: 'nom_du_template',
      templateLanguage: 'fr',
      bodyVariables: ['variable1', 'variable2'],
      // Pour les templates avec média d'en-tête
      headerMediaUrl: 'https://exemple.com/image.jpg',
      // OU
      headerMediaId: 'media-id-de-meta'
    });
    
    if (response.success) {
      // Traitement en cas de succès
      console.log(`Message envoyé avec ID: ${response.messageId}`);
    } else {
      // Gestion des erreurs
      console.error(`Erreur: ${response.error}`);
    }
  } catch (error) {
    console.error('Exception lors de l\'envoi:', error);
  }
}
```

## Conclusion

La migration vers une architecture REST exclusive pour les templates WhatsApp a considérablement simplifié le code, amélioré la robustesse et réduit les erreurs. Cette approche unifiée facilite la maintenance et les évolutions futures.

**Points clés :**
1. Une seule approche (REST) pour toutes les opérations liées aux templates
2. Client TypeScript dédié avec types fortement typés
3. Meilleure gestion des erreurs et des cas particuliers (médias)
4. Simplification de la base de code

---

*Documentation mise à jour le 21/05/2025 dans le cadre de la migration vers l'architecture REST exclusive.*