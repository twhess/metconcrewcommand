<template>
  <div class="equipment-list">
    <div class="list-header">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="Search equipment..."
        class="search-input"
      />
    </div>

    <div v-if="loading" class="loading">Loading equipment...</div>

    <div v-else class="equipment">
      <div class="section">
        <div class="section-title">Available ({{ availableEquipment.length }})</div>
        <div v-if="availableEquipment.length === 0" class="empty-section">
          No available equipment
        </div>
        <div
          v-for="item in availableEquipment"
          :key="item.id"
          class="equipment-item available"
        >
          <div class="equipment-name">{{ item.name }}</div>
          <div class="equipment-meta">
            <span v-if="item.equipment_number">#{{ item.equipment_number }}</span>
            <span v-if="item.category">{{ item.category }}</span>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">Scheduled ({{ scheduledEquipment.length }})</div>
        <div
          v-for="item in scheduledEquipment"
          :key="item.id"
          class="equipment-item scheduled"
        >
          <div class="equipment-name">{{ item.name }}</div>
          <div class="equipment-meta">On schedule</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useEquipmentStore } from '@/stores/equipment'
import { useScheduleStore } from '@/stores/schedule'
import type { Equipment } from '@/types'

const props = defineProps<{
  selectedDate: string
}>()

const equipmentStore = useEquipmentStore()
const scheduleStore = useScheduleStore()

const searchQuery = ref('')
const loading = ref(false)
const availableItems = ref<Equipment[]>([])

const filteredEquipment = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return equipmentStore.activeEquipment.filter(e =>
    e.name.toLowerCase().includes(query) ||
    e.equipment_number?.toLowerCase().includes(query) ||
    e.category?.toLowerCase().includes(query)
  )
})

const scheduledEquipmentIds = computed(() => {
  const ids = new Set<number>()
  scheduleStore.schedulesForSelectedDate.forEach(schedule => {
    schedule.equipment_assignments?.forEach(assignment => {
      ids.add(assignment.equipment_id)
    })
  })
  return ids
})

const availableEquipment = computed(() =>
  filteredEquipment.value.filter(e => !scheduledEquipmentIds.value.has(e.id))
)

const scheduledEquipment = computed(() =>
  filteredEquipment.value.filter(e => scheduledEquipmentIds.value.has(e.id))
)

onMounted(async () => {
  loading.value = true
  try {
    availableItems.value = await equipmentStore.fetchAvailableEquipment(props.selectedDate)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.equipment-list {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.list-header {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.loading {
  padding: 2rem 1rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.equipment {
  flex: 1;
  overflow-y: auto;
}

.section {
  border-bottom: 1px solid #e5e7eb;
  padding: 0.75rem 0;
}

.section-title {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  background: #f9fafb;
}

.empty-section {
  padding: 1rem;
  text-align: center;
  color: #9ca3af;
  font-size: 0.875rem;
}

.equipment-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.2s;
}

.equipment-item:hover {
  background: #f9fafb;
}

.equipment-item.available {
  border-left: 3px solid #10b981;
}

.equipment-item.scheduled {
  border-left: 3px solid #f59e0b;
  opacity: 0.6;
  cursor: not-allowed;
}

.equipment-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.equipment-meta {
  font-size: 0.75rem;
  color: #6b7280;
  display: flex;
  gap: 0.5rem;
}
</style>
