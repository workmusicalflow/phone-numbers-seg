<template>
  <div class="not-found-container">
    <div class="not-found-content">
      <h1 class="text-h2 text-primary q-mb-md">404</h1>
      <h2 class="text-h4 q-mb-lg">Page non trouvée</h2>
      <p class="text-body1 q-mb-xl">
        La page que vous recherchez n'existe pas ou a été déplacée.
      </p>
      <q-btn
        color="primary"
        label="Retour à l'accueil"
        :to="{ name: 'home' }"
        class="q-px-md"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
// Pas de logique nécessaire pour cette page
</script>

<style scoped>
.not-found-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 100px);
  padding: 2rem;
}

.not-found-content {
  text-align: center;
  max-width: 600px;
}
</style>
