<?php

// Test pour vérifier le format de date envoyé par le frontend

$testDates = [
    "2025-05-17",           // Format attendu
    "17/05/2025",           // Format français
    "05/17/2025",           // Format US
    "2025-05-17T00:00:00",  // ISO avec temps
    "2025-05-17 00:00:00"   // SQL format
];

echo "Test de conversion des formats de date\n";
echo "=====================================\n";

foreach ($testDates as $dateStr) {
    try {
        $date = new DateTime($dateStr);
        echo "Input: '$dateStr'\n";
        echo "  -> DateTime: " . $date->format('Y-m-d H:i:s') . "\n";
        echo "  -> Date only: " . $date->format('Y-m-d') . "\n";
        echo "  -> Start of day: " . (new DateTime($date->format('Y-m-d') . ' 00:00:00'))->format('Y-m-d H:i:s') . "\n";
        echo "  -> End of day: " . (new DateTime($date->format('Y-m-d') . ' 23:59:59'))->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "Input: '$dateStr' -> ERREUR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Test spécifique pour le jour en cours
echo "Test spécifique pour aujourd'hui\n";
echo "================================\n";
$today = date('Y-m-d');
$todayDateTime = new DateTime($today);
echo "Aujourd'hui (date()): $today\n";
echo "Aujourd'hui (DateTime): " . $todayDateTime->format('Y-m-d H:i:s') . "\n";
echo "Début de journée: " . (new DateTime($today . ' 00:00:00'))->format('Y-m-d H:i:s') . "\n";
echo "Fin de journée: " . (new DateTime($today . ' 23:59:59'))->format('Y-m-d H:i:s') . "\n";