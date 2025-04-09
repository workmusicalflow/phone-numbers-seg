<template>
  <div class="admin-layout">
    <header>
      <nav>
        <router-link to="/admin/dashboard">Tableau de bord</router-link>
        <router-link to="/admin/users">Utilisateurs</router-link>
        <router-link to="/admin/sender-names">Noms d'expéditeur</router-link>
        <router-link to="/admin/sms-orders">Commandes SMS</router-link>
        <router-link to="/admin/orange-api-config">Configuration API Orange</router-link>
      </nav>
    </header>
    <main>
      <router-view></router-view>
    </main>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'AdminLayout'
});
</script>

<style scoped>
.admin-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

header {
  background-color: #2c3e50;
  padding: 1rem;
}

nav {
  display: flex;
  gap: 1rem;
}

nav a {
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
}

nav a:hover {
  background-color: #34495e;
}

nav a.router-link-exact-active {
  background-color: #1abc9c;
}

main {
  flex: 1;
  padding: 1rem;
}
</style>
