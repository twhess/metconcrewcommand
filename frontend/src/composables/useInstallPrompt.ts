import { ref, onMounted, onUnmounted, computed } from 'vue'

interface BeforeInstallPromptEvent extends Event {
  prompt(): Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

export function useInstallPrompt() {
  const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null)
  const isInstalled = ref(false)

  // Detect iOS Safari (not in standalone mode)
  const isIOS = computed(() => {
    const ua = navigator.userAgent
    return /iPhone|iPad|iPod/.test(ua) && !('standalone' in navigator && (navigator as { standalone: boolean }).standalone)
  })

  // True when the native install prompt is available (Android/Chrome)
  const canInstall = computed(() => deferredPrompt.value !== null)

  // True when we should show iOS install instructions
  const showIOSInstall = computed(() => {
    return isIOS.value && !isInstalled.value
  })

  function handleBeforeInstallPrompt(event: Event) {
    event.preventDefault()
    deferredPrompt.value = event as BeforeInstallPromptEvent
  }

  function handleAppInstalled() {
    isInstalled.value = true
    deferredPrompt.value = null
  }

  async function promptInstall(): Promise<void> {
    if (!deferredPrompt.value) return

    await deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice

    if (outcome === 'accepted') {
      isInstalled.value = true
    }

    deferredPrompt.value = null
  }

  onMounted(() => {
    // Check if already running as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
      isInstalled.value = true
    }

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.addEventListener('appinstalled', handleAppInstalled)
  })

  onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.removeEventListener('appinstalled', handleAppInstalled)
  })

  return {
    canInstall,
    isIOS,
    showIOSInstall,
    isInstalled,
    promptInstall
  }
}
