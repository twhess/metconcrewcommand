export function useDateFormatting() {
  function formatDate(dateString: string, format: 'short' | 'long' | 'time' = 'short'): string {
    const date = new Date(dateString)

    if (format === 'short') {
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      })
    }

    if (format === 'long') {
      return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    }

    if (format === 'time') {
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
      })
    }

    return dateString
  }

  function formatTime(timeString: string): string {
    // Convert HH:MM:SS to HH:MM AM/PM
    const [hours, minutes] = timeString.split(':')
    const hour = parseInt(hours)
    const ampm = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour % 12 || 12
    return `${displayHour}:${minutes} ${ampm}`
  }

  function getTodayString(): string {
    return new Date().toISOString().split('T')[0]
  }

  function addDays(dateString: string, days: number): string {
    const date = new Date(dateString)
    date.setDate(date.getDate() + days)
    return date.toISOString().split('T')[0]
  }

  function getDayOfWeek(dateString: string): string {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { weekday: 'long' })
  }

  function isToday(dateString: string): boolean {
    return dateString === getTodayString()
  }

  function isPast(dateString: string): boolean {
    return new Date(dateString) < new Date(getTodayString())
  }

  function isFuture(dateString: string): boolean {
    return new Date(dateString) > new Date(getTodayString())
  }

  return {
    formatDate,
    formatTime,
    getTodayString,
    addDays,
    getDayOfWeek,
    isToday,
    isPast,
    isFuture,
  }
}
