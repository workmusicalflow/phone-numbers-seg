# Guide d'implémentation pour l'API WhatsApp Meta Cloud

## Date: 21 mai 2025

Ce document fournit des instructions détaillées sur l'implémentation correcte de l'intégration de l'API WhatsApp Meta Cloud dans notre application Oracle.

## Sommaire
1. [Structure des endpoints](#structure-des-endpoints)
2. [Authentification](#authentification)
3. [Récupération des templates](#récupération-des-templates)
4. [Envoi de messages](#envoi-de-messages)
5. [Gestion des médias](#gestion-des-médias)
6. [Implementations dans l'application](#implementations-dans-lapplication)
7. [Tests et validation](#tests-et-validation)

## Structure des endpoints

L'API WhatsApp Meta Cloud utilise différents endpoints selon l'opération. Il est **crucial** de respecter cette structure:

### 1. Informations du compte WABA
```
GET https://graph.facebook.com/v22.0/{WABA_ID}
```

### 2. Récupération des templates
```
GET https://graph.facebook.com/v22.0/{WABA_ID}/message_templates
```

### 3. Envoi de messages
```
POST https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/messages
```

### 4. Upload de médias
```
POST https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/media
```

> **ATTENTION**: Ne pas confondre WABA_ID et PHONE_NUMBER_ID, ils ont des usages différents selon l'opération.

## Authentification

Toutes les requêtes nécessitent un token d'accès valide:

```
Authorization: Bearer {ACCESS_TOKEN}
```

Le token doit avoir les permissions suivantes:
- `whatsapp_business_messages`
- `whatsapp_business_management`

## Récupération des templates

### Requête correcte
```bash
curl -X GET "https://graph.facebook.com/v22.0/{WABA_ID}/message_templates" \
    -H "Authorization: Bearer {ACCESS_TOKEN}"
```

### Paramètres optionnels
- `limit`: Nombre maximum de templates à retourner (défaut: 20)
- `status`: Filter par statut (`APPROVED`, `PENDING`, etc.)

### Traitement de la réponse
La réponse contient un tableau de templates dans `data`:

```json
{
  "data": [
    {
      "name": "connection_check",
      "language": "fr",
      "status": "APPROVED",
      "category": "UTILITY",
      "components": [
        {
          "type": "BODY",
          "text": "Bonjour, votre connexion WhatsApp est active et fonctionnelle."
        }
      ]
    },
    // Plus de templates...
  ]
}
```

## Envoi de messages

### Message template simple
```bash
curl -X POST "https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/messages" \
    -H "Authorization: Bearer {ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "+2250777104936",
        "type": "template",
        "template": {
            "name": "connection_check",
            "language": {
                "code": "fr"
            }
        }
    }'
```

### Template avec paramètres
```bash
curl -X POST "https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/messages" \
    -H "Authorization: Bearer {ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "+2250777104936",
        "type": "template",
        "template": {
            "name": "appointment_reminder",
            "language": {
                "code": "fr"
            },
            "components": [
                {
                    "type": "body",
                    "parameters": [
                        {
                            "type": "text",
                            "text": "John Doe"
                        },
                        {
                            "type": "text",
                            "text": "10/06/2025"
                        }
                    ]
                }
            ]
        }
    }'
```

### Template avec image
```bash
curl -X POST "https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/messages" \
    -H "Authorization: Bearer {ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "+2250777104936",
        "type": "template",
        "template": {
            "name": "qshe_invitation1",
            "language": {
                "code": "fr"
            },
            "components": [
                {
                    "type": "header",
                    "parameters": [
                        {
                            "type": "image",
                            "image": {
                                "link": "https://example.com/image.jpg"
                            }
                        }
                    ]
                }
            ]
        }
    }'
```

### Mode debug
Ajoutez `?debug=all` à l'URL pour tester sans envoyer réellement:

```
POST https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/messages?debug=all
```

## Gestion des médias

### Upload de média (méthode form-data)
```bash
curl -X POST "https://graph.facebook.com/v22.0/{PHONE_NUMBER_ID}/media" \
     -H "Authorization: Bearer {ACCESS_TOKEN}" \
     -F messaging_product=whatsapp \
     -F file=@/chemin/vers/fichier.jpg \
     -F type=image/jpeg
```

### Utilisation des URLs (recommandé)
Pour simplifier, utilisez directement des URLs dans les templates:

```json
{
    "parameters": [
        {
            "type": "image",
            "image": {
                "link": "https://example.com/image.jpg" 
            }
        }
    ]
}
```

## Implementations dans l'application

### 1. Configuration du service WhatsAppService

```php
class WhatsAppService implements WhatsAppServiceInterface
{
    private $apiClient;
    private $logger;
    private $repository;
    
    public function __construct(
        WhatsAppApiClientInterface $apiClient,
        LoggerInterface $logger,
        WhatsAppTemplateRepositoryInterface $repository
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->repository = $repository;
    }
    
    public function getApprovedTemplates(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            try {
                $templates = $this->apiClient->getTemplates('APPROVED');
                $this->repository->saveTemplates($templates);
                return $templates;
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la récupération des templates: ' . $e->getMessage());
                // Fallback aux templates en cache
            }
        }
        
        // Récupération depuis le cache/BD
        $templates = $this->repository->findApprovedTemplates();
        if (empty($templates) && !$forceRefresh) {
            // Si cache vide, essayer de récupérer depuis l'API
            return $this->getApprovedTemplates(true);
        }
        
        return $templates;
    }
    
    public function sendTemplateMessage(
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        try {
            $this->logger->info('Envoi de template', [
                'recipient' => $recipient,
                'template' => $templateName,
                'language' => $languageCode
            ]);
            
            $result = $this->apiClient->sendTemplateMessage(
                $recipient,
                $templateName,
                $languageCode,
                $components
            );
            
            $this->logger->info('Message envoyé avec succès', [
                'messageId' => $result['messages'][0]['id'] ?? null,
                'status' => $result['messages'][0]['message_status'] ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du template', [
                'error' => $e->getMessage(),
                'recipient' => $recipient,
                'template' => $templateName
            ]);
            
            throw $e;
        }
    }
}
```

### 2. Implémentation du client API

```php
class WhatsAppApiClient implements WhatsAppApiClientInterface
{
    private $httpClient;
    private $config;
    private $logger;
    
    public function __construct(
        HttpClientInterface $httpClient,
        array $config,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->logger = $logger;
    }
    
    public function getTemplates(string $status = null): array
    {
        $url = $this->buildUrl($this->config['waba_id'] . '/message_templates');
        $params = ['limit' => 1000];
        
        if ($status) {
            $params['status'] = $status;
        }
        
        $response = $this->makeRequest('GET', $url, ['query' => $params]);
        return $response['data'] ?? [];
    }
    
    public function sendTemplateMessage(
        string $recipient,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        $url = $this->buildUrl($this->config['phone_number_id'] . '/messages');
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];
        
        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }
        
        return $this->makeRequest('POST', $url, ['json' => $payload]);
    }
    
    private function buildUrl(string $endpoint): string
    {
        return sprintf(
            'https://graph.facebook.com/v%s/%s',
            $this->config['api_version'],
            $endpoint
        );
    }
    
    private function makeRequest(string $method, string $url, array $options = []): array
    {
        $options['headers'] = [
            'Authorization' => 'Bearer ' . $this->config['access_token'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        $this->logger->debug('Requête API WhatsApp', [
            'method' => $method,
            'url' => $url,
            'options' => array_filter($options, function ($key) {
                return $key !== 'headers'; // Ne pas logger les headers sensibles
            }, ARRAY_FILTER_USE_KEY)
        ]);
        
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur API WhatsApp', [
                'error' => $e->getMessage(),
                'method' => $method,
                'url' => $url
            ]);
            
            throw $e;
        }
    }
}
```

### 3. Configuration GraphQL pour les templates WhatsApp

```php
// src/GraphQL/Types/WhatsApp/WhatsAppTemplateType.php
class WhatsAppTemplateType
{
    /**
     * @Field()
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * @Field()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @Field()
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * @Field()
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    /**
     * @Field()
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }
    
    /**
     * @Field()
     */
    public function getComponents(): array
    {
        return $this->components;
    }
}

// src/GraphQL/Resolvers/WhatsApp/WhatsAppTemplateResolver.php
class WhatsAppTemplateResolver
{
    /**
     * @Query()
     * @return WhatsAppTemplateType[]
     */
    public function getApprovedWhatsAppTemplates(bool $forceRefresh = false): array
    {
        return $this->whatsAppService->getApprovedTemplates($forceRefresh);
    }
    
    /**
     * @Mutation()
     */
    public function sendWhatsAppTemplate(
        string $recipient,
        string $templateName,
        string $languageCode,
        ?array $components = null
    ): SendMessageResultType {
        try {
            $result = $this->whatsAppService->sendTemplateMessage(
                $recipient,
                $templateName,
                $languageCode,
                $components ?? []
            );
            
            return new SendMessageResultType(
                $result['messages'][0]['id'] ?? null,
                $result['messages'][0]['message_status'] ?? 'unknown',
                null
            );
        } catch (\Exception $e) {
            return new SendMessageResultType(
                null,
                'error',
                $e->getMessage()
            );
        }
    }
}
```

## Tests et validation

### Test de récupération des templates

```bash
#!/bin/bash
# scripts/test-whatsapp-templates.sh

source .env.whatsapp

echo "Test de récupération des templates WhatsApp..."
curl -s -X GET "https://graph.facebook.com/v${WHATSAPP_API_VERSION}/${WHATSAPP_WABA_ID}/message_templates" \
    -H "Authorization: Bearer ${WHATSAPP_ACCESS_TOKEN}" | jq .
```

### Test d'envoi de message

```bash
#!/bin/bash
# scripts/test-whatsapp-send.sh

source .env.whatsapp

if [ -z "$1" ]; then
  echo "Usage: $0 <recipient_phone_number>"
  exit 1
fi

RECIPIENT=$1

echo "Envoi d'un template WhatsApp de test..."
curl -s -X POST "https://graph.facebook.com/v${WHATSAPP_API_VERSION}/${WHATSAPP_PHONE_NUMBER_ID}/messages" \
    -H "Authorization: Bearer ${WHATSAPP_ACCESS_TOKEN}" \
    -H "Content-Type: application/json" \
    -d '{
        "messaging_product": "whatsapp",
        "to": "'$RECIPIENT'",
        "type": "template",
        "template": {
            "name": "connection_check",
            "language": {
                "code": "fr"
            }
        }
    }' | jq .
```

### Points à vérifier

1. **Identifiants corrects**:
   - WABA_ID pour la récupération des templates
   - PHONE_NUMBER_ID pour l'envoi de messages

2. **Format des numéros de téléphone**:
   - Format international avec préfixe pays: `+2250777104936`
   - Sans espaces ni caractères spéciaux

3. **Structure des templates**:
   - Respecter la structure exacte des composants (header, body, footer, buttons)
   - Inclure tous les paramètres obligatoires du template

4. **Gestion des erreurs**:
   - Implémenter un logging détaillé
   - Capturer et analyser les erreurs API
   - Implémenter des mécanismes de retry pour les erreurs temporaires

---

Ce guide est basé sur les tests effectués le 21 mai 2025 avec l'API WhatsApp version v22.0.