<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/config/di.php');
$container = $containerBuilder->build();
$entityManager = $container->get(EntityManagerInterface::class);

// Récupérer le template qshe_day_3
$template = $entityManager->getRepository('App\Entities\WhatsApp\WhatsAppTemplate')
    ->findOneBy(['name' => 'qshe_day_3']);

if ($template) {
    echo "Template: " . $template->getName() . "\n";
    echo "Components:\n";
    $components = json_decode($template->getComponents(), true);
    print_r($components);
    
    echo "\n\nAnalyse des composants:\n";
    foreach ($components as $component) {
        echo "- Type: " . $component['type'] . "\n";
        if ($component['type'] === 'HEADER' && isset($component['format'])) {
            echo "  Format: " . $component['format'] . "\n";
            echo "  => Ce template a un en-tête " . $component['format'] . " qui nécessite un média\n";
        }
        if ($component['type'] === 'BODY' && isset($component['text'])) {
            preg_match_all('/{{(\d+)}}/', $component['text'], $matches);
            if (count($matches[1]) > 0) {
                echo "  Variables trouvées: " . implode(', ', $matches[0]) . "\n";
            } else {
                echo "  Pas de variables dans le corps\n";
            }
        }
    }
}