<?php

/**
 * Configuration pour le client REST WhatsApp
 */

use App\Services\Interfaces\WhatsApp\WhatsAppRestClientInterface;
use App\Services\WhatsApp\WhatsAppRestClient;
use function DI\autowire;
use function DI\get;
use Psr\Log\LoggerInterface;

return [
    // Définition du client REST WhatsApp
    WhatsAppRestClientInterface::class => autowire(WhatsAppRestClient::class)
        ->constructorParameter('logger', get(LoggerInterface::class))
        ->constructorParameter('baseUrl', fn() => isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost:8000'),
];