import { ref, computed, onMounted, type Ref } from 'vue'

interface Pos { x: number; y: number }

export function useDraggable(storageKey: string, panelRef: Ref<HTMLElement | null>) {
  const pos = ref<Pos | null>(null)

  // Restore saved position on mount — clamp to current viewport so
  // a stale position from a different screen/session never hides the panel.
  onMounted(() => {
    const raw = localStorage.getItem(storageKey)
    if (raw) {
      try {
        const saved = JSON.parse(raw) as Pos
        const el = panelRef.value
        const w  = el ? el.offsetWidth  : 300
        const h  = el ? el.offsetHeight : 200
        const x  = Math.max(0, Math.min(window.innerWidth  - w, saved.x))
        const y  = Math.max(0, Math.min(window.innerHeight - h, saved.y))
        // If the clamped position differs significantly, discard it (off-screen save)
        if (Math.abs(x - saved.x) > 40 || Math.abs(y - saved.y) > 40) {
          localStorage.removeItem(storageKey)
        } else {
          pos.value = { x, y }
        }
      } catch {
        localStorage.removeItem(storageKey)
      }
    }
  })

  // When a saved position exists, override the CSS bottom/right with top/left
  const dragStyle = computed(() => {
    if (!pos.value) return {}
    return {
      left:   pos.value.x + 'px',
      top:    pos.value.y + 'px',
      right:  'auto',
      bottom: 'auto',
    }
  })

  let startClientX = 0
  let startClientY = 0
  let startPosX    = 0
  let startPosY    = 0

  function onHandlePointerDown(e: PointerEvent) {
    // Only primary button / first touch
    if (e.pointerType === 'mouse' && e.button !== 0) return
    e.preventDefault()

    const el = panelRef.value
    if (!el) return

    const rect  = el.getBoundingClientRect()
    startPosX   = rect.left
    startPosY   = rect.top
    startClientX = e.clientX
    startClientY = e.clientY

    // Immediately anchor so the panel jumps to absolute top/left coords
    pos.value = { x: rect.left, y: rect.top }

    ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
    ;(e.currentTarget as HTMLElement).addEventListener('pointermove', onPointerMove)
    ;(e.currentTarget as HTMLElement).addEventListener('pointerup',   onPointerUp)
    ;(e.currentTarget as HTMLElement).addEventListener('pointercancel', onPointerUp)
  }

  function onPointerMove(e: PointerEvent) {
    const dx = e.clientX - startClientX
    const dy = e.clientY - startClientY

    const el = panelRef.value
    const w  = el ? el.offsetWidth  : 200
    const h  = el ? el.offsetHeight : 100

    pos.value = {
      x: Math.max(0, Math.min(window.innerWidth  - w, startPosX + dx)),
      y: Math.max(0, Math.min(window.innerHeight - h, startPosY + dy)),
    }
  }

  function onPointerUp(e: PointerEvent) {
    if (pos.value) {
      localStorage.setItem(storageKey, JSON.stringify(pos.value))
    }
    ;(e.currentTarget as HTMLElement).removeEventListener('pointermove', onPointerMove)
    ;(e.currentTarget as HTMLElement).removeEventListener('pointerup',   onPointerUp)
    ;(e.currentTarget as HTMLElement).removeEventListener('pointercancel', onPointerUp)
  }

  function resetPosition() {
    pos.value = null
    localStorage.removeItem(storageKey)
  }

  return { dragStyle, onHandlePointerDown, resetPosition }
}
