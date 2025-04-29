# Contact SMS History & Score Frontend Implementation Plan

## Overview

This plan outlines the frontend components needed to display SMS history and score information for contacts, completing the user experience for the recently implemented GraphQL API enhancements. These new components will allow users to:

1. View SMS statistics for each contact (total, sent, failed, score)
2. Browse the SMS history for a specific contact 
3. See detailed information about each SMS
4. Quickly assess SMS quality with visual indicators

## Implementation Details

### 1. Update GraphQL Queries

First, we need to update the existing contact queries to request the new SMS fields.

**File: `/frontend/src/services/api.js` or relevant GraphQL query file**

```javascript
// Update existing CONTACT_DETAILS query
export const CONTACT_DETAILS = gql`
  query GetContact($id: ID!) {
    contact(id: $id) {
      id
      name
      phoneNumber
      email
      notes
      createdAt
      updatedAt
      groups {
        id
        name
      }
      # Add new SMS fields
      smsTotalCount
      smsSentCount
      smsFailedCount
      smsScore
    }
  }
`;

// Update CONTACTS_LIST query to include SMS score
export const CONTACTS_LIST = gql`
  query GetContacts($limit: Int, $offset: Int, $search: String, $groupId: ID) {
    contacts(limit: $limit, offset: $offset, search: $search, groupId: $groupId) {
      id
      name
      phoneNumber
      email
      createdAt
      # Add SMS score for badge display
      smsTotalCount
      smsScore
    }
    contactsCount(search: $search, groupId: $groupId)
  }
`;

// Create a new query for SMS history with pagination
export const CONTACT_SMS_HISTORY = gql`
  query GetContactSMSHistory($id: ID!, $limit: Int, $offset: Int) {
    contact(id: $id) {
      id
      phoneNumber
      smsHistory(limit: $limit, offset: $offset) {
        id
        status
        message
        createdAt
        errorMessage
        senderName
        messageId
        senderAddress
      }
    }
  }
`;
```

### 2. Create SMS Statistics Component

This component will display key SMS statistics for a contact.

**File: `/frontend/src/components/contacts/ContactSMSStats.vue`**

```vue
<template>
  <q-card flat bordered class="contact-sms-stats q-mb-md">
    <q-card-section>
      <div class="text-h6">SMS Statistics</div>
      <div class="row q-mt-md">
        <div class="col-6 col-md-3 q-pa-sm">
          <div class="text-center">
            <div class="text-subtitle1">Total SMS</div>
            <div class="text-h5">{{ smsTotalCount }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 q-pa-sm">
          <div class="text-center">
            <div class="text-subtitle1">Sent</div>
            <div class="text-h5 text-positive">{{ smsSentCount }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 q-pa-sm">
          <div class="text-center">
            <div class="text-subtitle1">Failed</div>
            <div class="text-h5 text-negative">{{ smsFailedCount }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3 q-pa-sm">
          <div class="text-center">
            <div class="text-subtitle1">Score</div>
            <div class="text-h5" :class="scoreColorClass">{{ scoreFormatted }}</div>
          </div>
        </div>
      </div>
      <div class="row q-mt-md">
        <div class="col-12">
          <q-linear-progress
            :value="smsScore"
            size="20px"
            :color="scoreColorClass"
            track-color="grey-3"
            class="q-mt-sm"
          >
            <div class="absolute-full flex flex-center">
              <q-badge color="white" text-color="black" :label="scorePercentage" />
            </div>
          </q-linear-progress>
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>

<script>
export default {
  name: 'ContactSMSStats',
  props: {
    smsTotalCount: {
      type: Number,
      default: 0
    },
    smsSentCount: {
      type: Number,
      default: 0
    },
    smsFailedCount: {
      type: Number,
      default: 0
    },
    smsScore: {
      type: Number,
      default: 0
    }
  },
  computed: {
    scoreFormatted() {
      return this.smsScore.toFixed(2);
    },
    scorePercentage() {
      return `${Math.round(this.smsScore * 100)}%`;
    },
    scoreColorClass() {
      if (this.smsScore >= 0.8) return 'text-positive';
      if (this.smsScore >= 0.5) return 'text-warning';
      return 'text-negative';
    }
  }
}
</script>

<style scoped>
.contact-sms-stats {
  border-radius: 8px;
}
</style>
```

### 3. Create SMS History Component

This component will display the SMS history for a contact with pagination.

**File: `/frontend/src/components/contacts/ContactSMSHistory.vue`**

```vue
<template>
  <q-card flat bordered class="contact-sms-history">
    <q-card-section>
      <div class="row items-center">
        <div class="text-h6">SMS History</div>
        <q-space />
        <q-btn v-if="smsHistory.length > 0" outline color="primary" label="Export" icon="download" size="sm" @click="exportHistory" />
      </div>

      <div class="q-mt-md">
        <q-table
          :rows="smsHistory"
          :columns="columns"
          row-key="id"
          :pagination.sync="pagination"
          :loading="loading"
          @request="onRequest"
          binary-state-sort
          dense
        >
          <template v-slot:body="props">
            <q-tr :props="props" class="cursor-pointer" @click="viewDetails(props.row)">
              <q-td key="createdAt" :props="props">
                {{ formatDate(props.row.createdAt) }}
              </q-td>
              <q-td key="message" :props="props">
                <div class="ellipsis" style="max-width: 250px">{{ props.row.message }}</div>
              </q-td>
              <q-td key="status" :props="props">
                <q-badge :color="getStatusColor(props.row.status)" :label="props.row.status" />
              </q-td>
              <q-td key="actions" :props="props">
                <q-btn flat round dense size="sm" color="primary" icon="info" @click.stop="viewDetails(props.row)">
                  <q-tooltip>View details</q-tooltip>
                </q-btn>
              </q-td>
            </q-tr>
          </template>
          <template v-slot:no-data>
            <div class="full-width text-center q-pa-md">
              <q-icon name="sms" size="3rem" color="grey-5" />
              <p>No SMS history available for this contact</p>
              <q-btn color="primary" label="Send SMS" icon="send" @click="$emit('send-sms')" />
            </div>
          </template>
        </q-table>
      </div>
    </q-card-section>
  </q-card>
</template>

<script>
import { useQuery } from '@vue/apollo-composable';
import { CONTACT_SMS_HISTORY } from '../../services/api';
import { ref, computed, watch } from 'vue';
import { date, exportFile } from 'quasar';

export default {
  name: 'ContactSMSHistory',
  props: {
    contactId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ['view-details', 'send-sms'],
  setup(props, { emit }) {
    // Pagination state
    const pagination = ref({
      sortBy: 'createdAt',
      descending: true,
      page: 1,
      rowsPerPage: 10,
      rowsNumber: 0
    });

    // Computed variables for query variables
    const variables = computed(() => ({
      id: props.contactId,
      limit: pagination.value.rowsPerPage,
      offset: (pagination.value.page - 1) * pagination.value.rowsPerPage
    }));

    // Execute the query
    const { result, loading, refetch } = useQuery(
      CONTACT_SMS_HISTORY,
      variables
    );

    // Extract SMS history from the result
    const smsHistory = computed(() => {
      if (!result.value) return [];
      return result.value.contact?.smsHistory || [];
    });

    // Table columns
    const columns = [
      { name: 'createdAt', label: 'Date & Time', field: 'createdAt', sortable: true, align: 'left' },
      { name: 'message', label: 'Message', field: 'message', sortable: false, align: 'left' },
      { name: 'status', label: 'Status', field: 'status', sortable: true, align: 'center' },
      { name: 'actions', label: 'Actions', field: 'id', sortable: false, align: 'center' }
    ];

    // Pagination request handler
    const onRequest = (props) => {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      pagination.value.page = page;
      pagination.value.rowsPerPage = rowsPerPage;
      pagination.value.sortBy = sortBy;
      pagination.value.descending = descending;
      refetch();
    };

    // Status color helper
    const getStatusColor = (status) => {
      switch (status) {
        case 'SENT':
          return 'positive';
        case 'FAILED':
          return 'negative';
        case 'PENDING':
          return 'warning';
        case 'PROCESSING':
          return 'info';
        default:
          return 'grey';
      }
    };

    // Date formatter
    const formatDate = (dateStr) => {
      return date.formatDate(dateStr, 'DD/MM/YYYY HH:mm');
    };

    // View SMS details
    const viewDetails = (sms) => {
      emit('view-details', sms);
    };

    // Export SMS history to CSV
    const exportHistory = () => {
      const content = smsHistory.value.map(sms => {
        return {
          Date: formatDate(sms.createdAt),
          Status: sms.status,
          Message: sms.message,
          'Error Message': sms.errorMessage || ''
        };
      });

      const data = [
        Object.keys(content[0]),
        ...content.map(item => Object.values(item))
      ].map(e => e.join(',')).join('\n');

      const status = exportFile(
        `sms-history-${date.formatDate(Date.now(), 'YYYY-MM-DD')}.csv`,
        data,
        { mimeType: 'text/csv' }
      );

      if (status !== true) {
        // Browser denied file download...
        console.error('Export failed');
      }
    };

    // Watch for contactId changes to refetch
    watch(() => props.contactId, () => {
      refetch();
    });

    return {
      smsHistory,
      columns,
      pagination,
      loading,
      onRequest,
      getStatusColor,
      formatDate,
      viewDetails,
      exportHistory
    };
  }
}
</script>

<style scoped>
.contact-sms-history {
  border-radius: 8px;
}
</style>
```

### 4. Create SMS Detail Dialog

This component will show detailed information for a single SMS message.

**File: `/frontend/src/components/contacts/SMSDetailDialog.vue`**

```vue
<template>
  <q-dialog ref="dialogRef" v-model="isOpen">
    <q-card class="sms-detail-dialog">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">SMS Details</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="q-mb-md">
          <q-badge :color="statusColor" class="q-pa-sm">
            {{ sms.status }}
          </q-badge>
          <div class="text-caption q-mt-sm">
            {{ formatDate(sms.createdAt) }}
          </div>
        </div>

        <q-list>
          <q-item>
            <q-item-section>
              <q-item-label caption>Sender</q-item-label>
              <q-item-label>{{ sms.senderName || 'Default Sender' }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-item-label caption>Recipient</q-item-label>
              <q-item-label>{{ sms.phoneNumber }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section>
              <q-item-label caption>Message</q-item-label>
              <q-item-label class="message-content">{{ sms.message }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-item v-if="sms.errorMessage">
            <q-item-section>
              <q-item-label caption>Error Message</q-item-label>
              <q-item-label class="text-negative">{{ sms.errorMessage }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-item v-if="sms.messageId">
            <q-item-section>
              <q-item-label caption>Message ID</q-item-label>
              <q-item-label class="text-code">{{ sms.messageId }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Send Again" color="primary" icon="send" @click="$emit('resend', sms)" v-if="sms.status === 'FAILED'" />
        <q-btn flat label="Close" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import { ref, computed } from 'vue';
import { date } from 'quasar';
import { useDialogPluginComponent } from 'quasar';

export default {
  name: 'SMSDetailDialog',
  props: {
    sms: {
      type: Object,
      required: true
    }
  },
  emits: [
    ...useDialogPluginComponent.emits,
    'resend'
  ],
  setup(props) {
    const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } = useDialogPluginComponent();
    const isOpen = ref(true);

    const statusColor = computed(() => {
      switch (props.sms.status) {
        case 'SENT':
          return 'positive';
        case 'FAILED':
          return 'negative';
        case 'PENDING':
          return 'warning';
        case 'PROCESSING':
          return 'info';
        default:
          return 'grey';
      }
    });

    const formatDate = (dateStr) => {
      return date.formatDate(dateStr, 'DD/MM/YYYY HH:mm:ss');
    };

    return {
      dialogRef,
      onDialogHide,
      onDialogOK,
      onDialogCancel,
      isOpen,
      statusColor,
      formatDate
    };
  }
}
</script>

<style scoped>
.sms-detail-dialog {
  min-width: 350px;
  max-width: 500px;
}
.message-content {
  white-space: pre-wrap;
  font-size: 14px;
}
.text-code {
  font-family: monospace;
  background: #f5f5f5;
  padding: 2px 4px;
  border-radius: 3px;
  font-size: 12px;
}
</style>
```

### 5. Add SMS Quality Badge to Contact Card/List

Add a visual indicator of SMS quality to contact listings.

**File: `/frontend/src/components/contacts/ContactListItem.vue`** (or similar)

```vue
<template>
  <q-item clickable v-ripple @click="$emit('click', contact.id)">
    <q-item-section avatar>
      <q-avatar color="primary" text-color="white">
        {{ contact.name.charAt(0).toUpperCase() }}
      </q-avatar>
    </q-item-section>

    <q-item-section>
      <q-item-label>{{ contact.name }}</q-item-label>
      <q-item-label caption>{{ contact.phoneNumber }}</q-item-label>
    </q-item-section>

    <q-item-section side v-if="contact.smsTotalCount > 0">
      <q-badge
        outline
        :color="scoreColor"
        :label="`${scorePercentage} SMS`"
        class="sms-quality-badge"
      >
        <q-tooltip>
          SMS delivery success rate: {{ scorePercentage }}
        </q-tooltip>
      </q-badge>
    </q-item-section>
  </q-item>
</template>

<script>
export default {
  name: 'ContactListItem',
  props: {
    contact: {
      type: Object,
      required: true
    }
  },
  computed: {
    scorePercentage() {
      return `${Math.round(this.contact.smsScore * 100)}%`;
    },
    scoreColor() {
      if (this.contact.smsScore >= 0.8) return 'positive';
      if (this.contact.smsScore >= 0.5) return 'warning';
      return 'negative';
    }
  }
}
</script>

<style scoped>
.sms-quality-badge {
  font-size: 0.7rem;
}
</style>
```

### 6. Create Empty State Component

Create a reusable component for handling empty state.

**File: `/frontend/src/components/common/EmptyState.vue`**

```vue
<template>
  <div class="empty-state q-pa-lg text-center">
    <q-icon :name="icon" size="4rem" color="grey-5" />
    <h6 class="q-mt-md">{{ title }}</h6>
    <p class="text-grey">{{ message }}</p>
    <slot name="actions"></slot>
  </div>
</template>

<script>
export default {
  name: 'EmptyState',
  props: {
    icon: {
      type: String,
      default: 'info'
    },
    title: {
      type: String,
      required: true
    },
    message: {
      type: String,
      required: true
    }
  }
}
</script>

<style scoped>
.empty-state {
  padding: 2rem;
}
</style>
```

### 7. Update Contact Detail View

Integrate the new components into the contact detail page.

**File: `/frontend/src/views/ContactDetail.vue`**

```vue
<template>
  <div class="contact-detail-page q-pa-md">
    <div class="row q-col-gutter-md">
      <!-- Contact Info Card -->
      <div class="col-12">
        <q-card v-if="loading" class="q-pa-lg">
          <q-skeleton type="rect" height="150px" />
        </q-card>
        
        <q-card v-else-if="contact" class="q-pa-md">
          <q-card-section>
            <div class="row items-center">
              <div class="col-grow">
                <div class="text-h5">{{ contact.name }}</div>
                <div class="text-subtitle1">{{ contact.phoneNumber }}</div>
                <div class="text-caption" v-if="contact.email">{{ contact.email }}</div>
              </div>
              <div>
                <q-btn color="primary" icon="send" label="Send SMS" @click="showSendSMSDialog = true" />
              </div>
            </div>
            
            <q-separator class="q-my-md" />
            
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <div v-if="contact.notes" class="q-mt-sm">
                  <div class="text-subtitle2">Notes</div>
                  <p>{{ contact.notes }}</p>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-subtitle2">Groups</div>
                <div class="q-mt-xs">
                  <q-chip 
                    v-for="group in contact.groups" 
                    :key="group.id"
                    size="sm"
                    outline
                    color="primary"
                  >
                    {{ group.name }}
                  </q-chip>
                  <div v-if="!contact.groups || contact.groups.length === 0" class="text-grey-6">
                    Not in any groups
                  </div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      
      <!-- SMS Statistics -->
      <div class="col-12">
        <contact-sms-stats
          v-if="contact"
          :sms-total-count="contact.smsTotalCount"
          :sms-sent-count="contact.smsSentCount"
          :sms-failed-count="contact.smsFailedCount"
          :sms-score="contact.smsScore"
        />
      </div>
      
      <!-- SMS History -->
      <div class="col-12">
        <contact-sms-history
          v-if="contact"
          :contact-id="contact.id"
          @view-details="openSMSDetails"
          @send-sms="showSendSMSDialog = true"
        />
      </div>
    </div>
    
    <!-- SMS Detail Dialog -->
    <sms-detail-dialog
      v-if="selectedSMS"
      v-model="smsDetailDialogVisible"
      :sms="selectedSMS"
      @resend="resendSMS"
    />
    
    <!-- Send SMS Dialog -->
    <q-dialog v-model="showSendSMSDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Send SMS to {{ contact?.name }}</div>
        </q-card-section>
        
        <q-card-section>
          <q-input
            v-model="newSMSMessage"
            type="textarea"
            label="Message"
            :rules="[val => !!val || 'Message is required']"
            counter
            maxlength="160"
            filled
            autofocus
          />
        </q-card-section>
        
        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="negative" v-close-popup />
          <q-btn 
            flat 
            label="Send" 
            color="positive" 
            @click="sendSMS" 
            :disable="!newSMSMessage"
            :loading="sendingSMS"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useQuery, useMutation } from '@vue/apollo-composable';
import { CONTACT_DETAILS, SEND_SMS } from '../services/api';
import ContactSMSStats from '../components/contacts/ContactSMSStats.vue';
import ContactSMSHistory from '../components/contacts/ContactSMSHistory.vue';
import SMSDetailDialog from '../components/contacts/SMSDetailDialog.vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';

export default {
  name: 'ContactDetail',
  components: {
    ContactSMSStats,
    ContactSMSHistory,
    SMSDetailDialog
  },
  setup() {
    const $q = useQuasar();
    const route = useRoute();
    const router = useRouter();
    const contactId = computed(() => route.params.id);
    
    // Query for contact details
    const { result, loading, refetch } = useQuery(CONTACT_DETAILS, () => ({
      id: contactId.value
    }));
    
    // Extract contact from result
    const contact = computed(() => result.value?.contact);
    
    // SMS detail dialog state
    const smsDetailDialogVisible = ref(false);
    const selectedSMS = ref(null);
    
    // Send SMS dialog state
    const showSendSMSDialog = ref(false);
    const newSMSMessage = ref('');
    const sendingSMS = ref(false);
    
    // Send SMS mutation
    const { mutate: sendSMSMutation } = useMutation(SEND_SMS);
    
    // Handler for opening SMS details
    const openSMSDetails = (sms) => {
      selectedSMS.value = sms;
      smsDetailDialogVisible.value = true;
    };
    
    // Handler for sending a new SMS
    const sendSMS = async () => {
      if (!newSMSMessage.value || !contact.value) return;
      
      sendingSMS.value = true;
      try {
        const response = await sendSMSMutation({
          phoneNumber: contact.value.phoneNumber,
          message: newSMSMessage.value
        });
        
        if (response.data.sendSms.status === 'SUCCESS') {
          $q.notify({
            color: 'positive',
            message: 'SMS sent successfully',
            icon: 'check'
          });
          
          // Close dialog and clear form
          showSendSMSDialog.value = false;
          newSMSMessage.value = '';
          
          // Refetch contact data to update SMS statistics
          await refetch();
        } else {
          throw new Error(response.data.sendSms.message || 'Failed to send SMS');
        }
      } catch (error) {
        $q.notify({
          color: 'negative',
          message: `Error sending SMS: ${error.message}`,
          icon: 'error'
        });
      } finally {
        sendingSMS.value = false;
      }
    };
    
    // Handler for resending a failed SMS
    const resendSMS = async (sms) => {
      sendingSMS.value = true;
      try {
        const response = await sendSMSMutation({
          phoneNumber: sms.phoneNumber,
          message: sms.message
        });
        
        if (response.data.sendSms.status === 'SUCCESS') {
          $q.notify({
            color: 'positive',
            message: 'SMS resent successfully',
            icon: 'check'
          });
          
          // Close dialog
          smsDetailDialogVisible.value = false;
          
          // Refetch contact data to update SMS statistics
          await refetch();
        } else {
          throw new Error(response.data.sendSms.message || 'Failed to resend SMS');
        }
      } catch (error) {
        $q.notify({
          color: 'negative',
          message: `Error resending SMS: ${error.message}`,
          icon: 'error'
        });
      } finally {
        sendingSMS.value = false;
      }
    };
    
    // Load contact data when mounted
    onMounted(async () => {
      if (!contactId.value) {
        router.push('/contacts');
      }
    });
    
    return {
      contact,
      loading,
      smsDetailDialogVisible,
      selectedSMS,
      openSMSDetails,
      showSendSMSDialog,
      newSMSMessage,
      sendingSMS,
      sendSMS,
      resendSMS
    };
  }
}
</script>
```

### 8. Update Contacts List View

Update the contacts list to show SMS quality badges.

**File: `/frontend/src/views/Contacts.vue`**

```vue
<template>
  <div class="contacts-page q-pa-md">
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <h1 class="text-h5 q-mt-none q-mb-md">Contacts</h1>
      </div>
      <div class="col-12 col-md-6 flex justify-end items-center">
        <q-btn color="primary" icon="add" label="New Contact" @click="showCreateContactDialog = true" />
      </div>
    </div>
    
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-3">
        <!-- Groups filter sidebar -->
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1">Filter by Group</div>
            <q-list padding>
              <q-item 
                clickable 
                v-ripple 
                :active="!selectedGroupId" 
                active-class="bg-primary text-white"
                @click="selectedGroupId = null"
              >
                <q-item-section>All Contacts</q-item-section>
                <q-item-section side>
                  <q-badge color="primary" :label="totalContacts" />
                </q-item-section>
              </q-item>
              
              <q-item 
                v-for="group in contactGroups" 
                :key="group.id"
                clickable 
                v-ripple 
                :active="selectedGroupId === group.id" 
                active-class="bg-primary text-white"
                @click="selectedGroupId = group.id"
              >
                <q-item-section>{{ group.name }}</q-item-section>
                <q-item-section side>
                  <q-badge color="primary" :label="group.contactCount" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
      
      <div class="col-12 col-md-9">
        <!-- Search bar -->
        <q-input 
          v-model="searchQuery" 
          filled 
          placeholder="Search contacts..." 
          class="q-mb-md"
          clearable
          @clear="onClearSearch"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
        
        <!-- Contacts list -->
        <q-card flat bordered>
          <q-card-section v-if="loading">
            <div v-for="i in 5" :key="i" class="q-mb-sm">
              <q-skeleton type="QItem" />
            </div>
          </q-card-section>
          
          <q-list v-else-if="contacts.length > 0">
            <contact-list-item 
              v-for="contact in contacts" 
              :key="contact.id"
              :contact="contact"
              @click="viewContact(contact.id)"
            />
          </q-list>
          
          <empty-state
            v-else
            icon="people"
            title="No contacts found"
            :message="getEmptyStateMessage()"
          >
            <template v-slot:actions>
              <q-btn color="primary" label="Create Contact" icon="add" @click="showCreateContactDialog = true" />
            </template>
          </empty-state>
          
          <!-- Pagination -->
          <q-card-section v-if="contacts.length > 0">
            <div class="row justify-center">
              <q-pagination
                v-model="currentPage"
                :max="Math.ceil(totalContacts / pageSize)"
                :max-pages="6"
                :boundary-numbers="true"
                :direction-links="true"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    
    <!-- Create Contact Dialog -->
    <!-- Dialog implementation here -->
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import { useQuery } from '@vue/apollo-composable';
import { CONTACTS_LIST, CONTACT_GROUPS } from '../services/api';
import ContactListItem from '../components/contacts/ContactListItem.vue';
import EmptyState from '../components/common/EmptyState.vue';
import { useRouter } from 'vue-router';

export default {
  name: 'ContactsView',
  components: {
    ContactListItem,
    EmptyState
  },
  setup() {
    const router = useRouter();
    const searchQuery = ref('');
    const selectedGroupId = ref(null);
    const currentPage = ref(1);
    const pageSize = 10;
    const showCreateContactDialog = ref(false);
    
    // Computed variables for the contacts query
    const contactsVariables = computed(() => ({
      limit: pageSize,
      offset: (currentPage.value - 1) * pageSize,
      search: searchQuery.value || undefined,
      groupId: selectedGroupId.value || undefined
    }));
    
    // Query for contacts list
    const { result: contactsResult, loading: contactsLoading, refetch: refetchContacts } = useQuery(
      CONTACTS_LIST,
      contactsVariables
    );
    
    // Query for contact groups
    const { result: groupsResult, loading: groupsLoading } = useQuery(CONTACT_GROUPS);
    
    // Extract data from query results
    const contacts = computed(() => contactsResult.value?.contacts || []);
    const totalContacts = computed(() => contactsResult.value?.contactsCount || 0);
    const contactGroups = computed(() => groupsResult.value?.contactGroups || []);
    
    // Combined loading state
    const loading = computed(() => contactsLoading.value || groupsLoading.value);
    
    // Handler for viewing a contact
    const viewContact = (id) => {
      router.push(`/contacts/${id}`);
    };
    
    // Handler for clearing search
    const onClearSearch = () => {
      searchQuery.value = '';
    };
    
    // Get appropriate empty state message based on filters
    const getEmptyStateMessage = () => {
      if (searchQuery.value) {
        return `No contacts found matching "${searchQuery.value}"`;
      } else if (selectedGroupId.value) {
        const groupName = contactGroups.value.find(g => g.id === selectedGroupId.value)?.name || 'selected group';
        return `No contacts in the ${groupName}`;
      } else {
        return "You don't have any contacts yet. Create your first contact to get started.";
      }
    };
    
    // Reset to page 1 when filters change
    watch([searchQuery, selectedGroupId], () => {
      currentPage.value = 1;
    });
    
    return {
      searchQuery,
      selectedGroupId,
      currentPage,
      pageSize,
      contacts,
      totalContacts,
      contactGroups,
      loading,
      showCreateContactDialog,
      viewContact,
      onClearSearch,
      getEmptyStateMessage
    };
  }
}
</script>
```

### 9. Update Contact Type Definition (TypeScript)

**File: `/frontend/src/types/contact.ts`**

```typescript
export interface Contact {
  id: string;
  name: string;
  phoneNumber: string;
  email?: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
  groups?: ContactGroup[];
  // New SMS fields
  smsTotalCount: number;
  smsSentCount: number;
  smsFailedCount: number;
  smsScore: number;
}

export interface ContactGroup {
  id: string;
  name: string;
  description?: string;
  contactCount: number;
}

export interface SMSHistory {
  id: string;
  phoneNumber: string;
  message: string;
  status: 'SENT' | 'FAILED' | 'PENDING' | 'PROCESSING' | 'CANCELLED';
  createdAt: string;
  messageId?: string;
  errorMessage?: string;
  senderName?: string;
  senderAddress?: string;
}
```

## Implementation Timeline

### Day 1: Setup & Query Updates

- Update GraphQL queries in API service
- Create the Contact SMS Statistics component
- Update TypeScript interfaces

### Day 2: SMS History Components

- Implement SMS History table component
- Create SMS Detail Dialog
- Create Empty State component
- Add necessary composables for data handling

### Day 3: Integration & Contact List Updates

- Add SMS quality badge to contact list items
- Update Contact Detail view to integrate new components
- Update Contacts List view to display SMS quality information

### Day 4: Testing & Refinement

- Create unit tests for new components
- Test integration with backend API
- Fix any issues or edge cases
- Optimize performance

### Day 5: Documentation & Final Polish

- Document new components
- Add user guidance tooltips
- Final visual polish
- Responsive design adjustments

## Technical Considerations

### Performance

- Use pagination for SMS history to prevent loading all messages at once
- Implement caching for recently viewed contacts
- Use GraphQL batching for efficient data loading

### Accessibility

- Ensure color contrast meets WCAG standards for the SMS quality indicators
- Add proper ARIA labels for all components
- Verify keyboard navigation works correctly

### Responsiveness

- Ensure all components display properly on mobile devices
- Adjust layouts for different screen sizes
- Test on various device sizes

### Error Handling

- Implement proper error states for all components
- Provide clear error messages to users
- Handle network failures gracefully

## Conclusion

This implementation will enhance the user experience by providing valuable SMS history and quality information directly on contact views. By visually representing SMS delivery success rates, users can quickly identify problematic contacts and take appropriate action.