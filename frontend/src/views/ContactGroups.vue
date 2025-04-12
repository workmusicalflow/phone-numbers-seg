<template>
  <q-page padding>
    <div class="text-h4 q-mb-md">Groupes de Contacts</div>

    <div class="row q-col-gutter-md">
      <!-- Left side: Groups list -->
      <div class="col-12 col-md-4">
        <contact-group-list @group-selected="onGroupSelected" />
      </div>

      <!-- Right side: Group details -->
      <div class="col-12 col-md-8">
        <contact-group-detail-view 
          :group-id="selectedGroupId" 
          @group-deleted="onGroupDeleted" 
        />
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import ContactGroupList from '@/components/contacts/ContactGroupList.vue';
import ContactGroupDetailView from '@/components/contacts/ContactGroupDetailView.vue';

const selectedGroupId = ref<string | null>(null);

function onGroupSelected(id: string | null) {
  selectedGroupId.value = id;
}

function onGroupDeleted() {
  selectedGroupId.value = null;
}
</script>
