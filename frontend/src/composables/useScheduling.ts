import { computed, ref, type Ref } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useUserStore } from '@/stores/user'
import { useEquipmentStore } from '@/stores/equipment'
import type { User, Equipment } from '@/types'

export function useScheduling(date: Ref<string>) {
  const scheduleStore = useScheduleStore()
  const userStore = useUserStore()
  const equipmentStore = useEquipmentStore()

  const availableEmployees = ref<User[]>([])
  const availableEquipment = ref<Equipment[]>([])
  const loading = ref(false)

  const schedulesForDate = computed(() =>
    scheduleStore.schedules.filter(s => s.date === date.value)
  )

  async function loadAvailableResources() {
    loading.value = true
    try {
      const [employees, equipment] = await Promise.all([
        userStore.fetchAvailableUsers(date.value),
        equipmentStore.fetchAvailableEquipment(date.value),
      ])
      availableEmployees.value = employees
      availableEquipment.value = equipment
    } finally {
      loading.value = false
    }
  }

  async function addSchedule(projectId: number, startTime: string) {
    return await scheduleStore.createSchedule({
      project_id: projectId,
      date: date.value,
      start_time: startTime,
      status: 'scheduled',
    })
  }

  async function assignCrewMember(scheduleId: number, userId: number, isForeman: boolean = false) {
    const foremanId = isForeman ? userId : undefined
    return await scheduleStore.assignCrew(scheduleId, [userId], foremanId)
  }

  async function assignEquipmentToSchedule(scheduleId: number, equipmentId: number) {
    return await scheduleStore.assignEquipment(scheduleId, [equipmentId])
  }

  function isEmployeeAvailable(userId: number): boolean {
    return availableEmployees.value.some(e => e.id === userId)
  }

  function isEquipmentAvailable(equipmentId: number): boolean {
    return availableEquipment.value.some(e => e.id === equipmentId)
  }

  return {
    schedulesForDate,
    availableEmployees,
    availableEquipment,
    loading,
    loadAvailableResources,
    addSchedule,
    assignCrewMember,
    assignEquipmentToSchedule,
    isEmployeeAvailable,
    isEquipmentAvailable,
  }
}
