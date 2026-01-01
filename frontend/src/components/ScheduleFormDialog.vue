<template>
  <div class="dialog-overlay" @click.self="$emit('close')">
    <div class="dialog">
      <div class="dialog-header">
        <h2>{{ schedule ? 'Edit Schedule' : 'New Schedule' }}</h2>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="dialog-body">
        <div class="form-group">
          <label for="project">Project *</label>
          <select
            id="project"
            v-model="form.project_id"
            required
            class="form-control"
          >
            <option value="">Select a project...</option>
            <option
              v-for="project in activeProjects"
              :key="project.id"
              :value="project.id"
            >
              {{ project.name }}
            </option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="date">Date *</label>
            <input
              id="date"
              type="date"
              v-model="form.date"
              required
              class="form-control"
            />
          </div>

          <div class="form-group">
            <label for="start_time">Start Time *</label>
            <input
              id="start_time"
              type="time"
              v-model="form.start_time"
              required
              class="form-control"
            />
          </div>

          <div class="form-group">
            <label for="end_time">End Time</label>
            <input
              id="end_time"
              type="time"
              v-model="form.end_time"
              class="form-control"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select
            id="status"
            v-model="form.status"
            class="form-control"
          >
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div class="form-group">
          <label for="dispatch_instructions">Dispatch Instructions</label>
          <textarea
            id="dispatch_instructions"
            v-model="form.dispatch_instructions"
            rows="3"
            class="form-control"
            placeholder="Special instructions for this schedule..."
          ></textarea>
        </div>

        <div class="form-group">
          <label for="notes">Notes</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="2"
            class="form-control"
            placeholder="Additional notes..."
          ></textarea>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <div class="dialog-footer">
          <button type="button" @click="$emit('close')" class="btn btn-secondary">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Saving...' : schedule ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useProjectStore } from '@/stores/project'
import { useScheduleStore } from '@/stores/schedule'
import type { Schedule } from '@/types'

const props = defineProps<{
  date: string
  schedule?: Schedule
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const projectStore = useProjectStore()
const scheduleStore = useScheduleStore()

const loading = ref(false)
const error = ref<string | null>(null)

const form = reactive({
  project_id: props.schedule?.project_id || '',
  date: props.schedule?.date || props.date,
  start_time: props.schedule?.start_time || '07:00',
  end_time: props.schedule?.end_time || '',
  status: props.schedule?.status || 'scheduled',
  dispatch_instructions: props.schedule?.dispatch_instructions || '',
  notes: props.schedule?.notes || '',
})

const activeProjects = computed(() => projectStore.activeProjects)

async function handleSubmit() {
  loading.value = true
  error.value = null

  try {
    if (props.schedule) {
      await scheduleStore.updateSchedule(props.schedule.id, form)
    } else {
      await scheduleStore.createSchedule(form)
    }
    emit('saved')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to save schedule'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (activeProjects.value.length === 0) {
    await projectStore.fetchProjects()
  }
})
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.dialog-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dialog-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.dialog-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1rem;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.form-control:focus {
  outline: none;
  border-color: #2563eb;
  ring: 2px;
  ring-color: #bfdbfe;
}

textarea.form-control {
  resize: vertical;
}

.error-message {
  padding: 0.75rem;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 0.375rem;
  color: #dc2626;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.dialog-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  background: white;
  color: #374151;
}

.btn-secondary:hover {
  background: #f3f4f6;
}
</style>
