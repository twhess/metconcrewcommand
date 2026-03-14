<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row q-mb-md">
        <div class="col">
          <h4 class="q-ma-none">Email Configuration Test</h4>
          <p class="text-grey-7">Test your Postmark email connection and send test emails</p>
        </div>
      </div>

      <!-- Email Configuration Info -->
      <q-card v-if="emailConfig" class="q-mb-md" flat bordered>
        <q-card-section>
          <div class="text-h6">Current Configuration</div>
          <div class="text-caption text-grey-7">Email service configuration from .env</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <div class="text-caption text-grey-7">Mail Driver</div>
              <div class="text-body1">
                <q-chip
                  :color="emailConfig.mailer === 'postmark' ? 'positive' : 'warning'"
                  text-color="white"
                  size="sm"
                >
                  {{ emailConfig.mailer }}
                </q-chip>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="text-caption text-grey-7">From Address</div>
              <div class="text-body1">{{ emailConfig.from_address }}</div>
            </div>
            <div class="col-12 col-md-4">
              <div class="text-caption text-grey-7">From Name</div>
              <div class="text-body1">{{ emailConfig.from_name }}</div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Success Banner -->
      <q-banner v-if="showSuccess" class="bg-positive text-white q-mb-md" rounded>
        <template v-slot:avatar>
          <q-icon name="check_circle" color="white" />
        </template>
        <div class="text-h6">Email Sent Successfully!</div>
        <div class="text-body2">
          Test email sent to <strong>{{ lastSentEmail }}</strong> at {{ lastSentTime }}
        </div>
      </q-banner>

      <!-- Error Banner -->
      <q-banner v-if="errorMessage" class="bg-negative text-white q-mb-md" rounded>
        <template v-slot:avatar>
          <q-icon name="error" color="white" />
        </template>
        <div class="text-h6">Failed to Send Email</div>
        <div class="text-body2">{{ errorMessage }}</div>
      </q-banner>

      <!-- Test Email Form -->
      <q-card flat bordered>
        <q-card-section>
          <div class="text-h6">Send Test Email</div>
          <div class="text-caption text-grey-7">Enter recipient details below</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-form @submit="sendTestEmail" class="q-gutter-md">
            <!-- Recipient Email -->
            <q-input
              v-model="formData.to"
              type="email"
              label="Recipient Email *"
              hint="Enter the email address to send the test email to"
              filled
              required
              :rules="[
                val => !!val || 'Email is required',
                val => /.+@.+\..+/.test(val) || 'Please enter a valid email address'
              ]"
            >
              <template v-slot:prepend>
                <q-icon name="mail" />
              </template>
            </q-input>

            <!-- Subject -->
            <q-input
              v-model="formData.subject"
              type="text"
              label="Subject (Optional)"
              hint="Default: Test Email from MetCon Application"
              filled
            >
              <template v-slot:prepend>
                <q-icon name="subject" />
              </template>
            </q-input>

            <!-- Message -->
            <q-input
              v-model="formData.message"
              type="textarea"
              label="Message (Optional)"
              hint="Default test message will be used if left empty"
              filled
              rows="4"
            >
              <template v-slot:prepend>
                <q-icon name="message" />
              </template>
            </q-input>

            <!-- Submit Button -->
            <div class="row q-gutter-sm">
              <q-btn
                type="submit"
                color="primary"
                icon="send"
                label="Send Test Email"
                :loading="loading"
                :disable="loading"
              />
              <q-btn
                type="button"
                flat
                color="grey-7"
                label="Clear Form"
                @click="clearForm"
                :disable="loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>

      <!-- Instructions -->
      <q-card flat bordered class="q-mt-md">
        <q-card-section>
          <div class="text-h6">Setup Instructions</div>
          <div class="text-caption text-grey-7">How to configure Postmark</div>
        </q-card-section>
        <q-separator />
        <q-card-section>
          <ol class="q-pl-md">
            <li class="q-mb-sm">
              Sign up for a free Postmark account at
              <a href="https://postmarkapp.com" target="_blank" class="text-primary">postmarkapp.com</a>
            </li>
            <li class="q-mb-sm">
              Create a new server in your Postmark dashboard
            </li>
            <li class="q-mb-sm">
              Verify your sender signature (email address or domain)
            </li>
            <li class="q-mb-sm">
              Copy your Server API Token from the Postmark dashboard
            </li>
            <li class="q-mb-sm">
              Update your <code>.env</code> file in the backend:
              <pre class="q-mt-sm bg-grey-2 q-pa-sm">MAIL_MAILER=postmark
POSTMARK_TOKEN=your-server-token-here
MAIL_FROM_ADDRESS=verified@yourdomain.com
MAIL_FROM_NAME="MetCon Application"</pre>
            </li>
            <li class="q-mb-sm">
              Restart your Laravel server: <code>php artisan serve --port=8002</code>
            </li>
            <li>
              Use this page to test your email configuration
            </li>
          </ol>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type {
  EmailTestRequest,
  EmailTestResponse,
  EmailConfigResponse
} from '@/types'
import type { AxiosError } from 'axios'

const $q = useQuasar()

const formData = ref<EmailTestRequest>({
  to: '',
  subject: '',
  message: ''
})

const emailConfig = ref<EmailConfigResponse | null>(null)
const loading = ref(false)
const showSuccess = ref(false)
const errorMessage = ref('')
const lastSentEmail = ref('')
const lastSentTime = ref('')

const loadEmailConfig = async (): Promise<void> => {
  try {
    const response = await apiClient.get<EmailConfigResponse>('/email/config')
    emailConfig.value = response.data
  } catch (error) {
    console.error('Failed to load email config:', error)
    $q.notify({
      type: 'warning',
      message: 'Could not load email configuration',
      position: 'top'
    })
  }
}

const sendTestEmail = async (): Promise<void> => {
  loading.value = true
  showSuccess.value = false
  errorMessage.value = ''

  try {
    const response = await apiClient.post<EmailTestResponse>('/email/test', formData.value)

    if (response.data.success) {
      showSuccess.value = true
      lastSentEmail.value = formData.value.to
      lastSentTime.value = new Date().toLocaleTimeString()

      $q.notify({
        type: 'positive',
        message: 'Test email sent successfully!',
        position: 'top'
      })

      clearForm()
    } else {
      errorMessage.value = response.data.message || 'Unknown error occurred'
    }
  } catch (error) {
    const axiosError = error as AxiosError<{ message: string; error?: string }>
    errorMessage.value = axiosError.response?.data?.error
      || axiosError.response?.data?.message
      || 'Failed to send test email. Please check your Postmark configuration.'

    $q.notify({
      type: 'negative',
      message: 'Failed to send test email',
      caption: errorMessage.value,
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

const clearForm = (): void => {
  formData.value = {
    to: '',
    subject: '',
    message: ''
  }
}

onMounted(() => {
  loadEmailConfig()
})
</script>

<style scoped>
pre {
  border-radius: 4px;
  font-size: 12px;
  overflow-x: auto;
}

code {
  background-color: #f5f5f5;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.9em;
}
</style>
