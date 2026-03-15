<template>
  <q-page class="q-pa-md driver-page">
    <!-- Header -->
    <div class="row q-mb-md items-center">
      <div class="col">
        <h5 class="q-my-none">My Assignments</h5>
        <div class="text-caption text-grey-7">{{ formattedDate }}</div>
      </div>
      <div class="col-auto row q-gutter-sm items-center">
        <q-btn
          color="orange"
          label="Ad-Hoc Pickup"
          icon="add_circle"
          size="md"
          padding="sm lg"
          @click="showAdhocDialog = true"
        />
        <q-btn
          flat round
          icon="refresh"
          @click="loadData"
          :loading="store.loading"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading && !store.myAssignments.length" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
      <div class="q-mt-md text-grey-7">Loading assignments...</div>
    </div>

    <template v-else>
      <!-- Active Transport (picked_up) — most prominent -->
      <div v-if="activeOrder" class="q-mb-lg">
        <div class="text-overline text-warning q-mb-sm">IN TRANSIT</div>
        <q-card bordered class="active-card">
          <q-card-section>
            <div class="row items-center q-mb-sm">
              <div class="col">
                <div class="text-h6 text-weight-bold">
                  {{ activeOrder.equipment?.name || 'Equipment #' + activeOrder.equipment_id }}
                </div>
                <div class="text-caption text-grey-7">{{ activeOrder.order_number }}</div>
              </div>
              <div class="col-auto">
                <q-badge v-if="activeOrder.is_adhoc" color="orange" label="Ad-Hoc" class="q-mr-xs" />
                <q-badge color="warning" label="In Transit" />
              </div>
            </div>

            <q-separator class="q-my-sm" />

            <div class="q-mb-sm">
              <div class="text-caption text-grey-6">DELIVERING TO</div>
              <div class="text-subtitle1">
                <q-icon name="flag" color="positive" size="sm" class="q-mr-xs" />
                {{ formatLocation(activeOrder.dropoff_location_type, activeOrder.dropoff_location_id) }}
              </div>
            </div>

            <div v-if="activeOrder.special_instructions" class="q-mb-md text-body2 text-grey-8">
              <q-icon name="info" size="xs" class="q-mr-xs" />
              {{ activeOrder.special_instructions }}
            </div>

            <div v-if="activeOrder.picked_up_at" class="text-caption text-grey-6 q-mb-md">
              Picked up: {{ formatTimestamp(activeOrder.picked_up_at) }}
            </div>
          </q-card-section>

          <q-card-actions class="q-pa-md">
            <q-btn
              color="positive"
              label="DROP OFF"
              icon="download"
              size="xl"
              padding="md xl"
              class="full-width"
              @click="onDropoff(activeOrder)"
            />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Assignment Queue (assigned) -->
      <div v-if="assignedOrders.length" class="q-mb-lg">
        <div class="text-overline text-info q-mb-sm">READY FOR PICKUP ({{ assignedOrders.length }})</div>
        <div class="column q-gutter-md">
          <q-card v-for="order in assignedOrders" :key="order.id" bordered flat>
            <q-card-section>
              <div class="row items-center q-mb-sm">
                <div class="col">
                  <div class="text-subtitle1 text-weight-bold">
                    {{ order.equipment?.name || 'Equipment #' + order.equipment_id }}
                  </div>
                  <div class="text-caption text-grey-7">{{ order.order_number }}</div>
                </div>
                <div class="col-auto">
                  <q-badge
                    v-if="order.priority !== 'normal'"
                    :color="priorityColor(order.priority)"
                    :label="order.priority.toUpperCase()"
                    class="q-mr-xs"
                  />
                  <q-badge color="info" label="Assigned" />
                </div>
              </div>

              <!-- Locations -->
              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-6">
                  <div class="text-caption text-grey-6">PICKUP</div>
                  <div class="text-body2">
                    <q-icon name="place" size="xs" color="negative" class="q-mr-xs" />
                    {{ formatLocation(order.pickup_location_type, order.pickup_location_id) }}
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-6">DROPOFF</div>
                  <div class="text-body2">
                    <q-icon name="flag" size="xs" color="positive" class="q-mr-xs" />
                    {{ formatLocation(order.dropoff_location_type, order.dropoff_location_id) }}
                  </div>
                </div>
              </div>

              <!-- Schedule -->
              <div class="text-body2 q-mb-xs">
                <q-icon name="event" size="xs" class="q-mr-xs" />
                {{ formatDate(order.scheduled_date) }}
                <span v-if="order.scheduled_time"> at {{ order.scheduled_time }}</span>
              </div>

              <!-- Special Instructions (expandable) -->
              <q-expansion-item
                v-if="order.special_instructions"
                dense
                dense-toggle
                icon="info"
                label="Special Instructions"
                header-class="text-caption text-grey-7"
                class="q-mt-xs"
              >
                <div class="q-pa-sm text-body2">{{ order.special_instructions }}</div>
              </q-expansion-item>
            </q-card-section>

            <q-card-actions class="q-pa-md">
              <q-btn
                color="primary"
                label="PICK UP"
                icon="upload"
                size="lg"
                padding="md xl"
                class="full-width"
                @click="onPickup(order)"
              />
            </q-card-actions>
          </q-card>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!activeOrder && !assignedOrders.length && !completedOrders.length" class="text-center q-pa-xl">
        <q-icon name="local_shipping" size="4em" color="grey-4" />
        <div class="text-h6 text-grey-5 q-mt-md">No assignments today</div>
        <div class="text-body2 text-grey-6">Check back later or use Ad-Hoc Pickup for emergency moves</div>
      </div>

      <!-- Completed Today -->
      <q-expansion-item
        v-if="completedOrders.length"
        icon="check_circle"
        :label="`Completed Today (${completedOrders.length})`"
        header-class="text-overline text-positive"
        default-closed
      >
        <div class="column q-gutter-sm q-pa-sm">
          <q-card v-for="order in completedOrders" :key="order.id" bordered flat class="bg-grey-1">
            <q-card-section class="q-py-sm">
              <div class="row items-center">
                <div class="col">
                  <div class="text-subtitle2">
                    {{ order.equipment?.name || 'Equipment #' + order.equipment_id }}
                  </div>
                  <div class="text-caption text-grey-7">
                    {{ order.order_number }}
                    <span v-if="order.completed_at"> — {{ formatTimestamp(order.completed_at) }}</span>
                  </div>
                </div>
                <div class="col-auto">
                  <q-badge v-if="order.is_adhoc" color="orange" label="Ad-Hoc" class="q-mr-xs" />
                  <q-badge color="positive" label="Done" />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </q-expansion-item>
    </template>

    <!-- Transport Action Dialog (pickup/dropoff) -->
    <TransportActionDialog
      v-model="showActionDialog"
      :order="selectedOrder"
      :action="actionType"
      @completed="onActionCompleted"
    />

    <!-- Ad-Hoc Pickup Dialog -->
    <AdhocPickupDialog
      v-model="showAdhocDialog"
      @completed="onActionCompleted"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useTransportOrderStore } from '@/stores/transportOrder'
import type { TransportOrder } from '@/types'
import TransportActionDialog from '@/components/TransportActionDialog.vue'
import AdhocPickupDialog from '@/components/AdhocPickupDialog.vue'

const store = useTransportOrderStore()

const selectedDate = ref(new Date().toISOString().split('T')[0])
const showActionDialog = ref(false)
const showAdhocDialog = ref(false)
const selectedOrder = ref<TransportOrder | null>(null)
const actionType = ref<'pickup' | 'dropoff'>('pickup')

const formattedDate = computed(() => {
  const d = new Date(selectedDate.value + 'T00:00:00')
  return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
})

const activeOrder = computed(() =>
  store.myAssignments.find((o) => o.status === 'picked_up')
)

const assignedOrders = computed(() =>
  store.myAssignments.filter((o) => o.status === 'assigned')
)

const completedOrders = computed(() =>
  store.myAssignments.filter((o) => o.status === 'completed')
)

function priorityColor(priority: string): string {
  const map: Record<string, string> = { urgent: 'red', high: 'orange', low: 'blue-grey' }
  return map[priority] || 'grey'
}

function formatLocation(type: string, id: number): string {
  const labels: Record<string, string> = { project: 'Project', yard: 'Yard', shop: 'Shop' }
  return `${labels[type] || type} #${id}`
}

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

function formatTimestamp(ts: string): string {
  if (!ts) return ''
  return new Date(ts).toLocaleString('en-US', {
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}

function onPickup(order: TransportOrder) {
  selectedOrder.value = order
  actionType.value = 'pickup'
  showActionDialog.value = true
}

function onDropoff(order: TransportOrder) {
  selectedOrder.value = order
  actionType.value = 'dropoff'
  showActionDialog.value = true
}

function onActionCompleted() {
  selectedOrder.value = null
  loadData()
}

async function loadData() {
  await store.fetchMyAssignments(selectedDate.value)
}

onMounted(loadData)
</script>

<style scoped>
.driver-page {
  max-width: 600px;
  margin: 0 auto;
}

.active-card {
  border-color: var(--q-warning);
  border-width: 2px;
}
</style>
