<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import EmployeeCheckoutDialog from '@/components/EmployeeCheckoutDialog.vue'
import apiClient from '@/api/client'
import type { InventoryTransaction, ApiResponse } from '@/types'

const authStore = useAuthStore()

// Dialog state
const showCheckoutDialog = ref(false)

// Today's checkouts
const todayCheckouts = ref<InventoryTransaction[]>([])
const loading = ref(false)

// Load today's checkouts by current user
async function loadTodayCheckouts(): Promise<void> {
  loading.value = true
  try {
    const today = new Date().toISOString().split('T')[0]
    const response = await apiClient.get<ApiResponse<InventoryTransaction[]>>('/inventory-transactions', {
      params: {
        created_by: authStore.user?.id,
        type: 'usage',
        date_from: today,
        with: 'item,project'
      }
    })
    todayCheckouts.value = response.data.data
  } catch (error) {
    console.error('Failed to load today\'s checkouts:', error)
    todayCheckouts.value = []
  } finally {
    loading.value = false
  }
}

// Format time
function formatTime(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

// Total items checked out today
const totalItemsToday = computed(() => {
  return todayCheckouts.value.reduce((sum, checkout) => sum + checkout.quantity, 0)
})

// Handle checkout completed
function handleCheckoutCompleted(): void {
  showCheckoutDialog.value = false
  loadTodayCheckouts()
}

onMounted(() => {
  loadTodayCheckouts()
})
</script>

<template>
  <q-page padding>
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h4">Material Checkout</div>
        <div class="text-subtitle2 text-grey-7">
          Quick checkout for shop materials
        </div>
      </div>
    </div>

    <!-- Info Banner -->
    <q-banner class="bg-blue-1 q-mb-md" rounded>
      <template #avatar>
        <q-icon name="info" color="blue" />
      </template>
      <div class="text-body2">
        Scan QR codes or select items to check out materials for your projects. All checkouts are tracked with your employee ID.
      </div>
    </q-banner>

    <!-- Quick Stats -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h4 text-primary">{{ todayCheckouts.length }}</div>
            <div class="text-grey-7">Checkouts Today</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h4 text-green">{{ totalItemsToday.toFixed(0) }}</div>
            <div class="text-grey-7">Total Units</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Today's Checkouts by Me -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="row items-center q-mb-sm">
          <div class="col">
            <div class="text-subtitle2">My Checkouts Today</div>
          </div>
          <div class="col-auto">
            <q-btn
              flat
              dense
              icon="refresh"
              @click="loadTodayCheckouts"
              :loading="loading"
            >
              <q-tooltip>Refresh</q-tooltip>
            </q-btn>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center q-pa-md">
          <q-spinner color="primary" size="2em" />
        </div>

        <!-- Empty State -->
        <div v-else-if="todayCheckouts.length === 0" class="text-center q-pa-lg">
          <q-icon name="inventory_2" size="4em" color="grey-5" />
          <div class="text-h6 q-mt-md text-grey-7">No checkouts today</div>
          <div class="text-grey-7 q-mt-sm">
            Click the + button below to check out materials
          </div>
        </div>

        <!-- Checkouts List -->
        <q-list v-else separator>
          <q-item v-for="checkout in todayCheckouts" :key="checkout.id">
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white" icon="inventory_2" />
            </q-item-section>

            <q-item-section>
              <q-item-label>{{ checkout.item?.name || 'Unknown Item' }}</q-item-label>
              <q-item-label caption>
                <span class="text-weight-medium">{{ checkout.quantity }}</span>
                {{ checkout.item?.unit || 'units' }}
                •
                {{ checkout.project?.name || 'Unknown Project' }}
              </q-item-label>
              <q-item-label caption v-if="checkout.notes" class="q-mt-xs">
                <q-icon name="notes" size="xs" /> {{ checkout.notes }}
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-item-label caption>{{ formatTime(checkout.created_at) }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>

    <!-- Floating Action Button -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="add"
        color="primary"
        @click="showCheckoutDialog = true"
        size="lg"
      >
        <q-tooltip>Check Out Material</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Checkout Dialog -->
    <EmployeeCheckoutDialog
      v-model="showCheckoutDialog"
      @checked-out="handleCheckoutCompleted"
    />
  </q-page>
</template>

<style scoped>
.q-page-sticky {
  z-index: 2000;
}
</style>
