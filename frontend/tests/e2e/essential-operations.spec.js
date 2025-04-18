// @ts-check
import { test, expect } from '@playwright/test';

// Données de test
const testPhoneNumbers = ['0777104936', '002250141399354', '+2250554272605'];
const users = [
  { username: 'Admin', password: 'oraclesms2025-0' },
  { username: 'AfricaQSHE', password: 'Qualitas@2024' }
];

for (const user of users) {
  test.describe(`Tests des opérations essentielles pour ${user.username}`, () => {
    test.beforeEach(async ({ page }) => {
      // Connexion
      await page.goto('http://localhost:5173/login');
      await page.fill('input[name="username"]', user.username);
      await page.fill('input[name="password"]', user.password);
      await page.click('button[type="submit"]');
      await page.waitForURL('http://localhost:5173/dashboard');
    });

    test('Vider l\'historique SMS', async ({ page }) => {
      // Naviguer vers la page d'historique SMS
      await page.goto('http://localhost:5173/sms-history');
      
      // Vérifier qu'il y a des entrées avant la suppression
      const entriesBeforeCount = await page.locator('.sms-history-item').count();
      console.log(`Nombre d'entrées avant suppression: ${entriesBeforeCount}`);
      
      // S'il n'y a pas d'entrées, créer un SMS de test
      if (entriesBeforeCount === 0) {
        console.log('Aucune entrée trouvée, création d\'un SMS de test...');
        await page.goto('http://localhost:5173/sms');
        await page.click('button.single-sms-btn');
        await page.fill('input[name="phoneNumber"]', testPhoneNumbers[0]);
        await page.fill('textarea[name="message"]', 'Message de test pour vider l\'historique');
        await page.click('button.send-sms-btn');
        await page.waitForSelector('.sms-success-message');
        await page.goto('http://localhost:5173/sms-history');
      }
      
      // Cliquer sur le bouton pour vider l'historique
      await page.click('button.clear-history-btn');
      
      // Confirmer dans la boîte de dialogue
      await page.click('.confirmation-dialog button.confirm-btn');
      
      // Vérifier que l'historique est vide
      await expect(page.locator('.no-history-message')).toBeVisible();
      // Ou vérifier qu'il n'y a plus d'entrées
      const entriesAfterCount = await page.locator('.sms-history-item').count();
      expect(entriesAfterCount).toBe(0);
    });

    test('Ajouter, modifier et supprimer un contact', async ({ page }) => {
      // Naviguer vers la page des contacts
      await page.goto('http://localhost:5173/contacts');
      
      // Cliquer sur le bouton pour ajouter un contact
      await page.click('button.add-contact-btn');
      
      // Remplir le formulaire
      await page.fill('input[name="name"]', 'Contact Test E2E');
      await page.fill('input[name="phoneNumber"]', testPhoneNumbers[0]);
      await page.fill('input[name="email"]', 'test.e2e@example.com');
      await page.fill('textarea[name="notes"]', 'Contact créé pour test E2E');
      
      // Soumettre le formulaire
      await page.click('.contact-form button[type="submit"]');
      
      // Vérifier que le contact a été ajouté
      await expect(page.locator('text=Contact Test E2E')).toBeVisible();
      
      // Modifier le contact
      await page.click('.contact-item:has-text("Contact Test E2E") button.edit-btn');
      await page.fill('input[name="name"]', 'Contact Test E2E Modifié');
      await page.click('.contact-form button[type="submit"]');
      
      // Vérifier que le contact a été modifié
      await expect(page.locator('text=Contact Test E2E Modifié')).toBeVisible();
      
      // Supprimer le contact
      await page.click('.contact-item:has-text("Contact Test E2E Modifié") button.delete-btn');
      await page.click('.confirmation-dialog button.confirm-btn');
      
      // Vérifier que le contact a été supprimé
      await expect(page.locator('text=Contact Test E2E Modifié')).not.toBeVisible();
    });

    test('Créer, modifier et supprimer un groupe de contacts', async ({ page }) => {
      // Naviguer vers la page des groupes
      await page.goto('http://localhost:5173/contact-groups');
      
      // Créer un groupe
      await page.click('button.add-group-btn');
      await page.fill('input[name="name"]', 'Groupe Test E2E');
      await page.fill('textarea[name="description"]', 'Groupe créé pour test E2E');
      await page.click('.group-form button[type="submit"]');
      
      // Vérifier que le groupe a été créé
      await expect(page.locator('text=Groupe Test E2E')).toBeVisible();
      
      // Modifier le groupe
      await page.click('.group-item:has-text("Groupe Test E2E") button.edit-btn');
      await page.fill('input[name="name"]', 'Groupe Test E2E Modifié');
      await page.click('.group-form button[type="submit"]');
      
      // Vérifier que le groupe a été modifié
      await expect(page.locator('text=Groupe Test E2E Modifié')).toBeVisible();
      
      // Supprimer le groupe
      await page.click('.group-item:has-text("Groupe Test E2E Modifié") button.delete-btn');
      await page.click('.confirmation-dialog button.confirm-btn');
      
      // Vérifier que le groupe a été supprimé
      await expect(page.locator('text=Groupe Test E2E Modifié')).not.toBeVisible();
    });

    test('Ajouter et supprimer un contact d\'un groupe', async ({ page }) => {
      // Créer un contact et un groupe pour le test
      await page.goto('http://localhost:5173/contacts');
      await page.click('button.add-contact-btn');
      await page.fill('input[name="name"]', 'Contact pour Groupe');
      await page.fill('input[name="phoneNumber"]', testPhoneNumbers[1]);
      await page.click('.contact-form button[type="submit"]');
      
      await page.goto('http://localhost:5173/contact-groups');
      await page.click('button.add-group-btn');
      await page.fill('input[name="name"]', 'Groupe pour Test');
      await page.click('.group-form button[type="submit"]');
      
      // Ajouter le contact au groupe
      await page.click('.group-item:has-text("Groupe pour Test")');
      await page.click('button.add-contacts-to-group-btn');
      await page.check('.contact-selection-list .contact-item:has-text("Contact pour Groupe") input[type="checkbox"]');
      await page.click('.add-contacts-dialog button.confirm-btn');
      
      // Vérifier que le contact a été ajouté au groupe
      await expect(page.locator('.group-contacts-list .contact-item:has-text("Contact pour Groupe")')).toBeVisible();
      
      // Supprimer le contact du groupe
      await page.click('.group-contacts-list .contact-item:has-text("Contact pour Groupe") button.remove-from-group-btn');
      await page.click('.confirmation-dialog button.confirm-btn');
      
      // Vérifier que le contact a été supprimé du groupe
      await expect(page.locator('.group-contacts-list .contact-item:has-text("Contact pour Groupe")')).not.toBeVisible();
      
      // Nettoyer - supprimer le contact et le groupe
      await page.goto('http://localhost:5173/contacts');
      await page.click('.contact-item:has-text("Contact pour Groupe") button.delete-btn');
      await page.click('.confirmation-dialog button.confirm-btn');
      
      await page.goto('http://localhost:5173/contact-groups');
      await page.click('.group-item:has-text("Groupe pour Test") button.delete-btn');
      await page.click('.confirmation-dialog button.confirm-btn');
    });
  });
}
