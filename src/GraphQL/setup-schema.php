<?php

declare(strict_types=1);

namespace App\GraphQL;

use TheCodingMachine\GraphQLite\SchemaFactory;
use GraphQL\Type\Schema;
use TheCodingMachine\GraphQLite\Context\Context;
use App\GraphQL\Types\DateTimeType;
use App\GraphQL\Controllers\WhatsApp\WhatsAppMonitoringController;

/**
 * Configure et initialise le schéma GraphQL
 */
class SchemaSetup
{
    /**
     * Initialise le schéma GraphQL en enregistrant le type DateTime directement dans le schéma
     * Cette méthode est utilisée car elle est indépendante de SchemaFactory
     */
    public static function setupDateTimeType(Schema $schema): Schema
    {
        // Vérifier si le type DateTime existe déjà
        $typeMap = $schema->getTypeMap();
        if (!isset($typeMap['DateTime'])) {
            // Enregistrer manuellement le type DateTime dans le schéma
            $typeMap['DateTime'] = new DateTimeType();
            
            // Mettre à jour le schéma avec ce nouveau type
            $reflection = new \ReflectionClass($schema);
            $typeMapProperty = $reflection->getProperty('typeMap');
            $typeMapProperty->setAccessible(true);
            $typeMapProperty->setValue($schema, $typeMap);
        }
        
        return $schema;
    }
}
}