<template>
  <div class="project-list">
    <div class="list-header">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="Search projects..."
        class="search-input"
      />
    </div>

    <div v-if="loading" class="loading">Loading projects...</div>

    <div v-else-if="filteredProjects.length === 0" class="empty">
      No active projects found
    </div>

    <div v-else class="projects">
      <div
        v-for="project in filteredProjects"
        :key="project.id"
        class="project-item"
        draggable="true"
        @dragstart="onDragStart(project)"
        @click="addScheduleForProject(project)"
      >
        <div class="project-name">{{ project.name }}</div>
        <div class="project-meta">
          <span v-if="project.project_number" class="project-number">
            #{{ project.project_number }}
          </span>
          <span v-if="project.company" class="company-name">
            {{ project.company.name }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useProjectStore } from '@/stores/project'
import { useScheduleStore } from '@/stores/schedule'
import type { Project } from '@/types'

const props = defineProps<{
  selectedDate: string
}>()

const projectStore = useProjectStore()
const scheduleStore = useScheduleStore()

const searchQuery = ref('')
const loading = computed(() => projectStore.loading)

const filteredProjects = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return projectStore.activeProjects.filter(p =>
    p.name.toLowerCase().includes(query) ||
    p.project_number?.toLowerCase().includes(query) ||
    p.company?.name.toLowerCase().includes(query)
  )
})

function onDragStart(project: Project) {
  // Store project data for drop event
  const event = window.event as DragEvent
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'copy'
    event.dataTransfer.setData('project', JSON.stringify(project))
  }
}

async function addScheduleForProject(project: Project) {
  // Quick add: create schedule at 7:00 AM
  try {
    await scheduleStore.createSchedule({
      project_id: project.id,
      date: props.selectedDate,
      start_time: '07:00',
      status: 'scheduled',
    })
  } catch (error) {
    console.error('Failed to create schedule:', error)
  }
}
</script>

<style scoped>
.project-list {
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

.loading,
.empty {
  padding: 2rem 1rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.projects {
  flex: 1;
  overflow-y: auto;
}

.project-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  cursor: pointer;
  transition: background 0.2s;
}

.project-item:hover {
  background: #f3f4f6;
}

.project-item:active {
  background: #e5e7eb;
}

.project-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.25rem;
}

.project-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.project-number {
  font-family: monospace;
}

.company-name {
  font-style: italic;
}
</style>
