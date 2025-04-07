<template>
  <q-page padding>
    <div class="q-pa-md">
      <h1 class="text-h3 q-mb-md">
        Oracle Gestionnaire de Contacts
      </h1>

      <p class="text-body1 q-mb-lg">
        Oracle vous permet de segmenter des numéros de téléphone,
        de gérer les segments associés, envoyer des SMS individuels et par Groupe de contacts.
      </p>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-4">
          <q-card class="my-card">
            <q-card-section>
              <div class="text-h6">Segmentation individuelle</div>
              <div class="text-subtitle2">Segmenter un numéro de téléphone</div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              Analysez et segmentez un numéro de téléphone individuel.
            </q-card-section>

            <q-card-actions align="right">
              <q-btn flat color="primary" label="Accéder" to="/segment" />
            </q-card-actions>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card class="my-card">
            <q-card-section>
              <div class="text-h6">Traitement par lot</div>
              <div class="text-subtitle2">Segmenter plusieurs numéros</div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              Traitez et segmentez plusieurs numéros de téléphone en une seule
              opération.
            </q-card-section>

            <q-card-actions align="right">
              <q-btn flat color="primary" label="Accéder" to="/batch" />
            </q-card-actions>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card class="my-card">
            <q-card-section>
              <div class="text-h6">Gestion des segments</div>
              <div class="text-subtitle2">
                Configurer les segments personnalisés
              </div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              Gérez les segments personnalisés pour améliorer la segmentation.
            </q-card-section>

            <q-card-actions align="right">
              <q-btn flat color="primary" label="Accéder" to="/segments" />
            </q-card-actions>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
// Home component logic
</script>

<style scoped>
.my-card {
  width: 100%;
  height: 100%;
}
</style>
