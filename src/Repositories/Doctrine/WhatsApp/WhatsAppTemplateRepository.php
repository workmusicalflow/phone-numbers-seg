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
                'metaTemplateName' => $metaTemplateName,
                'languageCode' => $languageCode
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
            $qb->andWhere('t.languageCode = :language')
               ->setParameter('language', $languageCode);
        }
        
        return $qb->orderBy('t.metaTemplateName', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByCategory(string $category): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->findBy(['category' => $category], ['metaTemplateName' => 'ASC']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByLanguage(string $languageCode): array
    {
        return $this->getEntityManager()->getRepository(WhatsAppTemplate::class)
            ->findBy(['languageCode' => $languageCode], ['metaTemplateName' => 'ASC']);
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
}