<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\PhoneNumberNormalizer;
use App\Services\Strategies\IvoryCoastSegmentationStrategy;
use App\Entities\PhoneNumber;

// Create a logger to capture and output normalization steps
$logger = new Monolog\Logger('test');
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://output', Monolog\Logger::DEBUG));

echo "======== TEST CÔTE D'IVOIRE PHONE NUMBER FORMATS ========\n\n";

// Initialize the normalizer with Côte d'Ivoire as default country code
$normalizer = new PhoneNumberNormalizer('225', $logger);
$strategy = new IvoryCoastSegmentationStrategy();

// Define test phone numbers in various formats
$testNumbers = [
    'Format international avec +' => '+22507XXXXXXXX',
    'Format international sans +' => '22507XXXXXXXX',
    'Format international avec 00' => '0022507XXXXXXXX',
    'Format local avec 0 initial (Orange)' => '07XXXXXXXX',
    'Format local sans 0 initial (Orange)' => '7XXXXXXXX',
    'Format local avec 0 initial (MTN)' => '05XXXXXXXX',
    'Format local sans 0 initial (MTN)' => '5XXXXXXXX',
    'Format local avec 0 initial (Moov)' => '01XXXXXXXX',
    'Format local sans 0 initial (Moov)' => '1XXXXXXXX',
    'Format avec espaces et tirets' => '+225 07-XX-XX-XX-XX',
    'Format avec parenthèses' => '(+225) 07 XX XX XX',
    'Format avec points' => '+225.07.XX.XX.XX',
];

// Helper function to create a very simple phone number entity for testing
function createSimplePhoneNumber(string $number): PhoneNumber {
    $phoneNumber = new PhoneNumber();
    $reflection = new ReflectionClass(PhoneNumber::class);
    $property = $reflection->getProperty('number');
    $property->setAccessible(true);
    $property->setValue($phoneNumber, $number);
    return $phoneNumber;
}

// Test each number
foreach ($testNumbers as $description => $number) {
    echo "\n----- $description: $number -----\n";
    
    // Normalize to E.164 format
    $normalized = $normalizer->normalize($number);
    echo "Normalisé (E.164): $normalized\n";
    
    // Format for WhatsApp
    $whatsAppNumber = $normalizer->normalizeForWhatsApp($number);
    echo "Format WhatsApp: $whatsAppNumber\n";
    
    // Only segment if we have a valid normalized number
    if ($normalized) {
        // Create a phone number entity with the normalized number
        $phoneEntity = createSimplePhoneNumber($normalized);
        
        // Segment the phone number
        $segmentedPhoneEntity = $strategy->segment($phoneEntity);
        
        // Get the segments (for display purposes only)
        $segments = $segmentedPhoneEntity->getTechnicalSegments();
        
        // Display segmentation details
        echo "Segmentation:\n";
        foreach ($segments as $segment) {
            $type = $segment->getSegmentType();
            $value = $segment->getValue();
            echo "  - $type: $value\n";
        }
    } else {
        echo "Impossible de segmenter un numéro invalide.\n";
    }
    
    echo "\n";
}

echo "\n======== TEST TERMINÉ ========\n";