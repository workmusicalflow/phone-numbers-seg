/**
 * Test de performance pour la galerie de médias
 * 
 * Ce script permet de mesurer les performances de chargement et d'interaction
 * avec la galerie de médias récemment utilisés.
 */

// Configuration
const NUM_MEDIA_ITEMS = 100; // Nombre d'éléments de médias à générer
const ITERATIONS = 5; // Nombre d'itérations pour les tests

// Fonction pour générer des données de test
function generateMediaItems(count) {
  const items = [];
  const types = ['image', 'document', 'video', 'audio'];
  const mimeTypes = {
    'image': ['image/jpeg', 'image/png', 'image/webp'],
    'document': ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'video': ['video/mp4', 'video/webm'],
    'audio': ['audio/mpeg', 'audio/wav']
  };

  for (let i = 0; i < count; i++) {
    const type = types[Math.floor(Math.random() * types.length)];
    const mimeType = mimeTypes[type][Math.floor(Math.random() * mimeTypes[type].length)];
    const size = Math.floor(Math.random() * 10000000); // Taille entre 0 et 10MB
    
    items.push({
      id: `id-${i}`,
      mediaId: `media-${i}`,
      type,
      mimeType,
      filename: `file-${i}.${mimeType.split('/')[1]}`,
      size,
      timestamp: new Date(Date.now() - Math.floor(Math.random() * 30 * 24 * 60 * 60 * 1000)).toISOString(), // Jusqu'à 30 jours dans le passé
      favorite: Math.random() > 0.8, // 20% des éléments sont favoris
      url: `blob:test-url-${i}`,
      thumbnailUrl: type === 'image' ? `blob:test-thumbnail-${i}` : undefined
    });
  }

  return items;
}

// Fonction pour mesurer les performances de filtrage
async function measureFilteringPerformance(mediaItems) {
  console.log('==== Test de performance du filtrage des médias ====');
  
  // Test de filtrage par type
  console.log('\n- Filtrage par type:');
  for (const type of ['image', 'document', 'video', 'audio']) {
    const start = performance.now();
    
    // Filtrer les éléments par type
    const filtered = mediaItems.filter(item => item.type === type);
    
    const end = performance.now();
    console.log(`  Type "${type}": ${filtered.length} éléments filtrés en ${(end - start).toFixed(2)}ms`);
  }
  
  // Test de filtrage par recherche
  console.log('\n- Filtrage par recherche:');
  const searchTerms = ['file', 'image', 'doc', '0', '5'];
  
  for (const term of searchTerms) {
    const start = performance.now();
    
    // Filtrer les éléments par terme de recherche
    const filtered = mediaItems.filter(media =>
      (media.filename && media.filename.toLowerCase().includes(term.toLowerCase())) ||
      (media.mimeType && media.mimeType.toLowerCase().includes(term.toLowerCase()))
    );
    
    const end = performance.now();
    console.log(`  Terme "${term}": ${filtered.length} éléments filtrés en ${(end - start).toFixed(2)}ms`);
  }
  
  // Test de filtrage combiné
  console.log('\n- Filtrage combiné (type + recherche):');
  const combinedTests = [
    { type: 'image', term: 'jpeg' },
    { type: 'document', term: 'pdf' },
    { type: 'video', term: 'mp4' }
  ];
  
  for (const test of combinedTests) {
    const start = performance.now();
    
    // Filtrer les éléments par type et terme de recherche
    const filtered = mediaItems.filter(media =>
      media.type === test.type &&
      ((media.filename && media.filename.toLowerCase().includes(test.term.toLowerCase())) ||
       (media.mimeType && media.mimeType.toLowerCase().includes(test.term.toLowerCase())))
    );
    
    const end = performance.now();
    console.log(`  Type "${test.type}" + Terme "${test.term}": ${filtered.length} éléments filtrés en ${(end - start).toFixed(2)}ms`);
  }
}

// Fonction pour mesurer les performances du tri
async function measureSortingPerformance(mediaItems) {
  console.log('\n==== Test de performance du tri des médias ====');
  
  // Tri par date (plus récent en premier)
  console.log('\n- Tri par date (plus récent en premier):');
  const startDateDesc = performance.now();
  const sortedByDateDesc = [...mediaItems].sort((a, b) => 
    new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  );
  const endDateDesc = performance.now();
  console.log(`  Tri de ${mediaItems.length} éléments en ${(endDateDesc - startDateDesc).toFixed(2)}ms`);
  
  // Tri par date (plus ancien en premier)
  console.log('\n- Tri par date (plus ancien en premier):');
  const startDateAsc = performance.now();
  const sortedByDateAsc = [...mediaItems].sort((a, b) => 
    new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime()
  );
  const endDateAsc = performance.now();
  console.log(`  Tri de ${mediaItems.length} éléments en ${(endDateAsc - startDateAsc).toFixed(2)}ms`);
  
  // Tri par taille (plus grand en premier)
  console.log('\n- Tri par taille (plus grand en premier):');
  const startSizeDesc = performance.now();
  const sortedBySizeDesc = [...mediaItems].sort((a, b) => b.size - a.size);
  const endSizeDesc = performance.now();
  console.log(`  Tri de ${mediaItems.length} éléments en ${(endSizeDesc - startSizeDesc).toFixed(2)}ms`);
  
  // Tri par nom de fichier
  console.log('\n- Tri par nom de fichier:');
  const startName = performance.now();
  const sortedByName = [...mediaItems].sort((a, b) => 
    a.filename.localeCompare(b.filename)
  );
  const endName = performance.now();
  console.log(`  Tri de ${mediaItems.length} éléments en ${(endName - startName).toFixed(2)}ms`);
}

// Fonction pour mesurer les performances de la gestion de la mémoire
async function measureMemoryUsage(mediaItems) {
  console.log('\n==== Test de performance de la gestion de la mémoire ====');
  
  // Mesurer la taille en mémoire des données (estimation)
  const jsonSize = JSON.stringify(mediaItems).length;
  console.log(`\n- Taille approximative des données: ${(jsonSize / 1024).toFixed(2)} KB`);
  
  // Mesurer l'impact sur localStorage
  console.log('\n- Test d\'écriture/lecture localStorage:');
  
  // Écriture
  const startWrite = performance.now();
  localStorage.setItem('performance-test-media', JSON.stringify(mediaItems));
  const endWrite = performance.now();
  console.log(`  Écriture de ${mediaItems.length} éléments dans localStorage: ${(endWrite - startWrite).toFixed(2)}ms`);
  
  // Lecture
  const startRead = performance.now();
  const readItems = JSON.parse(localStorage.getItem('performance-test-media'));
  const endRead = performance.now();
  console.log(`  Lecture de ${readItems.length} éléments depuis localStorage: ${(endRead - startRead).toFixed(2)}ms`);
  
  // Nettoyage
  localStorage.removeItem('performance-test-media');
}

// Fonction principale
async function runPerformanceTests() {
  console.log('Démarrage des tests de performance pour la galerie de médias');
  console.log(`Génération de ${NUM_MEDIA_ITEMS} éléments de médias pour les tests...`);
  
  // Générer des données de test
  const mediaItems = generateMediaItems(NUM_MEDIA_ITEMS);
  
  console.log(`Tests avec ${mediaItems.length} éléments de médias, ${ITERATIONS} itérations par test`);
  
  // Exécuter les tests de performance pour le filtrage
  for (let i = 0; i < ITERATIONS; i++) {
    console.log(`\n=== Itération ${i + 1}/${ITERATIONS} ===`);
    await measureFilteringPerformance(mediaItems);
    await measureSortingPerformance(mediaItems);
    await measureMemoryUsage(mediaItems);
  }
  
  console.log('\nTests de performance terminés');
}

// Exécuter les tests
runPerformanceTests().catch(console.error);