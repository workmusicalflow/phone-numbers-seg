<?php

/**
 * Convertir toutes les entités WhatsApp pour utiliser les Attributes PHP 8
 */

$entitiesPath = __DIR__ . '/../src/Entities/WhatsApp/';
$files = glob($entitiesPath . '*.php');

foreach ($files as $file) {
    if (strpos($file, 'backup') !== false) {
        continue; // Skip backup files
    }
    
    echo "Converting: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    
    // Mapping des annotations vers attributes
    $replacements = [
        // Entity
        '/@ORM\\\\Entity/' => '#[ORM\\Entity]',
        '/@ORM\\\\Table\((.*?)\)/' => function($matches) {
            // Transformer les paramètres
            $params = $matches[1];
            $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
            $params = preg_replace('/indexes=\{(.*?)\}/', 'indexes: [$1]', $params);
            return "#[ORM\\Table($params)]";
        },
        '/@ORM\\\\HasLifecycleCallbacks/' => '#[ORM\\HasLifecycleCallbacks]',
        
        // Columns
        '/@ORM\\\\Id/' => '#[ORM\\Id]',
        '/@ORM\\\\GeneratedValue/' => '#[ORM\\GeneratedValue]',
        '/@ORM\\\\Column\((.*?)\)/' => function($matches) {
            $params = $matches[1];
            // Transformer les paramètres
            $params = preg_replace('/type="([^"]+)"/', 'type: "$1"', $params);
            $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
            $params = preg_replace('/length=(\d+)/', 'length: $1', $params);
            $params = preg_replace('/nullable=true/', 'nullable: true', $params);
            $params = preg_replace('/unique=true/', 'unique: true', $params);
            $params = preg_replace('/options=\{(.*?)\}/', 'options: [$1]', $params);
            $params = preg_replace('"default": (\d+)', '"default" => $1', $params);
            return "#[ORM\\Column($params)]";
        },
        
        // Relations
        '/@ORM\\\\ManyToOne\((.*?)\)/' => function($matches) {
            $params = $matches[1];
            $params = preg_replace('/targetEntity="([^"]+)"/', 'targetEntity: "$1"', $params);
            return "#[ORM\\ManyToOne($params)]";
        },
        '/@ORM\\\\JoinColumn\((.*?)\)/' => function($matches) {
            $params = $matches[1];
            $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
            $params = preg_replace('/nullable=true/', 'nullable: true', $params);
            $params = preg_replace('/nullable=false/', 'nullable: false', $params);
            return "#[ORM\\JoinColumn($params)]";
        },
        
        // Index
        '/@ORM\\\\Index\((.*?)\)/' => function($matches) {
            $params = $matches[1];
            $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
            $params = preg_replace('/columns=\{"([^"]+)"\}/', 'columns: ["$1"]', $params);
            $params = preg_replace('/columns=\{(.*?)\}/', 'columns: [$1]', $params);
            return "@ORM\\Index($params)";
        },
        
        // Lifecycle callbacks
        '/@ORM\\\\PreUpdate/' => '#[ORM\\PreUpdate]',
        '/@ORM\\\\PrePersist/' => '#[ORM\\PrePersist]',
        
        // Remove empty comment blocks
        '/\/\*\*\s*\n\s*\*\/\s*\n/' => '',
        
        // Clean up multi-line annotations to attributes
        '/\/\*\*\s*\n(\s*\*\s*.*?\n)*\s*\*\s*(#\[ORM\\\\.*?\])\s*\n\s*\*\//' => '$2',
    ];
    
    // Appliquer les remplacements simples
    foreach ($replacements as $pattern => $replacement) {
        if (is_callable($replacement)) {
            $content = preg_replace_callback($pattern, $replacement, $content);
        } else {
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    
    // Traiter les commentaires avec annotations multiples
    $content = preg_replace_callback(
        '/\/\*\*\s*\n(\s*\*\s*.*?\n)*\s*\*\s*(@ORM\\\\.*?)\s*\n(\s*\*\s*@ORM\\\\.*?\s*\n)*\s*\*\//',
        function($matches) {
            // Extraire toutes les annotations ORM
            preg_match_all('/@ORM\\\\[^\n]+/', $matches[0], $annotations);
            
            $attributes = [];
            foreach ($annotations[0] as $annotation) {
                // Convertir en attribute
                $attr = preg_replace('/@ORM\\\\/', '#[ORM\\', $annotation);
                
                // Ajuster les paramètres
                if (preg_match('/#\[ORM\\\\Column\((.*?)\)/', $attr, $columnMatch)) {
                    $params = $columnMatch[1];
                    $params = preg_replace('/type="([^"]+)"/', 'type: "$1"', $params);
                    $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
                    $params = preg_replace('/length=(\d+)/', 'length: $1', $params);
                    $params = preg_replace('/nullable=true/', 'nullable: true', $params);
                    $params = preg_replace('/nullable=false/', 'nullable: false', $params);
                    $params = preg_replace('/unique=true/', 'unique: true', $params);
                    $attr = "#[ORM\\Column($params)]";
                }
                
                if (preg_match('/#\[ORM\\\\ManyToOne\((.*?)\)/', $attr, $mtoMatch)) {
                    $params = $mtoMatch[1];
                    $params = preg_replace('/targetEntity="([^"]+)"/', 'targetEntity: "$1"', $params);
                    $attr = "#[ORM\\ManyToOne($params)]";
                }
                
                if (preg_match('/#\[ORM\\\\JoinColumn\((.*?)\)/', $attr, $joinMatch)) {
                    $params = $joinMatch[1];
                    $params = preg_replace('/name="([^"]+)"/', 'name: "$1"', $params);
                    $params = preg_replace('/nullable=true/', 'nullable: true', $params);
                    $params = preg_replace('/nullable=false/', 'nullable: false', $params);
                    $attr = "#[ORM\\JoinColumn($params)]";
                }
                
                $attributes[] = $attr . ']';
            }
            
            // Retourner les attributes sur des lignes séparées
            return implode("\n    ", $attributes);
        },
        $content
    );
    
    // Sauvegarder le fichier
    file_put_contents($file, $content);
    echo "   ✓ Converted: " . basename($file) . "\n";
}

echo "\nConversion terminée!\n";