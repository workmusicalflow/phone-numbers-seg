/**
 * Script de test pour vérifier que le store WhatsApp fonctionne correctement
 * avec l'API GraphQL
 */

// Simuler l'environnement de test
const { graphql } = require('../src/services/graphql');

// Test 1: Récupération de l'historique des messages
async function testFetchHistory() {
  console.log('\n=== Test 1: Récupération de l\'historique ===');
  
  const query = `
    query WhatsAppHistory($limit: Int!, $offset: Int!) {
      whatsAppHistory(limit: $limit, offset: $offset) {
        id
        wabaMessageId
        phoneNumber
        direction
        type
        content
        status
        timestamp
        createdAt
      }
    }
  `;
  
  try {
    const response = await graphql(query, { limit: 10, offset: 0 });
    console.log('✓ Historique récupéré:', response);
  } catch (error) {
    console.error('✗ Erreur:', error.message);
  }
}

// Test 2: Envoi d'un message texte
async function testSendTextMessage() {
  console.log('\n=== Test 2: Envoi d\'un message texte ===');
  
  const query = `
    mutation SendWhatsAppMessage($message: WhatsAppMessageInput!) {
      sendWhatsAppMessage(message: $message) {
        id
        wabaMessageId
        phoneNumber
        direction
        type
        content
        status
      }
    }
  `;
  
  const variables = {
    message: {
      recipient: '+2250101010101',
      type: 'text',
      content: 'Test message depuis le store frontend'
    }
  };
  
  try {
    const response = await graphql(query, variables);
    console.log('✓ Message envoyé:', response);
  } catch (error) {
    console.error('✗ Erreur:', error.message);
  }
}

// Test 3: Envoi d'un message template
async function testSendTemplate() {
  console.log('\n=== Test 3: Envoi d\'un template ===');
  
  const query = `
    mutation SendWhatsAppTemplate($template: WhatsAppTemplateSendInput!) {
      sendWhatsAppTemplate(template: $template) {
        id
        wabaMessageId
        phoneNumber
        templateName
        templateLanguage
        status
      }
    }
  `;
  
  const variables = {
    template: {
      recipient: '+2250101010101',
      templateName: 'hello_world',
      languageCode: 'fr',
      body1Param: 'Store WhatsApp'
    }
  };
  
  try {
    const response = await graphql(query, variables);
    console.log('✓ Template envoyé:', response);
  } catch (error) {
    console.error('✗ Erreur:', error.message);
  }
}

// Test 4: Comptage des messages
async function testMessageCount() {
  console.log('\n=== Test 4: Comptage des messages ===');
  
  const query = `
    query WhatsAppMessageCount($status: String, $direction: String) {
      whatsAppMessageCount(status: $status, direction: $direction)
    }
  `;
  
  try {
    const response = await graphql(query, { 
      status: 'sent', 
      direction: 'OUTGOING' 
    });
    console.log('✓ Nombre de messages:', response);
  } catch (error) {
    console.error('✗ Erreur:', error.message);
  }
}

// Exécuter tous les tests
async function runAllTests() {
  console.log('=== Début des tests du store WhatsApp ===');
  
  await testFetchHistory();
  await testSendTextMessage();
  await testSendTemplate();
  await testMessageCount();
  
  console.log('\n=== Fin des tests ===');
}

// Lancer les tests si le script est exécuté directement
if (require.main === module) {
  runAllTests().catch(console.error);
}

module.exports = {
  testFetchHistory,
  testSendTextMessage,
  testSendTemplate,
  testMessageCount
};