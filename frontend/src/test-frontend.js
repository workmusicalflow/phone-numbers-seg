/**
 * Tests de protection contre les nulls pour les requêtes GraphQL
 * 
 * Ce script teste les protections contre les nulls implementées dans le frontend
 * pour la requête GraphQL fetchApprovedWhatsAppTemplates.
 */

// Log amélioré pour le débogage
const log = (message, type = 'info') => {
  const styles = {
    info: 'color: #4a9de7',
    success: 'color: #4caf50',
    error: 'color: #f44336',
    warning: 'color: #ff9800'
  };
  
  console.log(`%c${message}`, styles[type]);
};

// Fonction de test pour la requête fetchApprovedWhatsAppTemplates
async function testFetchApprovedTemplates() {
  log('Test de la requête fetchApprovedWhatsAppTemplates', 'info');
  
  try {
    const query = `
      query {
        fetchApprovedWhatsAppTemplates {
          id
          name
          category
          language
          status
        }
      }
    `;
    
    log(`Exécution de la requête: ${query}`, 'info');
    
    const response = await fetch('/graphql.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query }),
      credentials: 'include'
    });
    
    if (!response.ok) {
      log(`Erreur HTTP: ${response.status} ${response.statusText}`, 'error');
      return;
    }
    
    const result = await response.json();
    
    // Vérifier les erreurs
    if (result.errors) {
      log('Erreurs GraphQL détectées:', 'error');
      result.errors.forEach(error => {
        log(`- ${error.message}`, 'error');
        
        // Vérifier si c'est une erreur de nullabilité
        if (error.message.includes('Cannot return null for non-nullable field')) {
          log('ERREUR DE NULLABILITÉ DÉTECTÉE - Les correctifs n\'ont pas fonctionné!', 'error');
        }
      });
      return;
    }
    
    // Vérifier les données
    const templates = result.data?.fetchApprovedWhatsAppTemplates;
    
    // Test 1: Vérifier que les données existent
    if (templates === undefined) {
      log('La clé fetchApprovedWhatsAppTemplates est absente de la réponse', 'error');
      return;
    }
    
    // Test 2: Vérifier que templates n'est pas null
    if (templates === null) {
      log('La valeur de fetchApprovedWhatsAppTemplates est null', 'error');
      return;
    }
    
    // Test 3: Vérifier que templates est un tableau
    if (!Array.isArray(templates)) {
      log(`fetchApprovedWhatsAppTemplates n'est pas un tableau (type: ${typeof templates})`, 'error');
      return;
    }
    
    log(`Requête réussie: ${templates.length} templates récupérés`, 'success');
    
    // Afficher les templates
    if (templates.length > 0) {
      log('Premier template:', 'info');
      console.table(templates[0]);
    } else {
      log('Aucun template retourné (tableau vide)', 'warning');
    }
    
    log('Tous les tests passés avec succès!', 'success');
  } catch (error) {
    log(`Erreur lors de l'exécution du test: ${error.message}`, 'error');
    console.error(error);
  }
}

// Fonction de test pour getWhatsAppUserTemplates (utilisée par le sélecteur)
async function testGetWhatsAppUserTemplates() {
  log('Test de la requête getWhatsAppUserTemplates', 'info');
  
  try {
    const query = `
      query {
        getWhatsAppUserTemplates {
          id
          template_id
          name
          language
          status
        }
      }
    `;
    
    log(`Exécution de la requête: ${query}`, 'info');
    
    const response = await fetch('/graphql.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query }),
      credentials: 'include'
    });
    
    if (!response.ok) {
      log(`Erreur HTTP: ${response.status} ${response.statusText}`, 'error');
      return;
    }
    
    const result = await response.json();
    
    // Vérifier les erreurs
    if (result.errors) {
      log('Erreurs GraphQL détectées:', 'error');
      result.errors.forEach(error => {
        log(`- ${error.message}`, 'error');
      });
      return;
    }
    
    // Vérifier les données
    const templates = result.data?.getWhatsAppUserTemplates;
    
    if (templates === undefined) {
      log('La clé getWhatsAppUserTemplates est absente de la réponse', 'error');
      return;
    }
    
    if (templates === null) {
      log('La valeur de getWhatsAppUserTemplates est null', 'error');
      return;
    }
    
    if (!Array.isArray(templates)) {
      log(`getWhatsAppUserTemplates n'est pas un tableau (type: ${typeof templates})`, 'error');
      return;
    }
    
    log(`Requête réussie: ${templates.length} templates récupérés`, 'success');
    
    if (templates.length > 0) {
      log('Premier template:', 'info');
      console.table(templates[0]);
    } else {
      log('Aucun template retourné (tableau vide)', 'warning');
    }
    
    log('Tous les tests passés avec succès!', 'success');
  } catch (error) {
    log(`Erreur lors de l'exécution du test: ${error.message}`, 'error');
    console.error(error);
  }
}

// Exécuter les deux tests
async function runAllTests() {
  log('=== DÉBUT DES TESTS ===', 'info');
  
  // Test fetchApprovedWhatsAppTemplates
  await testFetchApprovedTemplates();
  
  log('--------------------------', 'info');
  
  // Test getWhatsAppUserTemplates
  await testGetWhatsAppUserTemplates();
  
  log('=== FIN DES TESTS ===', 'info');
}

// Exposer les fonctions pour utilisation depuis la console du navigateur
window.testFetchApprovedTemplates = testFetchApprovedTemplates;
window.testGetWhatsAppUserTemplates = testGetWhatsAppUserTemplates;
window.runAllTests = runAllTests;

// Exécuter automatiquement les tests après 2 secondes
setTimeout(() => {
  runAllTests();
}, 2000);

// Instructions pour l'utilisateur
log('Tests de protection contre les nulls chargés', 'info');
log('Pour exécuter les tests manuellement:', 'info');
log('- window.testFetchApprovedTemplates()', 'info');
log('- window.testGetWhatsAppUserTemplates()', 'info'); 
log('- window.runAllTests()', 'info');