<?php

declare(strict_types=1);

/**
 * Script de test pour vérifier la configuration du schéma GraphQL
 * 
 * Ce script vérifie que le schéma GraphQL est correctement configuré pour
 * la requête fetchApprovedWhatsAppTemplates et teste la résolution de cette requête.
 */

require_once __DIR__ . '/../bootstrap.php';

use TheCodingMachine\GraphQLite\SchemaFactory;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Psr\Container\ContainerInterface;

// Récupérer le conteneur
$container = require __DIR__ . '/../src/container.php';

echo "Test du schéma GraphQL pour fetchApprovedWhatsAppTemplates\n";
echo "========================================================\n\n";

try {
    // Récupérer le schéma GraphQL
    $schema = $container->get(Schema::class);
    echo "Schéma GraphQL récupéré avec succès\n";
    
    // Analyser le schéma pour vérifier que la requête existe
    $queryType = $schema->getQueryType();
    $fields = $queryType->getFields();
    
    if (isset($fields['fetchApprovedWhatsAppTemplates'])) {
        echo "La requête 'fetchApprovedWhatsAppTemplates' existe dans le schéma\n";
        $field = $fields['fetchApprovedWhatsAppTemplates'];
        
        echo "Type de retour: " . $field->getType() . "\n";
        echo "Est-ce un type non-nullable? " . ($field->getType()->isNonNull() ? "Oui" : "Non") . "\n";
        
        $args = $field->args;
        echo "Arguments: " . count($args) . "\n";
        foreach ($args as $arg) {
            echo " - " . $arg->name . " (" . $arg->getType() . ")\n";
        }
    } else {
        echo "ERREUR: La requête 'fetchApprovedWhatsAppTemplates' n'existe PAS dans le schéma\n";
        echo "Requêtes disponibles:\n";
        foreach (array_keys($fields) as $fieldName) {
            echo " - $fieldName\n";
        }
    }
    
    // Exécuter une requête GraphQL simple
    echo "\nTest d'exécution de la requête:\n";
    $query = '
    {
      fetchApprovedWhatsAppTemplates {
        id
        name
        category
        language
        status
      }
    }
    ';
    
    // Créer un contexte avec un utilisateur authentifié
    $context = [
        'user' => $container->get(\App\Repositories\Interfaces\UserRepositoryInterface::class)->findOneById(1)
    ];
    
    $result = GraphQL::executeQuery($schema, $query, null, $context);
    $output = $result->toArray();
    
    if (isset($output['errors'])) {
        echo "ERREUR d'exécution de la requête:\n";
        foreach ($output['errors'] as $error) {
            echo " - " . $error['message'] . "\n";
            if (isset($error['trace'])) {
                echo "   Trace: " . print_r($error['trace'], true) . "\n";
            }
        }
    } else {
        echo "Requête exécutée avec succès\n";
        echo "Résultat: " . json_encode($output['data']) . "\n";
    }
    
} catch (\Throwable $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest terminé\n";