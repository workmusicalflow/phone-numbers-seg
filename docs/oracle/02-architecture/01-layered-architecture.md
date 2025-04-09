# Architecture en couches

Le projet Oracle est construit selon une architecture en couches qui sépare clairement les responsabilités et facilite la maintenance et l'évolution du code. Cette page détaille chaque couche et ses responsabilités.

## Vue d'ensemble

L'architecture en couches du projet Oracle est organisée comme suit, de haut en bas :

1. **Couche Présentation** : Interface utilisateur (Vue.js, Quasar)
2. **Couche API** : Points d'entrée de l'application (GraphQL, REST)
3. **Couche Services** : Logique métier et orchestration
4. **Couche Repositories** : Accès aux données
5. **Couche Modèles** : Représentation des entités métier
6. **Couche Données** : Base de données (SQLite/MySQL)

Cette séparation permet de :

- Isoler les changements dans une couche sans affecter les autres
- Faciliter les tests unitaires et d'intégration
- Améliorer la maintenabilité et la lisibilité du code
- Permettre le développement parallèle par plusieurs équipes

## Couche Présentation

La couche présentation est responsable de l'interface utilisateur et de l'interaction avec l'utilisateur.

### Composants principaux

- **Vue.js Components** : Composants réutilisables pour l'interface utilisateur
- **Pinia Stores** : Gestion de l'état de l'application côté client
- **Vue Router** : Gestion des routes et de la navigation
- **Services API** : Communication avec le backend via GraphQL et REST

### Responsabilités

- Afficher les données à l'utilisateur
- Capturer les entrées utilisateur
- Valider les entrées côté client
- Gérer l'état de l'interface utilisateur
- Communiquer avec la couche API

### Exemple de code

```typescript
// Exemple de store Pinia pour la gestion des numéros de téléphone
import { defineStore } from "pinia";
import { api } from "@/services/api";

export const usePhoneStore = defineStore("phone", {
  state: () => ({
    phones: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchPhones() {
      this.loading = true;
      try {
        const response = await api.graphql(`
          query GetPhones {
            phones {
              id
              number
              countryCode
              operatorCode
              subscriberNumber
            }
          }
        `);
        this.phones = response.data.phones;
      } catch (error) {
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
  },
});
```

## Couche API

La couche API sert de point d'entrée pour les clients et expose les fonctionnalités de l'application.

### Composants principaux

- **GraphQL API** : API principale basée sur GraphQL
- **REST API** : API REST pour la compatibilité avec les clients existants
- **Middleware** : Authentification, autorisation, validation, etc.

### Responsabilités

- Exposer les fonctionnalités de l'application
- Valider les entrées
- Gérer l'authentification et l'autorisation
- Transformer les données pour les clients
- Gérer les erreurs et les exceptions

### Exemple de code

```php
// Exemple de contrôleur GraphQL pour les numéros de téléphone
namespace App\GraphQL\Controllers;

use App\Models\PhoneNumber;
use App\Services\PhoneSegmentationService;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class PhoneNumberController
{
    private $segmentationService;

    public function __construct(PhoneSegmentationService $segmentationService)
    {
        $this->segmentationService = $segmentationService;
    }

    /**
     * @Query
     * @return PhoneNumber[]
     */
    public function phones(): array
    {
        return PhoneNumber::all()->toArray();
    }

    /**
     * @Mutation
     * @param string $number
     * @return PhoneNumber
     */
    public function segmentPhone(string $number): PhoneNumber
    {
        return $this->segmentationService->segmentPhone($number);
    }
}
```

## Couche Services

La couche services contient la logique métier de l'application et orchestre les opérations.

### Composants principaux

- **Services métier** : Implémentation des cas d'utilisation
- **Validateurs** : Validation des données
- **Factories** : Création d'objets complexes
- **Stratégies** : Implémentation de différentes stratégies pour un même problème

### Responsabilités

- Implémenter la logique métier
- Orchestrer les opérations
- Valider les données
- Gérer les transactions
- Appliquer les règles métier

### Exemple de code

```php
// Exemple de service de segmentation de numéros de téléphone
namespace App\Services;

use App\Models\PhoneNumber;
use App\Models\Segment;
use App\Repositories\PhoneNumberRepository;
use App\Services\Strategies\SegmentationStrategy;
use App\Services\Factories\SegmentationStrategyFactory;

class PhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    private $phoneRepository;
    private $strategyFactory;

    public function __construct(
        PhoneNumberRepository $phoneRepository,
        SegmentationStrategyFactory $strategyFactory
    ) {
        $this->phoneRepository = $phoneRepository;
        $this->strategyFactory = $strategyFactory;
    }

    public function segmentPhone(string $number): PhoneNumber
    {
        // Normaliser le numéro
        $normalizedNumber = $this->normalizeNumber($number);

        // Déterminer la stratégie de segmentation
        $strategy = $this->strategyFactory->createStrategy($normalizedNumber);

        // Segmenter le numéro
        $segments = $strategy->segment($normalizedNumber);

        // Créer et sauvegarder le numéro de téléphone
        $phone = new PhoneNumber();
        $phone->number = $number;
        $phone->normalized_number = $normalizedNumber;
        $phone->country_code = $segments['countryCode'];
        $phone->operator_code = $segments['operatorCode'];
        $phone->subscriber_number = $segments['subscriberNumber'];

        $this->phoneRepository->save($phone);

        // Créer et sauvegarder les segments
        foreach ($segments as $type => $value) {
            $segment = new Segment();
            $segment->phone_number_id = $phone->id;
            $segment->type = $type;
            $segment->value = $value;
            $segment->save();
        }

        return $phone;
    }

    private function normalizeNumber(string $number): string
    {
        // Supprimer les caractères non numériques
        $number = preg_replace('/[^0-9]/', '', $number);

        // Supprimer le préfixe international si présent
        if (substr($number, 0, 3) === '225') {
            $number = substr($number, 3);
        }

        // Ajouter le préfixe local si nécessaire
        if (substr($number, 0, 1) !== '0') {
            $number = '0' . $number;
        }

        return $number;
    }
}
```

## Couche Repositories

La couche repositories fournit une abstraction pour l'accès aux données et isole la logique métier des détails de persistance.

### Composants principaux

- **Repositories** : Accès aux données pour chaque entité
- **Query Builders** : Construction de requêtes complexes
- **Data Mappers** : Conversion entre les modèles et les données persistées

### Responsabilités

- Fournir une interface pour l'accès aux données
- Encapsuler la logique de requête
- Gérer la persistance des entités
- Implémenter les opérations CRUD
- Gérer les relations entre entités

### Exemple de code

```php
// Exemple de repository pour les numéros de téléphone
namespace App\Repositories;

use App\Models\PhoneNumber;
use PDO;

class PhoneNumberRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?PhoneNumber
    {
        $stmt = $this->pdo->prepare('SELECT * FROM phone_numbers WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findByNumber(string $number): ?PhoneNumber
    {
        $stmt = $this->pdo->prepare('SELECT * FROM phone_numbers WHERE number = :number');
        $stmt->bindParam(':number', $number, PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function save(PhoneNumber $phone): void
    {
        if ($phone->id) {
            $this->update($phone);
        } else {
            $this->insert($phone);
        }
    }

    private function insert(PhoneNumber $phone): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO phone_numbers (
                number, normalized_number, country_code, operator_code, subscriber_number, created_at, updated_at
            ) VALUES (
                :number, :normalized_number, :country_code, :operator_code, :subscriber_number, :created_at, :updated_at
            )
        ');

        $now = date('Y-m-d H:i:s');

        $stmt->bindParam(':number', $phone->number, PDO::PARAM_STR);
        $stmt->bindParam(':normalized_number', $phone->normalized_number, PDO::PARAM_STR);
        $stmt->bindParam(':country_code', $phone->country_code, PDO::PARAM_STR);
        $stmt->bindParam(':operator_code', $phone->operator_code, PDO::PARAM_STR);
        $stmt->bindParam(':subscriber_number', $phone->subscriber_number, PDO::PARAM_STR);
        $stmt->bindParam(':created_at', $now, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $now, PDO::PARAM_STR);

        $stmt->execute();

        $phone->id = $this->pdo->lastInsertId();
    }

    private function update(PhoneNumber $phone): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE phone_numbers SET
                number = :number,
                normalized_number = :normalized_number,
                country_code = :country_code,
                operator_code = :operator_code,
                subscriber_number = :subscriber_number,
                updated_at = :updated_at
            WHERE id = :id
        ');

        $now = date('Y-m-d H:i:s');

        $stmt->bindParam(':id', $phone->id, PDO::PARAM_INT);
        $stmt->bindParam(':number', $phone->number, PDO::PARAM_STR);
        $stmt->bindParam(':normalized_number', $phone->normalized_number, PDO::PARAM_STR);
        $stmt->bindParam(':country_code', $phone->country_code, PDO::PARAM_STR);
        $stmt->bindParam(':operator_code', $phone->operator_code, PDO::PARAM_STR);
        $stmt->bindParam(':subscriber_number', $phone->subscriber_number, PDO::PARAM_STR);
        $stmt->bindParam(':updated_at', $now, PDO::PARAM_STR);

        $stmt->execute();
    }

    private function hydrate(array $data): PhoneNumber
    {
        $phone = new PhoneNumber();
        $phone->id = $data['id'];
        $phone->number = $data['number'];
        $phone->normalized_number = $data['normalized_number'];
        $phone->country_code = $data['country_code'];
        $phone->operator_code = $data['operator_code'];
        $phone->subscriber_number = $data['subscriber_number'];
        $phone->created_at = $data['created_at'];
        $phone->updated_at = $data['updated_at'];

        return $phone;
    }
}
```

## Couche Modèles

La couche modèles représente les entités métier de l'application.

### Composants principaux

- **Modèles** : Représentation des entités métier
- **Value Objects** : Objets immuables représentant des concepts métier
- **Enums** : Ensembles de valeurs prédéfinies

### Responsabilités

- Représenter les entités métier
- Encapsuler les données et le comportement
- Définir les relations entre entités
- Implémenter les règles métier spécifiques aux entités

### Exemple de code

```php
// Exemple de modèle pour les numéros de téléphone
namespace App\Models;

class PhoneNumber
{
    public $id;
    public $number;
    public $normalized_number;
    public $country_code;
    public $operator_code;
    public $subscriber_number;
    public $created_at;
    public $updated_at;

    public function getFormattedNumber(): string
    {
        return '+' . $this->country_code . ' ' . $this->operator_code . ' ' . $this->subscriber_number;
    }

    public function getOperatorName(): string
    {
        $operatorCodes = [
            '07' => 'Orange',
            '01' => 'MTN',
            '05' => 'Moov'
        ];

        return $operatorCodes[$this->operator_code] ?? 'Inconnu';
    }
}
```

## Couche Données

La couche données est responsable de la persistance des données.

### Composants principaux

- **Base de données** : SQLite/MySQL
- **Migrations** : Scripts de création et de mise à jour de la base de données
- **Seeds** : Données initiales pour la base de données

### Responsabilités

- Stocker les données de manière persistante
- Assurer l'intégrité des données
- Fournir des mécanismes de requête efficaces
- Gérer les transactions et les verrous

### Exemple de code

```sql
-- Exemple de migration pour la table des numéros de téléphone
CREATE TABLE phone_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    number VARCHAR(20) NOT NULL,
    normalized_number VARCHAR(20) NOT NULL,
    country_code VARCHAR(5),
    operator_code VARCHAR(5),
    subscriber_number VARCHAR(15),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_phone_numbers_number ON phone_numbers(number);
CREATE INDEX idx_phone_numbers_user_id ON phone_numbers(user_id);
```

## Communication entre les couches

La communication entre les couches suit le principe de dépendance inversée : les couches de haut niveau ne dépendent pas des couches de bas niveau, mais plutôt d'abstractions.

### Flux de données typique

1. L'utilisateur interagit avec l'interface utilisateur (Couche Présentation)
2. La requête est envoyée au backend via GraphQL ou REST (Couche API)
3. Le contrôleur traite la requête et appelle le service approprié (Couche API → Couche Services)
4. Le service implémente la logique métier et utilise les repositories pour accéder aux données (Couche Services → Couche Repositories)
5. Le repository récupère ou persiste les données via les modèles (Couche Repositories → Couche Modèles → Couche Données)
6. Les données sont retournées à travers les couches jusqu'à l'interface utilisateur

### Inversion de dépendance

L'inversion de dépendance est implémentée via des interfaces et l'injection de dépendances :

```php
// Interface pour le repository
interface PhoneNumberRepositoryInterface
{
    public function findById(int $id): ?PhoneNumber;
    public function findByNumber(string $number): ?PhoneNumber;
    public function save(PhoneNumber $phone): void;
}

// Service dépendant de l'interface, pas de l'implémentation
class PhoneSegmentationService
{
    private $phoneRepository;

    public function __construct(PhoneNumberRepositoryInterface $phoneRepository)
    {
        $this->phoneRepository = $phoneRepository;
    }

    // ...
}
```

## Avantages de l'architecture en couches

L'architecture en couches offre plusieurs avantages :

1. **Séparation des préoccupations** : Chaque couche a une responsabilité unique et bien définie
2. **Testabilité** : Les composants peuvent être testés isolément
3. **Maintenabilité** : Les changements dans une couche n'affectent pas les autres couches
4. **Évolutivité** : De nouvelles fonctionnalités peuvent être ajoutées sans modifier le code existant
5. **Développement parallèle** : Plusieurs équipes peuvent travailler sur différentes couches simultanément
6. **Réutilisabilité** : Les composants peuvent être réutilisés dans différentes parties de l'application

## Inconvénients et mitigations

L'architecture en couches présente également quelques inconvénients :

1. **Complexité accrue** : Plus de code et de concepts à comprendre

   - **Mitigation** : Documentation claire et formation des développeurs

2. **Surcharge de performance** : La communication entre les couches peut introduire une surcharge

   - **Mitigation** : Optimisation des points critiques et mise en cache

3. **Duplication de code** : Risque de duplication entre les couches

   - **Mitigation** : Utilisation de générateurs de code et d'outils d'automatisation

4. **Rigidité** : L'architecture peut être trop rigide pour certains cas d'utilisation
   - **Mitigation** : Flexibilité dans l'application des principes architecturaux

## Conclusion

L'architecture en couches du projet Oracle offre une base solide pour le développement et l'évolution de l'application. Elle permet de séparer clairement les responsabilités, de faciliter les tests et la maintenance, et d'évoluer avec les besoins du projet.

La clé du succès de cette architecture réside dans le respect des principes de séparation des préoccupations et d'inversion de dépendance, ainsi que dans la communication claire entre les équipes de développement.
