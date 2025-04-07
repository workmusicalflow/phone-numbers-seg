<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Implémentation simple de l'interface PSR-3 LoggerInterface
 */
class SimpleLogger implements LoggerInterface
{
    private string $name;
    private string $logFile;

    /**
     * Constructeur
     *
     * @param string $name Nom du logger
     * @param string|null $logFile Chemin du fichier de log (optionnel)
     */
    public function __construct(string $name, ?string $logFile = null)
    {
        $this->name = $name;
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';

        // Créer le répertoire de logs s'il n'existe pas
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[$timestamp] {$this->name}.$level: $message$contextStr" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}
