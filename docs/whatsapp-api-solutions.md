# Solutions pour les problèmes d'API WhatsApp

## Date: 21 mai 2025

Ce document propose des solutions aux problèmes identifiés lors des tests des APIs WhatsApp.

## 1. Correction des problèmes d'API Meta Cloud

### Problème: Erreurs d'accès aux champs dans l'API Meta

```
(#100) Tried accessing nonexisting field (message_templates) on node type (WhatsAppBusinessPhoneNumber)
```

### Solutions implémentées et validées:

1. **Utilisation de la structure correcte des endpoints Meta**:
   ```bash
   # Pour récupérer les templates (CORRECT)
   https://graph.facebook.com/v22.0/WABA_ID/message_templates
   
   # Pour envoyer des messages (CORRECT)
   https://graph.facebook.com/v22.0/PHONE_NUMBER_ID/messages
   ```

2. **Scripts corrigés pour utiliser les bons identifiants selon l'opération**:
   ```bash
   # Pour récupérer les templates
   curl -s -X GET "$META_API_URL/$WABA_ID/message_templates" \
       -H "Authorization: Bearer $ACCESS_TOKEN"
   
   # Pour envoyer des messages
   curl -s -X POST "$META_API_URL/$PHONE_NUMBER_ID/messages" \
       -H "Authorization: Bearer $ACCESS_TOKEN" \
       -H "Content-Type: application/json" \
       -d '{"messaging_product": "whatsapp", "to": "'$RECIPIENT_PHONE'", ...}'
   ```

3. **Validation des identifiants**:
   - ✅ Le token d'accès actuel fonctionne correctement
   - ✅ Les permissions sont suffisantes pour récupérer les templates et envoyer des messages
   - ✅ Testé et validé avec un envoi réel au numéro +2250777104936

## 2. Correction des problèmes d'API Locale

### Problème: Erreur fatale dans BatchSegmentationService

```
Fatal error: Uncaught TypeError: App\Services\BatchSegmentationService::__construct(): 
Argument #2 ($phoneNumberRepository) must be of type App\Repositories\Interfaces\PhoneNumberRepositoryInterface, 
App\Repositories\PhoneNumberRepository given
```

### Solution:

1. **Corriger la définition dans le conteneur DI**:
   ```php
   // Fichier: src/config/di.php ou src/config/di/other.php
   // Ajouter ou modifier:
   return [
       // ...
       App\Repositories\Interfaces\PhoneNumberRepositoryInterface::class => 
           DI\autowire(App\Repositories\Doctrine\PhoneNumberRepository::class),
       // ...
   ];
   ```

2. **Ou modifier la classe BatchSegmentationService**:
   ```php
   // Fichier: src/Services/BatchSegmentationService.php
   // Modifier le constructeur:
   public function __construct(
       Logger $logger, 
       // Changer le type hint pour accepter l'implémentation concrète également
       $phoneNumberRepository
   ) {
       $this->logger = $logger;
       $this->phoneNumberRepository = $phoneNumberRepository;
   }
   ```

### Problème: GraphQL - Champ non-nullable retourne null

```
Cannot return null for non-nullable field "Query.getWhatsAppTemplateUsageMetrics"
```

### Solution:

1. **Modifier le schéma GraphQL**:
   ```graphql
   # src/GraphQL/schema.graphql
   # Modifier:
   type Query {
     # Remplacer:
     getWhatsAppTemplateUsageMetrics(startDate: DateTime, endDate: DateTime): TemplateMetricsResult!
     # Par:
     getWhatsAppTemplateUsageMetrics(startDate: DateTime, endDate: DateTime): TemplateMetricsResult
   }
   ```

2. **Ou s'assurer que la méthode retourne toujours un objet valide**:
   ```php
   // Dans WhatsAppResolver.php ou le fichier approprié
   public function getWhatsAppTemplateUsageMetrics(...): TemplateMetricsResult 
   {
       // S'assurer de toujours retourner un objet TemplateMetricsResult valide
       // même en cas d'erreur
       try {
           // code existant...
       } catch (Exception $e) {
           // En cas d'erreur, retourner un objet vide mais valide
           return new TemplateMetricsResult(0, 0, $e->getMessage());
       }
   }
   ```

### Problème: GraphQL - Nom de méthode incorrect

```
Cannot query field "getMostUsedWhatsAppTemplates" on type "Query"
```

### Solution:

1. **Corriger le nom de la méthode dans les appels**:
   ```
   # Remplacer:
   query { getMostUsedWhatsAppTemplates(limit: 5) { ... } }
   
   # Par:
   query { mostUsedWhatsAppTemplates(limit: 5) { ... } }
   ```

2. **Ou mettre à jour le schéma GraphQL**:
   ```graphql
   # src/GraphQL/schema.graphql
   # Ajouter:
   type Query {
     # ...
     getMostUsedWhatsAppTemplates(limit: Int): [TemplateUsage!]!
     # ...
   }
   ```

### Problème: GraphQL - Structure d'entrée incorrecte pour l'envoi de message

```
Field WhatsAppMessageInput.recipient of required type String! was not provided.
Field "phoneNumber" is not defined by type "WhatsAppMessageInput".
Field "templateLanguage" is not defined by type "WhatsAppMessageInput".
```

### Solution:

1. **Corriger la structure d'entrée dans les appels**:
   ```graphql
   # Remplacer:
   mutation { 
     sendWhatsAppMessage(message: { 
       phoneNumber: "+22556789012", 
       type: "template", 
       templateName: "greeting", 
       templateLanguage: "fr" 
     }) { ... }
   }
   
   # Par:
   mutation { 
     sendWhatsAppMessage(message: { 
       recipient: "+22556789012", 
       type: "template", 
       templateName: "greeting", 
       languageCode: "fr" 
     }) { ... }
   }
   ```

2. **Structure complète pour l'envoi de template avec image**:
   ```graphql
   mutation {
     sendWhatsAppTemplate(
       recipient: "+2250777104936",
       templateName: "qshe_invitation1",
       languageCode: "fr",
       components: [
         {
           type: "header",
           parameters: [
             {
               type: "image",
               image: {
                 link: "https://example.com/image.jpg"
               }
             }
           ]
         },
         {
           type: "body",
           parameters: [
             {
               type: "text",
               text: "John Doe"
             },
             {
               type: "text",
               text: "10/06/2025"
             }
           ]
         }
       ]
     ) {
       messageId
       status
       errorMessage
     }
   }
   ```

2. **Mettre à jour la documentation pour refléter la structure correcte**:
   ```markdown
   # Structure correcte pour WhatsAppMessageInput:
   {
     recipient: String!       # Numéro de téléphone du destinataire
     type: String!           # Type de message: "text", "template", "media", etc.
     templateName: String    # Pour les messages de type "template"
     languageCode: String    # Code de langue pour les templates
     // autres champs...
   }
   ```

## 3. Amélioration générale de la robustesse

### Solutions proposées:

1. **Logging amélioré**:
   ```php
   // Ajouter des logs détaillés pour les appels à l'API Meta
   try {
       $this->logger->info("Appel API Meta: GET {$endpoint}", ['params' => $params]);
       $response = $this->client->get($endpoint, ['query' => $params]);
       $this->logger->info("Réponse API Meta reçue", ['status' => $response->getStatusCode()]);
   } catch (Exception $e) {
       $this->logger->error("Erreur API Meta: " . $e->getMessage(), [
           'endpoint' => $endpoint,
           'params' => $params,
           'exception' => get_class($e),
           'trace' => $e->getTraceAsString()
       ]);
   }
   ```

2. **Mécanisme de fallback**:
   ```php
   public function getTemplates($forceRefresh = false) 
   {
       try {
           if ($forceRefresh) {
               $templates = $this->fetchFromMetaApi();
               $this->cacheTemplates($templates);
               return $templates;
           }
           
           $cachedTemplates = $this->getCachedTemplates();
           if (empty($cachedTemplates)) {
               $templates = $this->fetchFromMetaApi();
               $this->cacheTemplates($templates);
               return $templates;
           }
           
           return $cachedTemplates;
       } catch (Exception $e) {
           $this->logger->warning("Utilisation de templates de fallback en raison d'une erreur", [
               'error' => $e->getMessage()
           ]);
           return $this->getDefaultTemplates();
       }
   }
   ```

3. **Ajout de tests spécifiques**:
   ```php
   // Dans un fichier de test
   public function testWhatsAppTemplateRetrieval()
   {
       // Test avec cache
       $templates = $this->service->getTemplates();
       $this->assertNotEmpty($templates);
       
       // Test avec forceRefresh
       $templates = $this->service->getTemplates(true);
       $this->assertNotEmpty($templates);
       
       // Test en cas d'erreur API
       $this->mockApiToThrowException();
       $templates = $this->service->getTemplates();
       $this->assertNotEmpty($templates); // Devrait toujours retourner des templates de fallback
   }
   ```

## 4. Documentation et monitoring

### Solutions proposées:

1. **Documenter l'authentification**:
   ```markdown
   # Authentification pour l'API GraphQL WhatsApp
   
   Toutes les requêtes GraphQL concernant WhatsApp nécessitent une authentification.
   
   ## Options d'authentification:
   
   1. **Cookie de session**:
      - Connectez-vous à l'application
      - Le cookie PHPSESSID sera utilisé pour authentifier les requêtes ultérieures
   
   2. **Token JWT (si implémenté)**:
      - Obtenez un token via la mutation login
      - Incluez le token dans l'en-tête Authorization: `Bearer <token>`
   ```

2. **Système de monitoring**:
   ```php
   // Classe pour le monitoring de l'API WhatsApp
   class WhatsAppApiMonitor 
   {
       private $logger;
       private $metricRepository;
       
       public function recordApiCall(string $endpoint, bool $success, ?string $error = null, int $duration = 0)
       {
           $this->metricRepository->save(new WhatsAppApiMetric(
               $endpoint,
               $success,
               $error,
               $duration,
               new DateTime()
           ));
       }
       
       public function getMetrics(DateTime $startDate, DateTime $endDate): array
       {
           return $this->metricRepository->findByDateRange($startDate, $endDate);
       }
   }
   ```

3. **Dashboard pour les métriques**:
   - Créer une vue admin pour afficher les métriques d'API WhatsApp
   - Inclure des graphiques pour les taux de succès, temps de réponse, etc.
   - Alertes en cas de dégradation

## 5. Upload et gestion des médias

### Problème: Erreur lors de l'upload de médias

```
{
  "error": {
    "message": "The parameter file is required",
    "type": "OAuthException",
    "code": 100,
    "error_data": { ... },
    "fbtrace_id": "A4TIuBJB4cGdqPwkNcpbLT0"
  }
}
```

### Solutions:

1. **Correction de l'upload de médias**:
   ```bash
   # Méthode correcte pour l'upload de média
   curl -X POST "https://graph.facebook.com/v22.0/$PHONE_NUMBER_ID/media" \
     -H "Authorization: Bearer $ACCESS_TOKEN" \
     -F "messaging_product=whatsapp" \
     -F "file=@/chemin/vers/image.jpg" \
     -F "type=image/jpeg"
   ```

2. **Alternative: Utilisation d'URLs pour les médias**:
   ```json
   {
     "messaging_product": "whatsapp",
     "to": "<NUMERO_DESTINATAIRE>",
     "type": "template",
     "template": {
       "name": "qshe_invitation1",
       "language": { "code": "fr" },
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
   }
   ```

3. **Implémentation d'un service de cache média**:
   ```php
   class MediaCacheService {
     private $repository;
     private $whatsappClient;
     
     // Obtient l'ID d'un média dans le cache ou l'upload si nécessaire
     public function getOrUploadMedia(string $mediaUrl): string {
       // Vérifier si déjà dans le cache
       $cachedMedia = $this->repository->findByUrl($mediaUrl);
       if ($cachedMedia && !$this->isExpired($cachedMedia)) {
         return $cachedMedia->getWhatsappMediaId();
       }
       
       // Upload le média et mettre en cache
       $mediaId = $this->whatsappClient->uploadMedia($mediaUrl);
       $this->repository->save(new MediaCache($mediaUrl, $mediaId, new DateTime()));
       return $mediaId;
     }
   }
   ```

## 6. Testing automatisé

### Solutions proposées:

1. **Tests unitaires des services WhatsApp**:
   ```php
   // Tests/Services/WhatsApp/WhatsAppServiceTest.php
   public function testTemplateRetrieval() {
     // Arrange
     $mockClient = $this->createMock(WhatsAppApiClientInterface::class);
     $mockClient->method('getTemplates')
       ->willReturn([
         ['name' => 'greeting', 'status' => 'APPROVED', 'language' => 'fr'],
         ['name' => 'support', 'status' => 'APPROVED', 'language' => 'fr']
       ]);
     
     $service = new WhatsAppService($mockClient, $this->mockLogger);
     
     // Act
     $templates = $service->getApprovedTemplates();
     
     // Assert
     $this->assertCount(2, $templates);
     $this->assertEquals('greeting', $templates[0]['name']);
   }
   ```

2. **Tests d'intégration**:
   ```php
   // Tests/Integration/WhatsApp/TemplateServiceIntegrationTest.php
   public function testTemplateSync() {
     // Arrange - utilise un environnement de test isolé
     $container = $this->getTestContainer();
     $service = $container->get(WhatsAppTemplateService::class);
     
     // Act
     $service->synchronizeTemplates();
     
     // Assert
     $repository = $container->get(WhatsAppTemplateRepositoryInterface::class);
     $templates = $repository->findAll();
     $this->assertGreaterThan(0, count($templates));
   }
   ```

3. **Script de test E2E**:
   ```bash
   #!/bin/bash
   
   # Test end-to-end du flux WhatsApp
   
   # 1. Sync des templates
   echo "1. Synchronisation des templates..."
   php scripts/sync-whatsapp-templates.php
   
   # 2. Vérification de la présence des templates dans la BDD
   echo "2. Vérification des templates en base..."
   php scripts/check-templates-in-db.php
   
   # 3. Test d'envoi de message
   echo "3. Envoi d'un message de test..."
   php scripts/send-test-template.php --template="connection_check" --to="+2250777104936"
   
   # 4. Vérification de l'historique
   echo "4. Vérification de l'historique des messages..."
   php scripts/verify-message-history.php --latest
   ```