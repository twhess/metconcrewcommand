<template>
  <q-page class="flex flex-center">
    <q-card style="width: 400px; max-width: 90vw">
      <q-card-section>
        <div class="text-h6 text-center">Login</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="handleLogin">
          <q-input
            v-model="email"
            label="Email"
            type="email"
            outlined
            :rules="[val => !!val || 'Email is required']"
            class="q-mb-md"
          />

          <q-input
            v-model="password"
            label="Password"
            type="password"
            outlined
            :rules="[val => !!val || 'Password is required']"
            class="q-mb-md"
          />

          <q-btn
            type="submit"
            color="primary"
            label="Login"
            class="full-width"
            :loading="loading"
          />
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'
import type { AuthResponse } from '@/types'
import type { AxiosError } from 'axios'

const router = useRouter()
const $q = useQuasar()
const authStore = useAuthStore()

const email = ref<string>('')
const password = ref<string>('')
const loading = ref<boolean>(false)

const handleLogin = async (): Promise<void> => {
  loading.value = true

  try {
    const response = await apiClient.post<AuthResponse>('/auth/login', {
      email: email.value,
      password: password.value
    })

    authStore.setAuth(response.data.token, response.data.user)

    $q.notify({
      type: 'positive',
      message: 'Login successful!'
    })

    router.push('/dashboard')
  } catch (error) {
    const axiosError = error as AxiosError<{ message?: string }>
    $q.notify({
      type: 'negative',
      message: axiosError.response?.data?.message || 'Login failed. Please check your credentials.'
    })
  } finally {
    loading.value = false
  }
}
</script>
