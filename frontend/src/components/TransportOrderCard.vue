<template>
  <q-card bordered flat>
    <q-card-section>
      <!-- Header: Equipment + Priority + Status -->
      <div class="row items-center q-mb-sm">
        <div class="col">
          <div class="text-subtitle1 text-weight-bold">
            {{ order.equipment?.name || 'Equipment #' + order.equipment_id }}
          </div>
          <div class="text-caption text-grey-7">{{ order.order_number }}</div>
        </div>
        <div class="col-auto q-gutter-x-xs">
          <q-badge v-if="order.is_adhoc" color="orange" label="Ad-Hoc" />
          <q-badge
            v-if="order.priority !== 'normal'"
            :color="priorityColor"
            :label="order.priority.toUpperCase()"
          />
          <q-badge :color="statusColor" :label="statusLabel" />
        </div>
      </div>

      <q-separator class="q-my-sm" />

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

      <!-- Driver/Vehicle (if assigned) -->
      <div v-if="order.assigned_driver" class="text-body2 q-mb-xs">
        <q-icon name="person" size="xs" class="q-mr-xs" />
        {{ order.assigned_driver.name }}
        <span v-if="order.assigned_vehicle" class="text-grey-7">
          &mdash; {{ order.assigned_vehicle.name }}
        </span>
      </div>

      <!-- Special Instructions -->
      <div v-if="order.special_instructions" class="text-caption text-grey-7 q-mt-sm">
        <q-icon name="info" size="xs" class="q-mr-xs" />
        {{ order.special_instructions }}
      </div>

      <!-- Timestamps for picked_up / completed -->
      <div v-if="order.picked_up_at" class="text-caption text-grey-6 q-mt-xs">
        Picked up: {{ formatTimestamp(order.picked_up_at) }}
      </div>
      <div v-if="order.completed_at" class="text-caption text-grey-6">
        Delivered: {{ formatTimestamp(order.completed_at) }}
      </div>
    </q-card-section>

    <!-- Actions -->
    <q-card-actions v-if="showActions" align="right">
      <!-- Dispatch actions -->
      <template v-if="mode === 'dispatch'">
        <q-btn
          v-if="order.status === 'requested'"
          flat dense
          color="primary"
          label="Assign Driver"
          icon="person_add"
          @click="$emit('assign', order)"
        />
        <q-btn
          v-if="['requested', 'assigned'].includes(order.status)"
          flat dense
          color="grey-7"
          label="Edit"
          icon="edit"
          @click="$emit('edit', order)"
        />
        <q-btn
          v-if="!['completed', 'cancelled'].includes(order.status) && order.status !== 'picked_up'"
          flat dense
          color="negative"
          label="Cancel"
          icon="cancel"
          @click="$emit('cancel', order)"
        />
      </template>

      <!-- Driver actions -->
      <template v-if="mode === 'driver'">
        <q-btn
          v-if="order.status === 'assigned'"
          color="primary"
          label="PICK UP"
          icon="upload"
          size="lg"
          padding="sm xl"
          @click="$emit('pickup', order)"
        />
        <q-btn
          v-if="order.status === 'picked_up'"
          color="positive"
          label="DROP OFF"
          icon="download"
          size="lg"
          padding="sm xl"
          @click="$emit('dropoff', order)"
        />
      </template>
    </q-card-actions>
  </q-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { TransportOrder } from '@/types'

interface Props {
  order: TransportOrder
  mode?: 'dispatch' | 'driver'
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'dispatch',
  showActions: true,
})

defineEmits<{
  (e: 'assign', order: TransportOrder): void
  (e: 'edit', order: TransportOrder): void
  (e: 'cancel', order: TransportOrder): void
  (e: 'pickup', order: TransportOrder): void
  (e: 'dropoff', order: TransportOrder): void
}>()

const statusColor = computed(() => {
  const map: Record<string, string> = {
    requested: 'blue-grey',
    assigned: 'info',
    picked_up: 'warning',
    completed: 'positive',
    cancelled: 'grey',
  }
  return map[props.order.status] || 'grey'
})

const statusLabel = computed(() => {
  const map: Record<string, string> = {
    requested: 'Requested',
    assigned: 'Assigned',
    picked_up: 'In Transit',
    completed: 'Completed',
    cancelled: 'Cancelled',
  }
  return map[props.order.status] || props.order.status
})

const priorityColor = computed(() => {
  const map: Record<string, string> = {
    urgent: 'red',
    high: 'orange',
    low: 'blue-grey',
  }
  return map[props.order.priority] || 'grey'
})

function formatLocation(type: string, id: number): string {
  // In a real app, you'd resolve names from stores. For now, show type + ID.
  const typeLabels: Record<string, string> = {
    project: 'Project',
    yard: 'Yard',
    shop: 'Shop',
  }
  return `${typeLabels[type] || type} #${id}`
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
</script>
