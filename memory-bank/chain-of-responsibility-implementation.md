# Implémentation du Pattern Chain of Responsibility

## Contexte et Problématique

La segmentation des numéros de téléphone était initialement gérée par le `PhoneSegmentationService` utilisant le pattern Strategy. Cette approche présentait plusieurs limitations :

- Manque de flexibilité pour ajouter de nouvelles étapes
- Duplication de code entre les stratégies
- Difficulté d'extension pour de nouveaux types de segmentation

## Solution Implémentée

Nous avons refactorisé le système en utilisant le pattern Chain of Responsibility, qui permet de :

1. Décomposer le processus en étapes distinctes
2. Faciliter l'ajout de nouvelles étapes sans modifier le code existant
3. Éliminer la duplication de code
4. Améliorer la testabilité

## Composants Clés

### Interface Handler

```php
interface SegmentationHandlerInterface
{
    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface;
    public function handle(PhoneNumber $phoneNumber): PhoneNumber;
}
```

### Handler Abstrait

```php
abstract class AbstractSegmentationHandler implements SegmentationHandlerInterface
{
    protected $nextHandler;

    public function setNext(SegmentationHandlerInterface $handler): SegmentationHandlerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    public function handle(PhoneNumber $phoneNumber): PhoneNumber
    {
        $processedPhoneNumber = $this->process($phoneNumber);

        if ($this->nextHandler) {
            return $this->nextHandler->handle($processedPhoneNumber);
        }

        return $processedPhoneNumber;
    }

    abstract protected function process(PhoneNumber $phoneNumber): PhoneNumber;
}
```

### Handlers Concrets

```php
class CountryCodeHandler extends AbstractSegmentationHandler
{
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Extraction du code pays
        // Création d'un segment pour le code pays
        return $phoneNumber;
    }
}

class OperatorCodeHandler extends AbstractSegmentationHandler
{
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Extraction du code opérateur
        // Création d'un segment pour le code opérateur
        return $phoneNumber;
    }
}

class SubscriberNumberHandler extends AbstractSegmentationHandler
{
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Extraction du numéro d'abonné
        // Création d'un segment pour le numéro d'abonné
        return $phoneNumber;
    }
}
```

### Factory pour Créer la Chaîne

```php
class SegmentationHandlerFactory
{
    public function createChain(): SegmentationHandlerInterface
    {
        $countryCodeHandler = new CountryCodeHandler();
        $operatorCodeHandler = new OperatorCodeHandler();
        $subscriberNumberHandler = new SubscriberNumberHandler();

        $countryCodeHandler->setNext($operatorCodeHandler)->setNext($subscriberNumberHandler);

        return $countryCodeHandler;
    }
}
```

### Service de Segmentation

```php
class ChainOfResponsibilityPhoneSegmentationService implements PhoneSegmentationServiceInterface
{
    private $validator;
    private $handlerFactory;

    public function __construct(
        PhoneNumberValidatorInterface $validator,
        SegmentationHandlerFactory $handlerFactory
    ) {
        $this->validator = $validator;
        $this->handlerFactory = $handlerFactory;
    }

    public function segmentPhoneNumber(PhoneNumber $phoneNumber): PhoneNumber
    {
        if (!$this->validator->validate($phoneNumber)) {
            throw new InvalidArgumentException('Invalid phone number format');
        }

        $chain = $this->handlerFactory->createChain();
        return $chain->handle($phoneNumber);
    }
}
```

## Configuration dans le Conteneur d'Injection de Dépendances

```php
// Dans di.php
SegmentationHandlerFactory::class => factory(function () {
    return new SegmentationHandlerFactory();
}),

PhoneSegmentationServiceInterface::class => factory(function (Container $container) {
    return new ChainOfResponsibilityPhoneSegmentationService(
        $container->get(PhoneNumberValidatorInterface::class),
        $container->get(SegmentationHandlerFactory::class)
    );
}),
```

## Avantages Obtenus

1. **Séparation des responsabilités** : Chaque handler est responsable d'une seule étape du processus.
2. **Extensibilité** : Ajout facile de nouvelles étapes sans modifier le code existant.
3. **Flexibilité** : Possibilité de réorganiser ou de remplacer des étapes spécifiques.
4. **Réutilisabilité** : Les handlers peuvent être réutilisés dans différentes chaînes.
5. **Testabilité** : Chaque handler peut être testé indépendamment.

## Exemple d'Extension

Pour ajouter une nouvelle étape de segmentation, il suffit de créer un nouveau handler et de l'intégrer dans la chaîne :

```php
class SpecialNumberHandler extends AbstractSegmentationHandler
{
    protected function process(PhoneNumber $phoneNumber): PhoneNumber
    {
        // Logique pour détecter les numéros spéciaux
        return $phoneNumber;
    }
}

// Modification de la factory
public function createChain(): SegmentationHandlerInterface
{
    $countryCodeHandler = new CountryCodeHandler();
    $operatorCodeHandler = new OperatorCodeHandler();
    $specialNumberHandler = new SpecialNumberHandler();
    $subscriberNumberHandler = new SubscriberNumberHandler();

    $countryCodeHandler->setNext($operatorCodeHandler)
                       ->setNext($specialNumberHandler)
                       ->setNext($subscriberNumberHandler);

    return $countryCodeHandler;
}
```

## Conclusion

L'implémentation du pattern Chain of Responsibility a considérablement amélioré la flexibilité et l'extensibilité du processus de segmentation des numéros de téléphone. Cette approche permet d'ajouter facilement de nouvelles étapes de segmentation sans modifier le code existant, tout en éliminant la duplication de code et en améliorant la testabilité.
