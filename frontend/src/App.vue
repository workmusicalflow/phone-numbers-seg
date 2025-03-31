<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-toolbar-title>
          Oracle <span class="text-caption">propulsé par Thalamus</span>
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item-label header>Navigation</q-item-label>

        <q-item clickable v-ripple to="/" exact>
          <q-item-section avatar>
            <q-icon name="home" />
          </q-item-section>
          <q-item-section>Accueil</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/segment">
          <q-item-section avatar>
            <q-icon name="phone" />
          </q-item-section>
          <q-item-section>Segmentation individuelle</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/batch">
          <q-item-section avatar>
            <q-icon name="list" />
          </q-item-section>
          <q-item-section>Traitement par lot</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/segments">
          <q-item-section avatar>
            <q-icon name="settings" />
          </q-item-section>
          <q-item-section>Gestion des segments</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/sms">
          <q-item-section avatar>
            <q-icon name="message" />
          </q-item-section>
          <q-item-section>Envoi de SMS</q-item-section>
        </q-item>

        <q-item clickable v-ripple to="/import">
          <q-item-section avatar>
            <q-icon name="upload" />
          </q-item-section>
          <q-item-section>Import/Export</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from "vue";

const leftDrawerOpen = ref(true);
</script>

<style>
/* Global styles */
</style>
