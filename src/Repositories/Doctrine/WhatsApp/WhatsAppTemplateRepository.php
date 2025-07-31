<?php

namespace App\Repositories\Doctrine\WhatsApp;

use App\Repositories\Doctrine\BaseRepository;
use App\Repositories\Interfaces\WhatsApp\WhatsAppTemplateRepositoryInterface;
use App\Entities\WhatsApp\WhatsAppTemplate;

/**
 * Repository Doctrine pour les templates WhatsApp
 */
class WhatsAppTemplateRepository extends BaseRepository implements WhatsAppTemplateRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save($template)
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
        return $template;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByMetaNameAndLanguage(string $metaTemplateName, string $languageCode): ?WhatsAppTemplate
    {
        return $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->findOneBy([
                'name' => $metaTemplateName,  // changé de metaTemplateName à name
                'language' => $languageCode   // changé de languageCode à language
            ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findApproved(?string $category = null, ?string $languageCode = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', 'APPROVED');
        
        if ($category !== null) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }
        
        if ($languageCode !== null) {
            $qb->andWhere('t.language = :language')
               ->setParameter('language', $languageCode);
        }
        
        return $qb->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByCategory(string $category): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->findBy(['category' => $category], ['name' => 'ASC']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByLanguage(string $languageCode): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->findBy(['language' => $languageCode], ['name' => 'ASC']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateStatus(int $templateId, string $status): bool
    {
        $template = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)->find($templateId);
        
        if ($template === null) {
            return false;
        }
        
        $template->setStatus($status);
        $this->getEntityManager()->flush();
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteById($templateId): bool
    {
        $template = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)->find($templateId);
        
        if ($template === null) {
            return false;
        }
        
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByStatus(): array
    {
        $results = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
        
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['status']] = (int) $result['count'];
        }
        
        // Assurer que tous les statuts sont représentés
        $allStatuses = [
            'PENDING',
            'APPROVED',
            'REJECTED'
        ];
        
        foreach ($allStatuses as $status) {
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
        }
        
        return $counts;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByAdvancedCriteria(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t');
        
        // Ajouter les conditions de base
        $this->addBasicCriteria($qb, $criteria);
        
        // Ajouter les conditions avancées
        $this->addAdvancedCriteria($qb, $criteria);
        
        // Ajouter le tri
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy('t.' . $field, $order);
        }
        
        // Ajouter la limite et l'offset
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByHeaderFormat(string $headerFormat, ?string $status = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.headerFormat = :headerFormat')
            ->setParameter('headerFormat', $headerFormat);
        
        if ($status !== null) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }
        
        $qb->orderBy('t.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByVariableCount(int $minVariables, ?int $maxVariables = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.bodyVariablesCount >= :minVariables')
            ->setParameter('minVariables', $minVariables);
        
        if ($maxVariables !== null) {
            $qb->andWhere('t.bodyVariablesCount <= :maxVariables')
               ->setParameter('maxVariables', $maxVariables);
        }
        
        $qb->orderBy('t.bodyVariablesCount', 'DESC')
           ->addOrderBy('t.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findWithButtons(?int $buttonCount = null): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.buttonsCount > 0');
        
        if ($buttonCount !== null) {
            $qb->andWhere('t.buttonsCount = :buttonCount')
               ->setParameter('buttonCount', $buttonCount);
        }
        
        $qb->orderBy('t.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function searchInBodyText(string $searchText): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.bodyText LIKE :searchText')
            ->setParameter('searchText', '%' . $searchText . '%')
            ->orderBy('t.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findMostUsed(int $limit = 10): array
    {
        $qb = $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->createQueryBuilder('t')
            ->where('t.usageCount > 0')
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findApprovedTemplates(array $filters = []): array
    {
        // Construire les critères basés sur les filtres
        $criteria = ['status' => 'APPROVED'];
        
        // Filtres optionnels
        if (isset($filters['name']) && $filters['name']) {
            $criteria['name'] = $filters['name'];
        }
        
        if (isset($filters['language']) && $filters['language']) {
            $criteria['language'] = $filters['language'];
        }
        
        if (isset($filters['category']) && $filters['category']) {
            $criteria['category'] = $filters['category'];
        }
        
        // Tri par ordre alphabétique du nom par défaut
        $orderBy = ['name' => 'ASC'];
        
        // Utiliser la recherche avancée existante
        $templates = $this->findByAdvancedCriteria($criteria, $orderBy);
        
        // Convertir les entités en tableaux pour l'API
        $templatesArray = [];
        foreach ($templates as $template) {
            // Construire le tableau de composants
            $components = [];
            if ($template->getComponentsJson()) {
                $components = json_decode($template->getComponentsJson(), true) ?? [];
            }
            
            $templatesArray[] = [
                'id' => $template->getTemplateId(),
                'name' => $template->getName(),
                'category' => $template->getCategory(),
                'language' => $template->getLanguage(),
                'status' => $template->getStatus(),
                'components' => $components,
                'description' => $template->getDescription(),
                'componentsJson' => $template->getComponentsJson(),
                'bodyVariablesCount' => $template->getBodyVariablesCount(),
                'hasMediaHeader' => $template->getHasMediaHeader(),
                'hasButtons' => $template->getHasButtons(),
                'buttonsCount' => $template->getButtonsCount(),
                'hasFooter' => $template->getHasFooter()
            ];
        }
        
        return $templatesArray;
    }
    
    /**
     * Ajoute les critères de base au query builder
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $criteria
     */
    private function addBasicCriteria(\Doctrine\ORM\QueryBuilder $qb, array $criteria): void
    {
        $paramIndex = 1;
        
        // Filtres de base
        $basicFields = [
            'name' => 'LIKE', // Recherche partielle pour le nom
            'language' => '=',
            'category' => '=',
            'status' => '=',
            'isActive' => '=',
            'isGlobal' => '='
        ];
        
        foreach ($basicFields as $field => $operator) {
            if (isset($criteria[$field])) {
                $paramName = 'param' . $paramIndex++;
                
                if ($operator === 'LIKE') {
                    $qb->andWhere("t.$field LIKE :$paramName")
                       ->setParameter($paramName, '%' . $criteria[$field] . '%');
                } else {
                    $qb->andWhere("t.$field $operator :$paramName")
                       ->setParameter($paramName, $criteria[$field]);
                }
            }
        }
    }
    
    /**
     * Ajoute les critères avancés au query builder
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array $criteria
     */
    private function addAdvancedCriteria(\Doctrine\ORM\QueryBuilder $qb, array $criteria): void
    {
        $paramIndex = 100; // Commencer à un index élevé pour éviter les conflits
        
        // Format d'en-tête
        if (isset($criteria['headerFormat'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.headerFormat = :$paramName")
               ->setParameter($paramName, $criteria['headerFormat']);
        }
        
        // Si on recherche un template avec média d'en-tête
        if (isset($criteria['hasHeaderMedia']) && $criteria['hasHeaderMedia'] === true) {
            $qb->andWhere("t.headerFormat IN ('IMAGE', 'VIDEO', 'DOCUMENT')");
        }
        
        // Recherche par nombre de variables
        if (isset($criteria['minVariables'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.bodyVariablesCount >= :$paramName")
               ->setParameter($paramName, $criteria['minVariables']);
        }
        
        if (isset($criteria['maxVariables'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.bodyVariablesCount <= :$paramName")
               ->setParameter($paramName, $criteria['maxVariables']);
        }
        
        // Recherche par présence de boutons
        if (isset($criteria['hasButtons']) && $criteria['hasButtons'] === true) {
            $qb->andWhere("t.buttonsCount > 0");
        }
        
        if (isset($criteria['buttonCount'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.buttonsCount = :$paramName")
               ->setParameter($paramName, $criteria['buttonCount']);
        }
        
        // Recherche textuelle dans le corps du message
        if (isset($criteria['bodyText'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.bodyText LIKE :$paramName")
               ->setParameter($paramName, '%' . $criteria['bodyText'] . '%');
        }
        
        // Recherche par utilisation
        if (isset($criteria['minUsageCount'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.usageCount >= :$paramName")
               ->setParameter($paramName, $criteria['minUsageCount']);
        }
        
        // Recherche par date de dernière utilisation
        if (isset($criteria['usedSince'])) {
            $paramName = 'param' . $paramIndex++;
            $qb->andWhere("t.lastUsedAt >= :$paramName")
               ->setParameter($paramName, $criteria['usedSince']);
        }
    }
}