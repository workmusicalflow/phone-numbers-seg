# Test info

- Name: Tests des opérations essentielles pour Admin >> Créer, modifier et supprimer un groupe de contacts
- Location: /Users/ns2poportable/Desktop/phone-numbers-seg/frontend/tests/e2e/essential-operations.spec.js:90:5

# Error details

```
Error: page.fill: Test timeout of 30000ms exceeded.
Call log:
  - waiting for locator('input[name="username"]')

    at /Users/ns2poportable/Desktop/phone-numbers-seg/frontend/tests/e2e/essential-operations.spec.js:16:18
```

# Page snapshot

```yaml
- banner:
  - toolbar:
    - text: Oracle Gestionnaire de Contacts by Thalamus
    - link "Connexion":
      - /url: /login
- heading "Connexion" [level=1]
- textbox "Nom d'utilisateur"
- text: Nom d'utilisateur
- textbox "Mot de passe"
- text: Mot de passe
- checkbox "Se souvenir de moi"
- button "Mot de passe oublié ?"
- button "Se connecter"
```

# Test source

```ts
   1 | // @ts-check
   2 | import { test, expect } from '@playwright/test';
   3 |
   4 | // Données de test
   5 | const testPhoneNumbers = ['0777104936', '002250141399354', '+2250554272605'];
   6 | const users = [
   7 |   { username: 'Admin', password: 'oraclesms2025-0' },
   8 |   { username: 'AfricaQSHE', password: 'Qualitas@2024' }
   9 | ];
   10 |
   11 | for (const user of users) {
   12 |   test.describe(`Tests des opérations essentielles pour ${user.username}`, () => {
   13 |     test.beforeEach(async ({ page }) => {
   14 |       // Connexion
   15 |       await page.goto('http://localhost:5173/login');
>  16 |       await page.fill('input[name="username"]', user.username);
      |                  ^ Error: page.fill: Test timeout of 30000ms exceeded.
   17 |       await page.fill('input[name="password"]', user.password);
   18 |       await page.click('button[type="submit"]');
   19 |       await page.waitForURL('http://localhost:5173/dashboard');
   20 |     });
   21 |
   22 |     test('Vider l\'historique SMS', async ({ page }) => {
   23 |       // Naviguer vers la page d'historique SMS
   24 |       await page.goto('http://localhost:5173/sms-history');
   25 |       
   26 |       // Vérifier qu'il y a des entrées avant la suppression
   27 |       const entriesBeforeCount = await page.locator('.sms-history-item').count();
   28 |       console.log(`Nombre d'entrées avant suppression: ${entriesBeforeCount}`);
   29 |       
   30 |       // S'il n'y a pas d'entrées, créer un SMS de test
   31 |       if (entriesBeforeCount === 0) {
   32 |         console.log('Aucune entrée trouvée, création d\'un SMS de test...');
   33 |         await page.goto('http://localhost:5173/sms');
   34 |         await page.click('button.single-sms-btn');
   35 |         await page.fill('input[name="phoneNumber"]', testPhoneNumbers[0]);
   36 |         await page.fill('textarea[name="message"]', 'Message de test pour vider l\'historique');
   37 |         await page.click('button.send-sms-btn');
   38 |         await page.waitForSelector('.sms-success-message');
   39 |         await page.goto('http://localhost:5173/sms-history');
   40 |       }
   41 |       
   42 |       // Cliquer sur le bouton pour vider l'historique
   43 |       await page.click('button.clear-history-btn');
   44 |       
   45 |       // Confirmer dans la boîte de dialogue
   46 |       await page.click('.confirmation-dialog button.confirm-btn');
   47 |       
   48 |       // Vérifier que l'historique est vide
   49 |       await expect(page.locator('.no-history-message')).toBeVisible();
   50 |       // Ou vérifier qu'il n'y a plus d'entrées
   51 |       const entriesAfterCount = await page.locator('.sms-history-item').count();
   52 |       expect(entriesAfterCount).toBe(0);
   53 |     });
   54 |
   55 |     test('Ajouter, modifier et supprimer un contact', async ({ page }) => {
   56 |       // Naviguer vers la page des contacts
   57 |       await page.goto('http://localhost:5173/contacts');
   58 |       
   59 |       // Cliquer sur le bouton pour ajouter un contact
   60 |       await page.click('button.add-contact-btn');
   61 |       
   62 |       // Remplir le formulaire
   63 |       await page.fill('input[name="name"]', 'Contact Test E2E');
   64 |       await page.fill('input[name="phoneNumber"]', testPhoneNumbers[0]);
   65 |       await page.fill('input[name="email"]', 'test.e2e@example.com');
   66 |       await page.fill('textarea[name="notes"]', 'Contact créé pour test E2E');
   67 |       
   68 |       // Soumettre le formulaire
   69 |       await page.click('.contact-form button[type="submit"]');
   70 |       
   71 |       // Vérifier que le contact a été ajouté
   72 |       await expect(page.locator('text=Contact Test E2E')).toBeVisible();
   73 |       
   74 |       // Modifier le contact
   75 |       await page.click('.contact-item:has-text("Contact Test E2E") button.edit-btn');
   76 |       await page.fill('input[name="name"]', 'Contact Test E2E Modifié');
   77 |       await page.click('.contact-form button[type="submit"]');
   78 |       
   79 |       // Vérifier que le contact a été modifié
   80 |       await expect(page.locator('text=Contact Test E2E Modifié')).toBeVisible();
   81 |       
   82 |       // Supprimer le contact
   83 |       await page.click('.contact-item:has-text("Contact Test E2E Modifié") button.delete-btn');
   84 |       await page.click('.confirmation-dialog button.confirm-btn');
   85 |       
   86 |       // Vérifier que le contact a été supprimé
   87 |       await expect(page.locator('text=Contact Test E2E Modifié')).not.toBeVisible();
   88 |     });
   89 |
   90 |     test('Créer, modifier et supprimer un groupe de contacts', async ({ page }) => {
   91 |       // Naviguer vers la page des groupes
   92 |       await page.goto('http://localhost:5173/contact-groups');
   93 |       
   94 |       // Créer un groupe
   95 |       await page.click('button.add-group-btn');
   96 |       await page.fill('input[name="name"]', 'Groupe Test E2E');
   97 |       await page.fill('textarea[name="description"]', 'Groupe créé pour test E2E');
   98 |       await page.click('.group-form button[type="submit"]');
   99 |       
  100 |       // Vérifier que le groupe a été créé
  101 |       await expect(page.locator('text=Groupe Test E2E')).toBeVisible();
  102 |       
  103 |       // Modifier le groupe
  104 |       await page.click('.group-item:has-text("Groupe Test E2E") button.edit-btn');
  105 |       await page.fill('input[name="name"]', 'Groupe Test E2E Modifié');
  106 |       await page.click('.group-form button[type="submit"]');
  107 |       
  108 |       // Vérifier que le groupe a été modifié
  109 |       await expect(page.locator('text=Groupe Test E2E Modifié')).toBeVisible();
  110 |       
  111 |       // Supprimer le groupe
  112 |       await page.click('.group-item:has-text("Groupe Test E2E Modifié") button.delete-btn');
  113 |       await page.click('.confirmation-dialog button.confirm-btn');
  114 |       
  115 |       // Vérifier que le groupe a été supprimé
  116 |       await expect(page.locator('text=Groupe Test E2E Modifié')).not.toBeVisible();
```