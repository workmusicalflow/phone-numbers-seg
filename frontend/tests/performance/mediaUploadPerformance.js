/**
 * Test de performance pour l'upload et la gestion des médias
 * 
 * Ce script permet de mesurer les performances de l'upload de fichiers,
 * de la gestion du cache et de la reprise après erreur.
 */

// Configuration
const ITERATIONS = 3; // Nombre d'itérations pour les tests
const FILE_SIZES = [100 * 1024, 500 * 1024, 1024 * 1024, 5 * 1024 * 1024]; // Tailles de fichiers à tester (100KB, 500KB, 1MB, 5MB)

// Classe simulant le service mediaCache
class MockMediaCache {
  constructor() {
    this.cache = new Map();
  }

  async findInCache(fileHash) {
    const start = performance.now();
    const result = this.cache.get(fileHash);
    const end = performance.now();
    
    console.log(`  Recherche dans le cache: ${(end - start).toFixed(2)}ms, Résultat: ${result ? 'HIT' : 'MISS'}`);
    
    return result;
  }

  async addToCache(fileHash, mediaId, metadata) {
    const start = performance.now();
    this.cache.set(fileHash, { mediaId, ...metadata });
    const end = performance.now();
    
    console.log(`  Ajout au cache: ${(end - start).toFixed(2)}ms`);
  }
}

// Fonction pour générer un fichier binaire de test
function generateTestFile(size, type = 'image/jpeg', name = 'test-file.jpg') {
  const buffer = new ArrayBuffer(size);
  const view = new Uint8Array(buffer);
  
  // Remplir le buffer avec des données pseudo-aléatoires
  for (let i = 0; i < size; i++) {
    view[i] = Math.floor(Math.random() * 256);
  }
  
  return new File([buffer], name, { type });
}

// Fonction pour simuler un upload
async function simulateUpload(file, options = {}) {
  console.log(`\n- Upload de fichier: ${file.name}, Taille: ${(file.size / 1024).toFixed(2)} KB, Type: ${file.type}`);
  
  const { chunkSize = 1024 * 64, delay = 10, errorProbability = 0, resumable = true } = options;
  
  // Simuler le calcul du hash pour le cache
  const start = performance.now();
  await new Promise(resolve => setTimeout(resolve, file.size / 100000)); // Simulation du temps de calcul du hash
  const hashCalcTime = performance.now() - start;
  console.log(`  Calcul du hash: ${hashCalcTime.toFixed(2)}ms`);
  
  // Simuler l'upload
  let uploaded = 0;
  const totalChunks = Math.ceil(file.size / chunkSize);
  let currentChunk = 0;
  let errors = 0;
  let resumeCount = 0;
  
  const uploadStart = performance.now();
  
  while (uploaded < file.size) {
    // Simuler l'upload d'un chunk
    const chunkStart = performance.now();
    
    // Calculer la taille du chunk
    const remainingBytes = file.size - uploaded;
    const currentChunkSize = Math.min(chunkSize, remainingBytes);
    
    // Simuler le délai d'upload proportionnel à la taille du chunk
    await new Promise(resolve => setTimeout(resolve, (currentChunkSize / chunkSize) * delay));
    
    // Simuler une erreur aléatoire
    if (Math.random() < errorProbability) {
      errors++;
      console.log(`  ERROR: Erreur d'upload au chunk ${currentChunk + 1}/${totalChunks} (${Math.round(uploaded / file.size * 100)}% terminé)`);
      
      if (resumable) {
        // Simuler une reprise d'upload
        resumeCount++;
        console.log(`  RESUME: Reprise de l'upload à partir de ${(uploaded / 1024).toFixed(2)} KB`);
        await new Promise(resolve => setTimeout(resolve, 100)); // Délai pour la reprise
        continue;
      } else {
        console.log(`  FAIL: Upload échoué après ${errors} erreurs`);
        break;
      }
    }
    
    // Incrémenter les compteurs
    uploaded += currentChunkSize;
    currentChunk++;
    
    // Calculer la progression
    const progress = Math.round(uploaded / file.size * 100);
    const chunkEnd = performance.now();
    const chunkTime = chunkEnd - chunkStart;
    
    if (currentChunk % 10 === 0 || currentChunk === totalChunks) {
      console.log(`  Chunk ${currentChunk}/${totalChunks}: ${progress}% terminé, ${chunkTime.toFixed(2)}ms`);
    }
  }
  
  const uploadEnd = performance.now();
  const uploadTime = uploadEnd - uploadStart;
  
  // Résultats
  console.log(`  Résultat de l'upload:`);
  console.log(`    - Durée totale: ${uploadTime.toFixed(2)}ms`);
  console.log(`    - Vitesse moyenne: ${((file.size / 1024) / (uploadTime / 1000)).toFixed(2)} KB/s`);
  console.log(`    - Nombre d'erreurs: ${errors}`);
  console.log(`    - Nombre de reprises: ${resumeCount}`);
  console.log(`    - Statut: ${uploaded >= file.size ? 'SUCCÈS' : 'ÉCHEC'}`);
  
  return {
    success: uploaded >= file.size,
    uploadTime,
    errors,
    resumeCount,
    speed: (file.size / 1024) / (uploadTime / 1000)
  };
}

// Fonction pour tester le cache
async function testCachePerformance(mockCache) {
  console.log('\n==== Test de performance du cache de médias ====');
  
  // Générer différents fichiers pour les tests
  const files = [];
  for (let i = 0; i < 5; i++) {
    files.push(generateTestFile(1024 * 1024, 'image/jpeg', `test-image-${i}.jpg`));
  }
  
  // Premier test: Ajout au cache
  console.log('\n- Test d\'ajout au cache:');
  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    const fileHash = `hash-${i}`; // Simuler un hash unique
    
    await mockCache.addToCache(fileHash, `media-id-${i}`, {
      mimeType: file.type,
      filename: file.name,
      size: file.size,
      timestamp: new Date().toISOString()
    });
  }
  
  // Deuxième test: Recherche dans le cache (hit)
  console.log('\n- Test de recherche dans le cache (hit):');
  for (let i = 0; i < files.length; i++) {
    const fileHash = `hash-${i}`;
    await mockCache.findInCache(fileHash);
  }
  
  // Troisième test: Recherche dans le cache (miss)
  console.log('\n- Test de recherche dans le cache (miss):');
  for (let i = 0; i < 5; i++) {
    const fileHash = `hash-nonexistent-${i}`;
    await mockCache.findInCache(fileHash);
  }
}

// Fonction pour tester la reprise après erreur
async function testResumePerformance() {
  console.log('\n==== Test de performance de la reprise après erreur ====');
  
  // Test avec différentes tailles de fichiers et probabilités d'erreur
  const testScenarios = [
    { size: 1024 * 1024, errorProb: 0.2, name: "Erreurs occasionnelles (20%)" },
    { size: 1024 * 1024, errorProb: 0.5, name: "Erreurs fréquentes (50%)" },
    { size: 5 * 1024 * 1024, errorProb: 0.1, name: "Fichier volumineux, erreurs rares (10%)" }
  ];
  
  for (const scenario of testScenarios) {
    console.log(`\n- Scénario: ${scenario.name}`);
    
    // Comparer upload sans reprise vs avec reprise
    const file = generateTestFile(scenario.size, 'image/jpeg', `test-resume-${scenario.size}-${scenario.errorProb}.jpg`);
    
    console.log('\n  Sans reprise:');
    const resultWithoutResume = await simulateUpload(file, {
      chunkSize: 64 * 1024,
      delay: 5,
      errorProbability: scenario.errorProb,
      resumable: false
    });
    
    console.log('\n  Avec reprise:');
    const resultWithResume = await simulateUpload(file, {
      chunkSize: 64 * 1024,
      delay: 5,
      errorProbability: scenario.errorProb,
      resumable: true
    });
    
    // Comparer les résultats
    console.log('\n  Comparaison:');
    console.log(`    - Sans reprise: ${resultWithoutResume.success ? 'SUCCÈS' : 'ÉCHEC'}, ${resultWithoutResume.uploadTime.toFixed(2)}ms`);
    console.log(`    - Avec reprise: ${resultWithResume.success ? 'SUCCÈS' : 'ÉCHEC'}, ${resultWithResume.uploadTime.toFixed(2)}ms`);
    
    if (resultWithoutResume.success && resultWithResume.success) {
      const diff = resultWithResume.uploadTime - resultWithoutResume.uploadTime;
      const percentDiff = (diff / resultWithoutResume.uploadTime) * 100;
      
      console.log(`    - Différence: ${diff.toFixed(2)}ms (${percentDiff.toFixed(2)}%)`);
      console.log(`    - Conclusion: La reprise est ${percentDiff > 0 ? 'plus lente' : 'plus rapide'} de ${Math.abs(percentDiff).toFixed(2)}%`);
    }
  }
}

// Fonction pour tester la performance d'upload de différentes tailles de fichiers
async function testUploadSizePerformance() {
  console.log('\n==== Test de performance d\'upload selon la taille ====');
  
  // Tester chaque taille de fichier
  for (const size of FILE_SIZES) {
    console.log(`\n- Test avec fichier de ${(size / 1024).toFixed(2)} KB:`);
    
    const results = [];
    
    // Plusieurs itérations pour obtenir une moyenne
    for (let i = 1; i <= ITERATIONS; i++) {
      console.log(`\n  Itération ${i}/${ITERATIONS}:`);
      
      const file = generateTestFile(size, 'image/jpeg', `test-size-${size}-${i}.jpg`);
      const result = await simulateUpload(file, {
        chunkSize: 64 * 1024,
        delay: 5,
        errorProbability: 0,
        resumable: true
      });
      
      results.push(result);
    }
    
    // Calculer et afficher les moyennes
    const avgTime = results.reduce((sum, r) => sum + r.uploadTime, 0) / results.length;
    const avgSpeed = results.reduce((sum, r) => sum + r.speed, 0) / results.length;
    
    console.log(`\n  Moyenne sur ${ITERATIONS} itérations:`);
    console.log(`    - Temps moyen: ${avgTime.toFixed(2)}ms`);
    console.log(`    - Vitesse moyenne: ${avgSpeed.toFixed(2)} KB/s`);
  }
}

// Fonction principale
async function runPerformanceTests() {
  console.log('Démarrage des tests de performance pour l\'upload et la gestion des médias');
  
  // Créer une instance de mock cache
  const mockCache = new MockMediaCache();
  
  // Exécuter les tests
  await testCachePerformance(mockCache);
  await testResumePerformance();
  await testUploadSizePerformance();
  
  console.log('\nTests de performance terminés');
}

// Exécuter les tests
runPerformanceTests().catch(console.error);