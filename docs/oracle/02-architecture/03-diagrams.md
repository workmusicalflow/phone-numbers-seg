# Diagrammes d'architecture

Cette page présente les diagrammes d'architecture du projet Oracle, offrant une représentation visuelle des différents aspects du système.

## Vue d'ensemble de l'architecture

Le diagramme suivant illustre la vue d'ensemble de l'architecture du projet Oracle, montrant les principales couches et leurs interactions.

```mermaid
graph TD
    subgraph "Couche Présentation"
        UI[Interface Utilisateur]
        VUE[Composants Vue.js]
        STORE[Pinia Stores]
    end

    subgraph "Couche API"
        GQL[API GraphQL]
        REST[API REST]
        MW[Middleware]
    end

    subgraph "Couche Services"
        SVC[Services Métier]
        VAL[Validateurs]
        FACT[Factories]
        STRAT[Stratégies]
    end

    subgraph "Couche Repositories"
        REPO[Repositories]
        QB[Query Builders]
    end

    subgraph "Couche Modèles"
        MOD[Modèles]
        VO[Value Objects]
    end

    subgraph "Couche Données"
        DB[(Base de données)]
    end

    UI --> VUE
    VUE --> STORE
    STORE --> GQL
    UI --> GQL
    UI --> REST

    GQL --> SVC
    REST --> SVC
    MW --> GQL
    MW --> REST

    SVC --> VAL
    SVC --> FACT
    SVC --> STRAT
    SVC --> REPO

    REPO --> QB
    REPO --> MOD
    QB --> DB
    MOD --> DB
```

## Diagramme de composants

Le diagramme suivant montre les principaux composants du système et leurs relations.

```mermaid
graph TD
    subgraph "Frontend"
        UI[Interface Utilisateur]
        ROUTER[Vue Router]
        STORE[Pinia Stores]
        API_CLIENT[API Client]
    end

    subgraph "Backend"
        GQL_SERVER[Serveur GraphQL]
        REST_SERVER[Serveur REST]
        AUTH[Service d'Authentification]
        SMS[Service SMS]
        PHONE[Service de Segmentation]
        IMPORT[Service d'Import/Export]
        NOTIF[Service de Notification]
    end

    subgraph "Externe"
        SMS_API[API SMS Orange]
    end

    UI --> ROUTER
    ROUTER --> STORE
    STORE --> API_CLIENT
    API_CLIENT --> GQL_SERVER
    API_CLIENT --> REST_SERVER

    GQL_SERVER --> AUTH
    GQL_SERVER --> SMS
    GQL_SERVER --> PHONE
    GQL_SERVER --> IMPORT
    GQL_SERVER --> NOTIF

    REST_SERVER --> AUTH
    REST_SERVER --> SMS
    REST_SERVER --> PHONE
    REST_SERVER --> IMPORT

    SMS --> SMS_API
```

## Diagramme de séquence pour la segmentation de numéros

Le diagramme suivant illustre la séquence d'opérations pour la segmentation d'un numéro de téléphone.

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant FE as Frontend
    participant API as API GraphQL
    participant PS as PhoneSegmentationService
    participant SF as StrategyFactory
    participant S as SegmentationStrategy
    participant PR as PhoneRepository
    participant SR as SegmentRepository
    participant DB as Base de données

    U->>FE: Saisit un numéro
    FE->>API: Requête GraphQL (segmentPhone)
    API->>PS: segmentPhone(number)
    PS->>SF: createStrategy(number)
    SF-->>PS: Stratégie appropriée
    PS->>S: segment(number)
    S-->>PS: Segments (country, operator, subscriber)
    PS->>PR: save(phoneNumber)
    PR->>DB: INSERT/UPDATE
    PS->>SR: saveSegments(segments)
    SR->>DB: INSERT
    PS-->>API: PhoneNumber avec segments
    API-->>FE: Résultat de segmentation
    FE-->>U: Affichage des segments
```

## Diagramme de séquence pour l'envoi de SMS

Le diagramme suivant illustre la séquence d'opérations pour l'envoi d'un SMS.

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant FE as Frontend
    participant API as API GraphQL
    participant SBS as SMSBusinessService
    participant VS as ValidationService
    participant PS as PhoneSegmentationService
    participant SS as SMSSenderService
    participant HS as HistoryService
    participant OA as OrangeAPI
    participant DB as Base de données

    U->>FE: Saisit message et destinataires
    FE->>API: Requête GraphQL (sendSMS)
    API->>SBS: sendSMS(to, message, sender)
    SBS->>VS: validateMessage(message)
    VS-->>SBS: Validation OK
    SBS->>PS: segmentPhone(to)
    PS-->>SBS: Segments
    SBS->>SS: send(to, message)
    SS->>OA: sendSMS(params)
    OA-->>SS: Résultat d'envoi
    SS-->>SBS: Statut d'envoi
    SBS->>HS: recordSMS(to, message, sender)
    HS->>DB: INSERT
    SBS-->>API: Résultat d'envoi
    API-->>FE: Confirmation
    FE-->>U: Notification de succès/échec
```

## Diagramme de séquence pour l'authentification

Le diagramme suivant illustre la séquence d'opérations pour l'authentification d'un utilisateur.

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant FE as Frontend
    participant API as API GraphQL
    participant AS as AuthService
    participant US as UserService
    participant UR as UserRepository
    participant DB as Base de données

    U->>FE: Saisit identifiants
    FE->>API: Requête GraphQL (login)
    API->>AS: login(email, password)
    AS->>UR: findByEmail(email)
    UR->>DB: SELECT
    DB-->>UR: User
    UR-->>AS: User
    AS->>AS: Vérification mot de passe
    AS->>AS: Génération JWT
    AS-->>API: Token JWT
    API-->>FE: Token JWT
    FE->>FE: Stockage token
    FE-->>U: Redirection dashboard
```

## Diagramme de classes pour les services de segmentation

Le diagramme suivant illustre les classes impliquées dans la segmentation des numéros de téléphone.

```mermaid
classDiagram
    class PhoneSegmentationServiceInterface {
        <<interface>>
        +segmentPhone(number: string): PhoneNumber
    }

    class PhoneSegmentationService {
        -strategyFactory: SegmentationStrategyFactory
        -phoneRepository: PhoneNumberRepository
        -segmentRepository: SegmentRepository
        +segmentPhone(number: string): PhoneNumber
        -normalizeNumber(number: string): string
    }

    class ChainOfResponsibilityPhoneSegmentationService {
        -chain: SegmentationHandlerInterface
        +segmentPhone(number: string): PhoneNumber
    }

    class SegmentationStrategyInterface {
        <<interface>>
        +segment(number: string): array
    }

    class IvoryCoastSegmentationStrategy {
        +segment(number: string): array
    }

    class SegmentationStrategyFactory {
        +createStrategy(number: string): SegmentationStrategyInterface
    }

    class SegmentationHandlerInterface {
        <<interface>>
        +setNext(handler: SegmentationHandlerInterface): SegmentationHandlerInterface
        +handle(number: string, segments: array): void
    }

    class AbstractSegmentationHandler {
        #nextHandler: SegmentationHandlerInterface
        +setNext(handler: SegmentationHandlerInterface): SegmentationHandlerInterface
        +handle(number: string, segments: array): void
    }

    class CountryCodeHandler {
        +handle(number: string, segments: array): void
    }

    class OperatorCodeHandler {
        +handle(number: string, segments: array): void
    }

    class SubscriberNumberHandler {
        +handle(number: string, segments: array): void
    }

    PhoneSegmentationServiceInterface <|.. PhoneSegmentationService
    PhoneSegmentationServiceInterface <|.. ChainOfResponsibilityPhoneSegmentationService
    PhoneSegmentationService --> SegmentationStrategyFactory
    SegmentationStrategyFactory --> SegmentationStrategyInterface
    SegmentationStrategyInterface <|.. IvoryCoastSegmentationStrategy
    ChainOfResponsibilityPhoneSegmentationService --> SegmentationHandlerInterface
    SegmentationHandlerInterface <|.. AbstractSegmentationHandler
    AbstractSegmentationHandler <|-- CountryCodeHandler
    AbstractSegmentationHandler <|-- OperatorCodeHandler
    AbstractSegmentationHandler <|-- SubscriberNumberHandler
```

## Diagramme de classes pour les services SMS

Le diagramme suivant illustre les classes impliquées dans l'envoi de SMS.

```mermaid
classDiagram
    class SMSBusinessServiceInterface {
        <<interface>>
        +sendSMS(to: string, message: string, sender: string): bool
    }

    class SMSBusinessService {
        -validationService: SMSValidationServiceInterface
        -segmentationService: PhoneSegmentationServiceInterface
        -senderService: SMSSenderServiceInterface
        -historyService: SMSHistoryServiceInterface
        +sendSMS(to: string, message: string, sender: string): bool
    }

    class SMSSenderServiceInterface {
        <<interface>>
        +send(to: string, message: string): bool
        +getStatus(messageId: string): string
    }

    class SMSSenderService {
        -orangeAPI: OrangeAPIClientInterface
        +send(to: string, message: string): bool
        +getStatus(messageId: string): string
    }

    class OrangeAPIClientInterface {
        <<interface>>
        +sendSMS(params: array): array
        +checkDeliveryStatus(id: string): array
    }

    class OrangeAPIClient {
        -apiKey: string
        -apiUrl: string
        +sendSMS(params: array): array
        +checkDeliveryStatus(id: string): array
    }

    class SMSValidationServiceInterface {
        <<interface>>
        +validateMessage(message: string): bool
    }

    class SMSValidationService {
        -maxLength: int
        +validateMessage(message: string): bool
    }

    class SMSHistoryServiceInterface {
        <<interface>>
        +recordSMS(to: string, message: string, sender: string): void
        +getHistory(userId: int): array
    }

    class SMSHistoryService {
        -historyRepository: SMSHistoryRepositoryInterface
        +recordSMS(to: string, message: string, sender: string): void
        +getHistory(userId: int): array
    }

    SMSBusinessServiceInterface <|.. SMSBusinessService
    SMSBusinessService --> SMSValidationServiceInterface
    SMSBusinessService --> PhoneSegmentationServiceInterface
    SMSBusinessService --> SMSSenderServiceInterface
    SMSBusinessService --> SMSHistoryServiceInterface
    SMSSenderServiceInterface <|.. SMSSenderService
    SMSSenderService --> OrangeAPIClientInterface
    OrangeAPIClientInterface <|.. OrangeAPIClient
    SMSValidationServiceInterface <|.. SMSValidationService
    SMSHistoryServiceInterface <|.. SMSHistoryService
```

## Diagramme entité-relation de la base de données

Le diagramme suivant illustre les relations entre les tables de la base de données.

```mermaid
erDiagram
    USERS ||--o{ PHONE_NUMBERS : possède
    USERS ||--o{ SMS_ORDERS : commande
    USERS ||--o{ SENDER_NAMES : enregistre
    USERS ||--o{ CONTACTS : gère
    USERS ||--o{ CONTACT_GROUPS : organise
    PHONE_NUMBERS ||--o{ SEGMENTS : est_segmenté_en
    PHONE_NUMBERS ||--o{ SMS_HISTORY : reçoit
    SEGMENTS ||--o{ CUSTOM_SEGMENTS : correspond_à
    SMS_ORDERS ||--o{ SMS_HISTORY : génère
    SENDER_NAMES ||--o{ SMS_ORDERS : utilisé_dans
    CONTACTS ||--o{ CONTACT_GROUP_MEMBERSHIPS : appartient_à
    CONTACT_GROUPS ||--o{ CONTACT_GROUP_MEMBERSHIPS : contient
    USERS ||--o{ SCHEDULED_SMS : planifie
    SCHEDULED_SMS ||--o{ SCHEDULED_SMS_LOGS : génère
    USERS ||--o{ SMS_TEMPLATES : crée

    USERS {
        int id PK
        string email
        string password
        string name
        boolean is_admin
        datetime created_at
        datetime updated_at
    }

    PHONE_NUMBERS {
        int id PK
        int user_id FK
        string number
        string normalized_number
        string country_code
        string operator_code
        string subscriber_number
        datetime created_at
        datetime updated_at
    }

    SEGMENTS {
        int id PK
        int phone_number_id FK
        string type
        string value
        string description
        datetime created_at
    }

    CUSTOM_SEGMENTS {
        int id PK
        string name
        string pattern
        string description
        boolean is_active
        datetime created_at
        datetime updated_at
    }

    SMS_ORDERS {
        int id PK
        int user_id FK
        int sender_name_id FK
        string message
        int credit_used
        string status
        datetime scheduled_at
        datetime created_at
        datetime updated_at
    }

    SMS_HISTORY {
        int id PK
        int sms_order_id FK
        int phone_number_id FK
        string status
        string message_id
        datetime sent_at
        datetime delivered_at
    }

    SENDER_NAMES {
        int id PK
        int user_id FK
        string name
        string status
        datetime approved_at
        datetime created_at
        datetime updated_at
    }

    CONTACTS {
        int id PK
        int user_id FK
        string name
        string phone_number
        string email
        string notes
        datetime created_at
        datetime updated_at
    }

    CONTACT_GROUPS {
        int id PK
        int user_id FK
        string name
        string description
        datetime created_at
        datetime updated_at
    }

    CONTACT_GROUP_MEMBERSHIPS {
        int id PK
        int contact_id FK
        int group_id FK
        datetime created_at
    }

    SCHEDULED_SMS {
        int id PK
        int user_id FK
        int sender_name_id FK
        string message
        string recipients_type
        string recipients_data
        string frequency
        datetime next_execution
        boolean is_active
        datetime created_at
        datetime updated_at
    }

    SCHEDULED_SMS_LOGS {
        int id PK
        int scheduled_sms_id FK
        datetime execution_time
        int sms_sent
        int sms_failed
        string status
        string error_message
    }

    SMS_TEMPLATES {
        int id PK
        int user_id FK
        string name
        string content
        string description
        boolean is_public
        datetime created_at
        datetime updated_at
    }
```

## Diagramme de déploiement

Le diagramme suivant illustre l'architecture de déploiement du projet Oracle.

```mermaid
graph TD
    subgraph "Client"
        BROWSER[Navigateur Web]
    end

    subgraph "Serveur Web"
        NGINX[Serveur NGINX]
        PHP_FPM[PHP-FPM]
        STATIC[Fichiers Statiques]
    end

    subgraph "Base de données"
        DB[(SQLite/MySQL)]
    end

    subgraph "Services Externes"
        SMS_API[API SMS Orange]
    end

    BROWSER --> NGINX
    NGINX --> PHP_FPM
    NGINX --> STATIC
    PHP_FPM --> DB
    PHP_FPM --> SMS_API
```

## Diagramme d'activité pour l'importation de numéros

Le diagramme suivant illustre le processus d'importation de numéros de téléphone.

```mermaid
graph TD
    A[Début] --> B[Téléverser fichier CSV]
    B --> C[Valider format du fichier]
    C --> D{Format valide?}
    D -->|Non| E[Afficher erreur]
    E --> B
    D -->|Oui| F[Lire données CSV]
    F --> G[Initialiser compteurs]
    G --> H[Traiter ligne suivante]
    H --> I{Fin du fichier?}
    I -->|Oui| J[Afficher résultats]
    I -->|Non| K[Extraire numéro]
    K --> L{Numéro valide?}
    L -->|Non| M[Incrémenter compteur d'erreurs]
    M --> H
    L -->|Oui| N[Segmenter numéro]
    N --> O{Segmentation réussie?}
    O -->|Non| P[Incrémenter compteur d'erreurs]
    P --> H
    O -->|Oui| Q[Sauvegarder numéro]
    Q --> R{Sauvegarde réussie?}
    R -->|Non| S[Incrémenter compteur d'erreurs]
    S --> H
    R -->|Oui| T[Incrémenter compteur de succès]
    T --> H
    J --> U[Fin]
```

## Conclusion

Ces diagrammes fournissent une représentation visuelle des différents aspects de l'architecture du projet Oracle. Ils aident à comprendre la structure du système, les interactions entre les composants, et les flux de données.

Les diagrammes sont maintenus à jour avec l'évolution du projet pour assurer qu'ils reflètent fidèlement l'architecture actuelle du système.
