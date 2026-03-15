<template>
  <q-page class="q-pa-md">
    <!-- Header -->
    <div class="row q-mb-md items-center">
      <div class="col">
        <h4 class="q-my-none">Dispatch Board</h4>
      </div>
      <div class="col-auto row q-gutter-sm items-center">
        <q-input
          v-model="selectedDate"
          outlined dense
          type="date"
          @update:model-value="loadData"
        />
        <q-btn
          color="primary"
          label="New Order"
          icon="add"
          @click="showFormDialog = true"
        />
        <q-btn
          flat round
          icon="refresh"
          @click="loadData"
          :loading="store.loading"
        />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-6 col-md-3">
        <q-card flat bordered>
          <q-card-section class="text-center">
            <div class="text-h4 text-blue-grey">{{ summary.requested }}</div>
            <div class="text-caption">Needs Assignment</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered>
          <q-card-section class="text-center">
            <div class="text-h4 text-info">{{ summary.assigned }}</div>
            <div class="text-caption">Needs Pickup</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered>
          <q-card-section class="text-center">
            <div class="text-h4 text-warning">{{ summary.picked_up }}</div>
            <div class="text-caption">In Transit</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-md-3">
        <q-card flat bordered>
          <q-card-section class="text-center">
            <div class="text-h4 text-positive">{{ summary.completed }}</div>
            <div class="text-caption">Completed Today</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Tabs -->
    <q-tabs v-model="activeTab" dense class="text-grey" active-color="primary" indicator-color="primary" align="left">
      <q-tab name="action" label="Needs Action" />
      <q-tab name="transit" label="In Transit" />
      <q-tab name="completed" label="Completed" />
      <q-tab name="all" label="All Orders" />
    </q-tabs>

    <q-separator />

    <!-- Loading -->
    <div v-if="store.loading" class="text-center q-pa-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Tab panels -->
    <q-tab-panels v-else v-model="activeTab" animated>
      <!-- Needs Action -->
      <q-tab-panel name="action">
        <div v-if="needsActionOrders.length === 0" class="text-center q-pa-lg text-grey-7">
          No orders need action
        </div>
        <div class="row q-col-gutter-md">
          <div v-for="order in needsActionOrders" :key="order.id" class="col-12 col-md-6 col-lg-4">
            <TransportOrderCard
              :order="order"
              mode="dispatch"
              @assign="onAssign"
              @edit="onEdit"
              @cancel="onCancel"
            />
          </div>
        </div>
      </q-tab-panel>

      <!-- In Transit -->
      <q-tab-panel name="transit">
        <div v-if="store.inTransitOrders.length === 0" class="text-center q-pa-lg text-grey-7">
          Nothing in transit
        </div>
        <div class="row q-col-gutter-md">
          <div v-for="order in store.inTransitOrders" :key="order.id" class="col-12 col-md-6 col-lg-4">
            <TransportOrderCard :order="order" mode="dispatch" :show-actions="false" />
          </div>
        </div>
      </q-tab-panel>

      <!-- Completed -->
      <q-tab-panel name="completed">
        <div v-if="store.completedOrders.length === 0" class="text-center q-pa-lg text-grey-7">
          No completed orders today
        </div>
        <div class="row q-col-gutter-md">
          <div v-for="order in store.completedOrders" :key="order.id" class="col-12 col-md-6 col-lg-4">
            <TransportOrderCard :order="order" mode="dispatch" :show-actions="false" />
          </div>
        </div>
      </q-tab-panel>

      <!-- All Orders -->
      <q-tab-panel name="all">
        <q-table
          :rows="store.orders"
          :columns="columns"
          row-key="id"
          flat bordered
          :pagination="{ rowsPerPage: 25 }"
        >
          <template v-slot:body-cell-status="cellProps">
            <q-td :props="cellProps">
              <q-badge :color="statusColor(cellProps.row.status)" :label="cellProps.row.status" />
            </q-td>
          </template>
          <template v-slot:body-cell-priority="cellProps">
            <q-td :props="cellProps">
              <q-badge
                v-if="cellProps.row.priority !== 'normal'"
                :color="priorityColor(cellProps.row.priority)"
                :label="cellProps.row.priority"
              />
              <span v-else>normal</span>
            </q-td>
          </template>
        </q-table>
      </q-tab-panel>
    </q-tab-panels>

    <!-- Dialogs -->
    <TransportOrderFormDialog
      v-model="showFormDialog"
      :order="selectedOrder"
      @saved="onSaved"
    />

    <AssignDriverDialog
      v-model="showAssignDialog"
      :order="selectedOrder"
      @assigned="onSaved"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useTransportOrderStore } from '@/stores/transportOrder'
import type { TransportOrder } from '@/types'
import TransportOrderCard from '@/components/TransportOrderCard.vue'
import TransportOrderFormDialog from '@/components/TransportOrderFormDialog.vue'
import AssignDriverDialog from '@/components/AssignDriverDialog.vue'

const $q = useQuasar()
const store = useTransportOrderStore()

const selectedDate = ref(new Date().toISOString().split('T')[0])
const activeTab = ref('action')
const showFormDialog = ref(false)
const showAssignDialog = ref(false)
const selectedOrder = ref<TransportOrder | null>(null)

const summary = computed(() => store.dispatchSummary?.counts || {
  requested: 0, assigned: 0, picked_up: 0, completed: 0, cancelled: 0,
})

const needsActionOrders = computed(() =>
  store.orders.filter((o) => ['requested', 'assigned'].includes(o.status))
)

const columns = [
  { name: 'order_number', label: 'Order #', field: 'order_number', align: 'left' as const, sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center' as const, sortable: true },
  { name: 'priority', label: 'Priority', field: 'priority', align: 'center' as const, sortable: true },
  { name: 'equipment', label: 'Equipment', field: (row: TransportOrder) => row.equipment?.name || '', align: 'left' as const, sortable: true },
  { name: 'driver', label: 'Driver', field: (row: TransportOrder) => row.assigned_driver?.name || '-', align: 'left' as const },
  { name: 'scheduled_date', label: 'Date', field: 'scheduled_date', align: 'left' as const, sortable: true },
]

function statusColor(status: string): string {
  const map: Record<string, string> = {
    requested: 'blue-grey', assigned: 'info', picked_up: 'warning', completed: 'positive', cancelled: 'grey',
  }
  return map[status] || 'grey'
}

function priorityColor(priority: string): string {
  const map: Record<string, string> = { urgent: 'red', high: 'orange', low: 'blue-grey' }
  return map[priority] || 'grey'
}

function onAssign(order: TransportOrder) {
  selectedOrder.value = order
  showAssignDialog.value = true
}

function onEdit(order: TransportOrder) {
  selectedOrder.value = order
  showFormDialog.value = true
}

function onCancel(order: TransportOrder) {
  $q.dialog({
    title: 'Cancel Order',
    message: `Cancel transport order ${order.order_number}?`,
    prompt: { model: '', type: 'textarea', label: 'Reason for cancellation' },
    cancel: true,
    persistent: true,
  }).onOk(async (reason: string) => {
    try {
      await store.cancelOrder(order.id, reason || 'No reason provided')
      $q.notify({ type: 'positive', message: 'Order cancelled' })
      loadData()
    } catch (e: any) {
      $q.notify({ type: 'negative', message: e?.response?.data?.message || 'Failed to cancel' })
    }
  })
}

function onSaved() {
  selectedOrder.value = null
  loadData()
}

async function loadData() {
  await Promise.all([
    store.fetchOrders({ date: selectedDate.value }),
    store.fetchDispatchSummary(selectedDate.value),
  ])
}

onMounted(loadData)
</script>

<style scoped>
.q-page {
  max-width: 1400px;
  margin: 0 auto;
}
</style>
