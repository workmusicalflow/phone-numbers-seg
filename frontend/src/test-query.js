/**
 * Test script for the GraphQL fetchApprovedWhatsAppTemplates query
 * 
 * This script helps diagnose issues with the GraphQL query by making a direct
 * API call to the GraphQL endpoint.
 */

// Helper function to log results with color
const log = {
  info: (msg) => console.log(`%c${msg}`, 'color: #4a9de7'),
  success: (msg) => console.log(`%c${msg}`, 'color: #4caf50'),
  error: (msg) => console.log(`%c${msg}`, 'color: #f44336'),
  warning: (msg) => console.log(`%c${msg}`, 'color: #ff9800'),
  divider: () => console.log('-'.repeat(80))
};

// API endpoint URL - adjust based on your environment
const API_URL = '/graphql';

// Function to run the test
async function testFetchTemplatesQuery() {
  log.info('Testing fetchApprovedWhatsAppTemplates GraphQL query');
  log.divider();
  
  // The GraphQL query with minimal fields
  const simpleQuery = `
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
  
  // The GraphQL query with all fields
  const fullQuery = `
    query {
      fetchApprovedWhatsAppTemplates {
        id
        name
        category
        language
        status
        componentsJson
        description
        hasMediaHeader
        headerType
        bodyVariablesCount
        hasButtons
        buttonsCount
        hasFooter
        qualityScore
        headerFormat
        fullBodyText
        footerText
        buttonsDetailsJson
        rejectionReason
        usageCount
        lastUsedAt
        isPopular
      }
    }
  `;
  
  // The query with filter
  const filteredQuery = `
    query {
      fetchApprovedWhatsAppTemplates(filter: { category: "MARKETING" }) {
        id
        name
        category
        language
        status
      }
    }
  `;
  
  // Try the simple query first
  try {
    log.info('Executing simple query...');
    
    const simpleResponse = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query: simpleQuery }),
      credentials: 'include' // Important for authentication
    });
    
    const simpleResult = await simpleResponse.json();
    
    if (simpleResult.errors) {
      log.error('Simple query failed with errors:');
      simpleResult.errors.forEach(err => log.error(`- ${err.message}`));
    } else {
      log.success('Simple query successful!');
      const templates = simpleResult.data?.fetchApprovedWhatsAppTemplates || [];
      log.info(`Retrieved ${templates.length} templates`);
      
      if (templates.length > 0) {
        log.info('First template:');
        console.log(templates[0]);
      } else {
        log.warning('No templates returned (empty array)');
      }
    }
  } catch (err) {
    log.error(`Error executing simple query: ${err.message}`);
  }
  
  log.divider();
  
  // Try the filtered query
  try {
    log.info('Executing filtered query...');
    
    const filteredResponse = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query: filteredQuery }),
      credentials: 'include'
    });
    
    const filteredResult = await filteredResponse.json();
    
    if (filteredResult.errors) {
      log.error('Filtered query failed with errors:');
      filteredResult.errors.forEach(err => log.error(`- ${err.message}`));
    } else {
      log.success('Filtered query successful!');
      const templates = filteredResult.data?.fetchApprovedWhatsAppTemplates || [];
      log.info(`Retrieved ${templates.length} templates with MARKETING category`);
      
      if (templates.length > 0) {
        log.info('First template:');
        console.log(templates[0]);
      } else {
        log.warning('No templates with MARKETING category returned (empty array)');
      }
    }
  } catch (err) {
    log.error(`Error executing filtered query: ${err.message}`);
  }
  
  log.divider();
  
  // Finally try the full query
  try {
    log.info('Executing full query...');
    
    const fullResponse = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ query: fullQuery }),
      credentials: 'include'
    });
    
    const fullResult = await fullResponse.json();
    
    if (fullResult.errors) {
      log.error('Full query failed with errors:');
      fullResult.errors.forEach(err => log.error(`- ${err.message}`));
    } else {
      log.success('Full query successful!');
      const templates = fullResult.data?.fetchApprovedWhatsAppTemplates || [];
      log.info(`Retrieved ${templates.length} templates with all fields`);
      
      if (templates.length > 0) {
        log.info('First template (summary):');
        const template = templates[0];
        console.log({
          id: template.id,
          name: template.name,
          category: template.category,
          language: template.language,
          status: template.status,
          hasMediaHeader: template.hasMediaHeader,
          bodyVariablesCount: template.bodyVariablesCount,
          hasButtons: template.hasButtons
        });
      } else {
        log.warning('No templates returned for full query (empty array)');
      }
    }
  } catch (err) {
    log.error(`Error executing full query: ${err.message}`);
  }
  
  log.divider();
  log.info('Test complete');
}

// Execute the test function
testFetchTemplatesQuery().catch(error => {
  log.error(`Unhandled error in test: ${error.message}`);
});

// Export the function for manual execution in browser console
window.testFetchTemplatesQuery = testFetchTemplatesQuery;