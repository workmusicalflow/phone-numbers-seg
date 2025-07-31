// Script pour tester le routage WhatsApp

console.log('Test de routage WhatsApp');

// Test de l'import du composant
import('../src/views/WhatsApp.vue')
  .then(() => {
    console.log('✓ Import du composant WhatsApp réussi');
  })
  .catch((err) => {
    console.error('✗ Erreur lors de l\'import du composant WhatsApp:', err);
  });

// Test des imports des sous-composants
import('../src/components/whatsapp/WhatsAppSendMessage.vue')
  .then(() => {
    console.log('✓ Import de WhatsAppSendMessage réussi');
  })
  .catch((err) => {
    console.error('✗ Erreur lors de l\'import de WhatsAppSendMessage:', err);
  });

import('../src/components/whatsapp/WhatsAppMessageList.vue')
  .then(() => {
    console.log('✓ Import de WhatsAppMessageList réussi');
  })
  .catch((err) => {
    console.error('✗ Erreur lors de l\'import de WhatsAppMessageList:', err);
  });

// Test du store
import('../src/stores/whatsappStore.ts')
  .then(() => {
    console.log('✓ Import du whatsappStore réussi');
  })
  .catch((err) => {
    console.error('✗ Erreur lors de l\'import du whatsappStore:', err);
  });