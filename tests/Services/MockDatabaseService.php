<?php

namespace Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Service pour gérer la base de données de test
 */
class MockDatabaseService
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    
    /**
     * Constructeur
     * 
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Recrée le schéma de la base de données
     * 
     * @return void
     */
    public function recreateSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
    
    /**
     * Vide toutes les tables
     * 
     * @return void
     */
    public function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        
        // Désactiver les contraintes de clé étrangère
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        foreach ($metadata as $classMetadata) {
            $table = $classMetadata->getTableName();
            
            $connection->executeStatement('DELETE FROM ' . $table);
        }
        
        // Réactiver les contraintes de clé étrangère
        $connection->executeStatement('PRAGMA foreign_keys = ON');
    }
    
    /**
     * Persiste et flush les entités
     * 
     * @param array $entities
     * @return void
     */
    public function persistEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        
        $this->entityManager->flush();
    }
}