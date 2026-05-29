const KEY = 'pkw_uuid'

/** Returns a persistent UUID for this browser/device. Created on first call. */
export function getOrCreateUUID(): string {
  let id = localStorage.getItem(KEY)
  if (!id) {
    id = crypto.randomUUID()
    localStorage.setItem(KEY, id)
  }
  return id
}

/** True if this browser has never connected before (no UUID stored yet). */
export function isFirstSession(): boolean {
  return !localStorage.getItem(KEY)
}
