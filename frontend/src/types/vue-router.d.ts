import 'vue-router'
import type { RouteMeta } from './index'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    requiresGuest?: boolean
  }
}
