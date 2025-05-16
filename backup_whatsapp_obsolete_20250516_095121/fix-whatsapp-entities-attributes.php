<?php

$entitiesDir = __DIR__ . '/../src/Entities/WhatsApp';
$entities = [
    'WhatsAppMessageHistory' => 'whatsapp_message_history',
    'WhatsAppTemplate' => 'whatsapp_templates',
    'WhatsAppUserTemplate' => 'whatsapp_user_templates'
];

foreach ($entities as $className => $tableName) {
    $filePath = $entitiesDir . '/' . $className . '.php';
    
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Add missing Entity and Table attributes
    if (!str_contains($content, '#[ORM\Entity]')) {
        // Find the HasLifecycleCallbacks or class declaration
        if (preg_match('/^#\[ORM\\\\HasLifecycleCallbacks\]\s*\nclass/m', $content)) {
            $content = preg_replace(
                '/^(#\[ORM\\\\HasLifecycleCallbacks\])\s*\n(class)/m',
                '#[ORM\Entity]' . "\n" . '#[ORM\Table(name: "' . $tableName . '")]' . "\n" . '$1' . "\n" . '$2',
                $content
            );
        } else {
            $content = preg_replace(
                '/^(class ' . $className . ')/m',
                '#[ORM\Entity]' . "\n" . '#[ORM\Table(name: "' . $tableName . '")]' . "\n" . '$1',
                $content
            );
        }
    }
    
    // Fix id column
    $content = preg_replace(
        '/#\[ORM\\\\Column\(\)\]\s*\n\s*private int \$id;/',
        '#[ORM\Id]' . "\n" . '    #[ORM\GeneratedValue]' . "\n" . '    #[ORM\Column(type: "integer")]' . "\n" . '    private int $id;',
        $content
    );
    
    // Add declare(strict_types=1) if missing
    if (!str_contains($content, 'declare(strict_types=1)')) {
        $content = preg_replace('/^<\?php\s*\n/m', '<?php' . "\n\n" . 'declare(strict_types=1);' . "\n", $content);
    }
    
    file_put_contents($filePath, $content);
    echo "Fixed: $className\n";
}

echo "All entities fixed.\n";