// Script pour tester l'API GraphQL fetchApprovedWhatsAppTemplates
const API_URL = 'http://localhost:8000/graphql.php';

// Requête GraphQL pour récupérer les templates WhatsApp approuvés
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

// Options pour la requête fetch
const options = {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  credentials: 'include', // Important pour transmettre les cookies de session
  body: JSON.stringify({
    query: query
  })
};

// Exécution de la requête
console.log('Envoi de la requête GraphQL...');
fetch(API_URL, options)
  .then(response => response.json())
  .then(result => {
    console.log('Réponse reçue:', JSON.stringify(result, null, 2));
    
    // Analyse des résultats
    if (result.errors) {
      console.error('Erreurs GraphQL:', result.errors);
    } else if (result.data && result.data.fetchApprovedWhatsAppTemplates) {
      const templates = result.data.fetchApprovedWhatsAppTemplates;
      console.log(`${templates.length} templates trouvés:`);
      templates.forEach(template => {
        console.log(`- ${template.name} (${template.language}, ${template.category})`);
      });
    } else {
      console.log('Aucun template ou format de réponse inattendu');
    }
  })
  .catch(error => {
    console.error('Erreur lors de la requête:', error);
  });