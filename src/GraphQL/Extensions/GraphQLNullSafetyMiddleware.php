<?php

declare(strict_types=1);

namespace App\GraphQL\Extensions;

use GraphQL\Executor\ExecutionResult;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Schema;

/**
 * Middleware pour garantir que les champs non-nullables ne renvoient jamais null
 * 
 * Ce middleware s'assure que les champs définis comme non-nullables dans le schéma
 * ne retournent jamais null, mais plutôt une valeur par défaut vide (tableau vide, chaîne vide, etc.)
 */
class GraphQLNullSafetyMiddleware
{
    private LoggerInterface $logger;
    private Schema $schema;
    
    public function __construct(Schema $schema, LoggerInterface $logger)
    {
        $this->schema = $schema;
        $this->logger = $logger;
    }
    
    /**
     * Processus de middleware qui vérifie et corrige les valeurs nulles
     */
    public function process(ExecutionResult $result): ExecutionResult
    {
        // S'il y a des erreurs, les traiter
        if (!empty($result->errors)) {
            foreach ($result->errors as $error) {
                $errorMessage = $error->getMessage();
                
                // Vérifier si l'erreur concerne un champ non-nullable qui retourne null
                if (strpos($errorMessage, 'Cannot return null for non-nullable field') !== false) {
                    $this->logger->warning('Erreur de nullabilité GraphQL détectée', [
                        'error' => $errorMessage,
                        'path' => $error->getPath() ?? []
                    ]);
                    
                    // Récupérer le chemin de l'erreur
                    $path = $error->getPath() ?? [];
                    
                    // Si le chemin est vide, on ne peut pas corriger
                    if (empty($path)) {
                        continue;
                    }
                    
                    // Identifier le type attendu basé sur l'erreur
                    $fieldName = end($path);
                    $match = null;
                    if (preg_match('/Cannot return null for non-nullable field "([^"]+)\.([^"]+)"/', $errorMessage, $match)) {
                        $typeName = $match[1];
                        $fieldName = $match[2];
                        
                        // Corriger la valeur nulle dans le résultat
                        $this->fixNullValue($result->data, $path, $typeName, $fieldName);
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Corrige une valeur nulle dans le résultat en fournissant une valeur par défaut
     */
    private function fixNullValue(array &$data, array $path, string $typeName, string $fieldName): void
    {
        // Récupérer la référence à la valeur problématique
        $current = &$data;
        $lastIndex = count($path) - 1;
        
        // Naviguer dans la structure de données jusqu'au parent de la valeur null
        for ($i = 0; $i < $lastIndex; $i++) {
            $key = $path[$i];
            if (!isset($current[$key])) {
                // Si le chemin n'existe pas, on ne peut pas corriger
                return;
            }
            
            $current = &$current[$key];
        }
        
        // Appliquer la valeur par défaut en fonction du type attendu
        $field = $path[$lastIndex];
        
        // Si la valeur est déjà définie et non-nulle, ne rien faire
        if (isset($current[$field]) && $current[$field] !== null) {
            return;
        }
        
        // Déterminer une valeur par défaut en fonction du nom et du type
        if (stripos($field, 'templates') !== false || stripos($field, 'list') !== false) {
            // Pour les listes
            $current[$field] = [];
        } elseif (stripos($field, 'count') !== false) {
            // Pour les compteurs
            $current[$field] = 0;
        } elseif (stripos($field, 'has') === 0 || stripos($field, 'is') === 0) {
            // Pour les booléens
            $current[$field] = false;
        } else {
            // Par défaut une chaîne vide
            $current[$field] = '';
        }
        
        $this->logger->info('GraphQL null corrigé', [
            'path' => implode('.', $path),
            'type' => $typeName,
            'field' => $fieldName,
            'defaultValue' => $current[$field]
        ]);
    }
}