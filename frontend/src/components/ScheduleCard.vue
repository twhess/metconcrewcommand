<template>
  <div class="schedule-card">
    <div class="card-header">
      <div class="project-info">
        <h3>{{ schedule.project?.name || 'Unknown Project' }}</h3>
        <span class="time">{{ formatTime(schedule.start_time) }}</span>
      </div>
      <div class="card-actions">
        <span :class="['status-badge', `status-${schedule.status}`]">
          {{ schedule.status }}
        </span>
        <button
          v-if="can('schedules.update')"
          @click="$emit('edit', schedule)"
          class="btn-icon"
          title="Edit"
        >
          ✏️
        </button>
        <button
          v-if="can('schedules.delete')"
          @click="$emit('delete', schedule.id)"
          class="btn-icon"
          title="Delete"
        >
          🗑️
        </button>
      </div>
    </div>

    <div class="card-body">
      <!-- Crew Section -->
      <div v-if="schedule.crew_assignments && schedule.crew_assignments.length > 0" class="section">
        <div class="section-title">Crew ({{ schedule.crew_assignments.length }})</div>
        <div class="crew-list">
          <div
            v-for="assignment in schedule.crew_assignments"
            :key="assignment.id"
            class="crew-member"
          >
            <span>{{ assignment.user?.name || 'Unknown' }}</span>
            <span v-if="assignment.is_foreman" class="badge">Foreman</span>
          </div>
        </div>
      </div>

      <!-- Equipment Section -->
      <div v-if="schedule.equipment_assignments && schedule.equipment_assignments.length > 0" class="section">
        <div class="section-title">Equipment ({{ schedule.equipment_assignments.length }})</div>
        <div class="equipment-list">
          <div
            v-for="assignment in schedule.equipment_assignments"
            :key="assignment.id"
            class="equipment-item"
          >
            {{ assignment.equipment?.name || 'Unknown' }}
          </div>
        </div>
      </div>

      <!-- Materials Section -->
      <div v-if="schedule.materials && schedule.materials.length > 0" class="section">
        <div class="section-title">Materials ({{ schedule.materials.length }})</div>
        <div class="materials-list">
          <div
            v-for="material in schedule.materials"
            :key="material.id"
            class="material-item"
          >
            {{ material.type }} {{ material.quantity ? `(${material.quantity} ${material.unit})` : '' }}
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="schedule.dispatch_instructions" class="section">
        <div class="section-title">Dispatch Instructions</div>
        <p class="notes">{{ schedule.dispatch_instructions }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions'
import { useDateFormatting } from '@/composables/useDateFormatting'
import type { Schedule } from '@/types'

defineProps<{
  schedule: Schedule
}>()

defineEmits<{
  edit: [schedule: Schedule]
  delete: [scheduleId: number]
}>()

const { can } = usePermissions()
const { formatTime } = useDateFormatting()
</script>

<style scoped>
.schedule-card {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: white;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.schedule-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.project-info h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

.time {
  display: block;
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-scheduled {
  background: #dbeafe;
  color: #1e40af;
}

.status-in_progress {
  background: #fef3c7;
  color: #92400e;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-icon:hover {
  opacity: 1;
}

.card-body {
  padding: 1rem;
}

.section {
  margin-bottom: 1rem;
}

.section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.crew-list,
.equipment-list,
.materials-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.crew-member,
.equipment-item,
.material-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.25rem 0;
  font-size: 0.875rem;
  color: #374151;
}

.badge {
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  background: #fbbf24;
  color: #78350f;
}

.notes {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
}
</style>
