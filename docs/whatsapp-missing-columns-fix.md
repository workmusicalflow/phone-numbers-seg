# Correctif pour l'erreur "no such column: t0.quality_score"

## Problème

Le système rencontrait des erreurs SQL lors de l'envoi de messages WhatsApp, malgré que les messages étaient correctement délivrés aux destinataires :

```
"Erreur lors de l'envoi du message: An exception occurred while executing a query: SQLSTATE[HY000]: General error: 1 no such column: t0.quality_score"
```

Cette erreur se produisait parce que le code de l'entité `WhatsAppTemplate` faisait référence à des colonnes qui n'existaient pas dans la table de base de données `whatsapp_templates`.

## Analyse

L'entité `WhatsAppTemplate` contenait plusieurs annotations Doctrine ORM faisant référence à des colonnes qui n'existaient pas dans la base de données, notamment :

- `quality_score`
- Potentiellement d'autres colonnes comme des drapeaux booléens pour `hasMediaHeader`, `hasButtons`, etc.

Bien que ces propriétés soient utilisées dans le code, elles n'avaient pas de correspondance dans le schéma de la base de données, ce qui provoquait des erreurs SQL lors des requêtes.

## Solution

Nous avons adopté une approche de "virtualisation" des propriétés problématiques :

1. **Suppression des annotations ORM** : Nous avons retiré les annotations `#[ORM\Column]` des propriétés qui n'existent pas dans la base de données, les transformant ainsi en propriétés PHP normales.

2. **Propriétés virtuelles** : Nous avons transformé ces propriétés en propriétés "virtuelles" qui sont gérées uniquement en mémoire pendant l'exécution du code, sans être persistées dans la base de données.

3. **Documentation claire** : Nous avons ajouté des commentaires pour expliquer que ces propriétés sont virtuelles et ne sont pas stockées en base de données.

Exemple de modification pour la propriété `quality_score` :
```php
// Avant :
#[ORM\Column(name: "quality_score", type: "float", nullable: true)]
private ?float $qualityScore = null;

// Après :
/**
 * Score de qualité du template - non stocké en base de données
 */
private ?float $qualityScore = null;
```

Pour les getters et setters associés :
```php
/**
 * Obtenir le score de qualité du template
 * Propriété virtuelle, non persistée en base de données
 */
public function getQualityScore(): ?float
{
    return $this->qualityScore;
}

/**
 * Définir le score de qualité du template
 * Propriété virtuelle, non persistée en base de données
 */
public function setQualityScore(?float $qualityScore): self
{
    $this->qualityScore = $qualityScore;
    return $this;
}
```

## Avantages de cette approche

1. **Non-invasif** : Cette solution ne nécessite pas de modification du schéma de la base de données, ce qui est généralement plus risqué.

2. **Compatibilité ascendante** : Le code existant qui utilise ces propriétés continue de fonctionner sans modification.

3. **Clarté** : Les commentaires indiquent clairement quelles propriétés sont virtuelles et ne sont pas persistées en base de données.

## Conclusion

Cette correction permet au système d'envoyer des messages template WhatsApp sans erreurs SQL, tout en maintenant la compatibilité avec le code existant. Cette approche est appropriée pour une correction rapide et à faible risque. À long terme, il pourrait être judicieux d'harmoniser le schéma de la base de données avec l'entité, soit en ajoutant les colonnes manquantes, soit en restructurant l'entité.

## Test et validation

Un script de test a été créé pour valider que l'envoi de messages WhatsApp fonctionne correctement :
`/scripts/whatsapp-test-send.php`

Ce script permet d'envoyer un message template WhatsApp directement, sans passer par l'API REST, ce qui facilite le débogage.