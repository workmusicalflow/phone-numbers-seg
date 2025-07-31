<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use TheCodingMachine\GraphQLite\Types\TypeAnnotations;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException;

/**
 * Type scalaire DateTime pour GraphQL
 */
class DateTimeType extends ScalarType
{
    public function __construct()
    {
        $this->name = 'DateTime';
        $this->description = 'Type de date et heure au format ISO 8601 (exemple: 2025-05-21T14:30:00Z)';
        parent::__construct();
    }

    /**
     * Serializes a DateTime object to a string
     *
     * @param mixed $value
     * @return string
     * @throws Error
     */
    public function serialize($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c'); // ISO 8601 format
        }
        
        // On peut aussi accepter un timestamp
        if (is_numeric($value)) {
            $date = new \DateTime();
            $date->setTimestamp((int) $value);
            return $date->format('c');
        }
        
        // Ou une chaîne de date valide
        if (is_string($value)) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $value);
            if ($date) {
                return $date->format('c');
            }
            
            $date = date_create($value);
            if ($date) {
                return $date->format('c');
            }
        }
        
        throw new Error(sprintf(
            'DateTime cannot represent non-date value: %s', 
            Utils::printSafe($value)
        ));
    }

    /**
     * Parses a DateTime string
     *
     * @param mixed $value
     * @return \DateTime
     * @throws Error
     */
    public function parseValue($value): \DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_string($value)) {
            // Essayer de parser la chaîne de date
            try {
                $date = new \DateTime($value);
                return $date;
            } catch (\Exception $e) {
                // Continuer avec d'autres tentatives
            }
            
            // Essayer de parser comme date ISO
            $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $value);
            if ($date) {
                return $date;
            }
        }
        
        throw new Error(sprintf(
            'DateTime cannot represent non-date value: %s', 
            Utils::printSafe($value)
        ));
    }

    /**
     * Parses a DateTime AST value
     *
     * @param mixed $valueNode
     * @return \DateTime
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null): \DateTime
    {
        if (!property_exists($valueNode, 'value') || !is_string($valueNode->value)) {
            throw new Error(
                'DateTime must be a string value',
                $valueNode
            );
        }
        
        return $this->parseValue($valueNode->value);
    }
}