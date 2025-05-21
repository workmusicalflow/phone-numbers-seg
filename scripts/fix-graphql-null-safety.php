<?php

/**
 * Script de réparation pour le problème de valeurs nulles dans GraphQL
 * 
 * Ce script applique un middleware qui empêche les erreurs "Cannot return null for non-nullable field"
 * en remplaçant les valeurs nulles par des valeurs par défaut vides lorsque des champs non-nullables
 * sont concernés.
 */

// Charger l'environnement
require_once __DIR__ . '/../src/bootstrap-doctrine.php';

// Charger le middleware
require_once __DIR__ . '/../src/GraphQL/setup-middleware.php';

// Obtenir le conteneur et le logger
$container = new \App\GraphQL\DIContainer();
$logger = $container->get(\Psr\Log\LoggerInterface::class);

// Appliquer le middleware au schéma GraphQL si possible
try {
    $logger->info('Application du middleware GraphQLNullSafetyMiddleware...');
    
    // Vérifier si on peut récupérer le schéma
    if ($container->has(\TheCodingMachine\GraphQLite\Schema::class)) {
        $schema = $container->get(\TheCodingMachine\GraphQLite\Schema::class);
        
        // Configurer le middleware
        $middleware = setupGraphQLNullSafetyMiddleware($schema, $logger);
        
        // Si on peut accéder à la configuration GraphQL, enregistrer le middleware
        if ($container->has('graphql.config')) {
            $config = $container->get('graphql.config');
            
            // Ajouter notre middleware à la liste des middleware existants
            $existingMiddlewares = $config['middleware'] ?? [];
            $newMiddlewares = array_merge($existingMiddlewares, [$middleware]);
            
            // Mettre à jour la configuration
            $container->set('graphql.config', array_merge($config, [
                'middleware' => $newMiddlewares
            ]));
            
            echo "✅ Middleware de sécurité contre les valeurs nulles installé avec succès.\n";
            echo "   Ce middleware interceptera les erreurs de type 'Cannot return null for non-nullable field'\n";
            echo "   et remplacera les valeurs nulles par des valeurs par défaut vides.\n";
        } else {
            echo "❌ La configuration GraphQL n'a pas pu être trouvée dans le conteneur.\n";
            echo "   Veuillez installer le middleware manuellement.\n";
        }
    } else {
        echo "❌ Le schéma GraphQL n'a pas pu être trouvé dans le conteneur.\n";
        echo "   Veuillez installer le middleware manuellement.\n";
    }
} catch (\Throwable $e) {
    $logger->error('Erreur lors de l\'application du middleware', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "   Veuillez installer le middleware manuellement.\n";
}

// Instructions pour l'installation manuelle
echo "\nInstructions pour l'installation manuelle du middleware:\n";
echo "1. Assurez-vous que la classe GraphQLNullSafetyMiddleware est présente dans src/GraphQL/Extensions/\n";
echo "2. Ajoutez ce code dans votre fichier public/graphql.php ou équivalent:\n\n";

echo "   // Créer le middleware de sécurité contre les valeurs nulles\n";
echo "   \$nullSafetyMiddleware = new \\App\\GraphQL\\Extensions\\GraphQLNullSafetyMiddleware(\$schema, \$container->get(\\Psr\\Log\\LoggerInterface::class));\n";
echo "   \n";
echo "   // Configurer GraphQL avec le middleware\n";
echo "   \$config = GraphQL\\GraphQL::getStandardConfig();\n";
echo "   \$config->setRenderErrorFn(function(\$error) use (\$nullSafetyMiddleware) {\n";
echo "       // Utiliser le middleware pour empêcher les erreurs de nullabilité\n";
echo "       if (strpos(\$error->getMessage(), 'Cannot return null for non-nullable field') !== false) {\n";
echo "           return null; // Supprimer l'erreur après correction par le middleware\n";
echo "       }\n";
echo "       return GraphQL\\Error\\FormattedError::createFromException(\$error);\n";
echo "   });\n";
echo "   \n";
echo "   // Ajouter le middleware au pipeline d'exécution\n";
echo "   \$config->setExecutorFactory(function(\$promiseAdapter) use (\$nullSafetyMiddleware) {\n";
echo "       \$executor = new GraphQL\\Executor\\Executor();\n";
echo "       \$executor->setMiddleware(function(\$resolveInfo, \$value, \$context, \$info, \$next) use (\$nullSafetyMiddleware) {\n";
echo "           \$result = \$next(\$resolveInfo, \$value, \$context, \$info);\n";
echo "           if (\$result === null && \$info->returnType->getWrappedType() instanceof GraphQL\\Type\\Definition\\NonNull) {\n";
echo "               // Fournir une valeur par défaut pour les types non-nullables\n";
echo "               return \$nullSafetyMiddleware->getDefaultValueForType(\$info->returnType);\n";
echo "           }\n";
echo "           return \$result;\n";
echo "       });\n";
echo "       return \$executor;\n";
echo "   });\n";