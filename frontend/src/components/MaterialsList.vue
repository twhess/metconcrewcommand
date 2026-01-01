<template>
  <div class="materials-list">
    <div class="list-header">
      <h3>Quick Add Materials</h3>
    </div>

    <div class="materials">
      <div
        v-for="material in commonMaterials"
        :key="material.type"
        class="material-item"
        @click="selectMaterial(material)"
      >
        <div class="material-icon">{{ material.icon }}</div>
        <div class="material-info">
          <div class="material-name">{{ material.name }}</div>
          <div class="material-desc">{{ material.description }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const commonMaterials = [
  {
    type: 'concrete',
    name: 'Concrete',
    icon: '🏗️',
    description: 'Ready-mix concrete delivery',
  },
  {
    type: 'gravel',
    name: 'Gravel',
    icon: '🪨',
    description: 'Gravel and aggregate',
  },
  {
    type: 'other',
    name: 'Other Materials',
    icon: '📦',
    description: 'Custom materials',
  },
]

function selectMaterial(material: any) {
  console.log('Material selected:', material)
  // This will be used when adding materials to a schedule
}
</script>

<style scoped>
.materials-list {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.list-header {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.list-header h3 {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.materials {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0;
}

.material-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f3f4f6;
}

.material-item:hover {
  background: #f9fafb;
}

.material-icon {
  font-size: 2rem;
  margin-right: 1rem;
}

.material-info {
  flex: 1;
}

.material-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.material-desc {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
