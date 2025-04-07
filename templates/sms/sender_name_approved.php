<?php

/**
 * Template SMS pour notification d'approbation de nom d'expéditeur
 * 
 * Variables disponibles:
 * - $username: Nom d'utilisateur
 * - $senderName: Nom d'expéditeur approuvé
 * - $approvalDate: Date d'approbation
 */

return "ORACLE: Bonjour {$username}, votre demande de nom d'expéditeur '{$senderName}' a été approuvée le {$approvalDate}. Vous pouvez maintenant l'utiliser pour vos envois de SMS. Merci de votre confiance!";
