<template>
  <div class="lazy-loader">
    <div v-if="loading" class="loader-container">
      <q-spinner color="primary" size="3em" />
      <div class="q-mt-sm">{{ loadingMessage }}</div>
    </div>
    <div v-else-if="error" class="error-container">
      <q-icon name="error" color="negative" size="3em" />
      <div class="q-mt-sm text-negative">{{ errorMessage }}</div>
      <q-btn color="primary" class="q-mt-md" @click="retry">Réessayer</q-btn>
    </div>
    <component v-else :is="resolvedComponent" v-bind="$attrs"></component>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, defineAsyncComponent, shallowRef } from 'vue';

// Props
const props = defineProps<{
  componentPath: string;
  loadingMessage?: string;
  errorMessage?: string;
  timeout?: number;
}>();

// État local
const loading = ref(true);
const error = ref(false);
const resolvedComponent = shallowRef<any>(null);

// Valeurs par défaut
const defaultLoadingMessage = 'Chargement du composant...';
const defaultErrorMessage = 'Erreur lors du chargement du composant';
const defaultTimeout = 10000; // 10 secondes

// Méthode pour charger le composant
const loadComponent = async () => {
  loading.value = true;
  error.value = false;
  
  try {
    // Utiliser dynamic import pour charger le composant de manière asynchrone
    const component = defineAsyncComponent({
      loader: () => import(`../views/${props.componentPath}.vue`),
      // Utiliser undefined au lieu de null pour éviter les erreurs TypeScript
      loadingComponent: undefined, 
      errorComponent: undefined,
      delay: 0, // Pas de délai avant d'afficher le composant de chargement
      timeout: props.timeout || defaultTimeout, // Timeout après lequel on affiche une erreur
      suspensible: false, // Ne pas utiliser la fonctionnalité de suspension de Vue 3
      onError: (err, retry, fail, attempts) => {
        if (attempts <= 3) {
          // Réessayer automatiquement jusqu'à 3 fois
          console.log(`Tentative de rechargement du composant (${attempts}/3)...`);
          retry();
        } else {
          // Après 3 tentatives, afficher l'erreur
          console.error('Erreur lors du chargement du composant:', err);
          loading.value = false;
          error.value = true;
          fail();
        }
      }
    });
    
    resolvedComponent.value = component;
    loading.value = false;
  } catch (err) {
    console.error('Erreur lors du chargement du composant:', err);
    loading.value = false;
    error.value = true;
  }
};

// Méthode pour réessayer de charger le composant
const retry = () => {
  loadComponent();
};

// Cycle de vie
onMounted(() => {
  loadComponent();
});
</script>

<style scoped>
.lazy-loader {
  width: 100%;
  height: 100%;
}

.loader-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}
</style>
