<template>
  <div
    class="joy-base"
    :style="joyBaseStyle"
    @pointerdown="onDown"
    @pointermove="onMove"
    @pointerup="onUp"
    @pointercancel="onUp"
  >
    <!-- Drag handle — top-right corner, separate from joystick touch area -->
    <div
      class="joy-handle"
      title="Drag to move"
      @pointerdown.stop="onHandleDown"
    >⠿</div>

    <div class="joy-ring"></div>
    <div class="joy-knob" :class="{ active: dragging }" :style="knobStyle"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const emit = defineEmits<{
  move: [keyCode: number]
  stop: []
}>()

const STORAGE_KEY = 'pkw_dpad_pos'
const JOY_SIZE    = 118   // matches CSS width/height
const BASE_R      = 55    // half diameter for knob clamping
const DEADZONE    = 18

// ── Joystick position ─────────────────────────────────────────────
const joyPos = ref<{ x: number; y: number } | null>(null)

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved) {
    try {
      const p = JSON.parse(saved) as { x: number; y: number }
      // Clamp to current viewport
      const x = Math.max(0, Math.min(window.innerWidth  - JOY_SIZE, p.x))
      const y = Math.max(0, Math.min(window.innerHeight - JOY_SIZE, p.y))
      joyPos.value = { x, y }
      return
    } catch { /* ignore bad data */ }
  }
  // Default: bottom-right on mobile, bottom-left on desktop
  const mobile = window.innerWidth <= 600
  joyPos.value = {
    x: mobile
      ? window.innerWidth  - JOY_SIZE - 20
      : 24,
    y: window.innerHeight - JOY_SIZE - 24,
  }
})

const joyBaseStyle = computed(() => {
  if (!joyPos.value) return {}
  return {
    left:   joyPos.value.x + 'px',
    top:    joyPos.value.y + 'px',
    right:  'auto',
    bottom: 'auto',
  }
})

// ── Drag handle — moves the whole joystick ────────────────────────
let hStartClientX = 0, hStartClientY = 0
let hStartPosX    = 0, hStartPosY    = 0

function onHandleDown(e: PointerEvent) {
  e.preventDefault()
  if (!joyPos.value) return
  hStartClientX = e.clientX
  hStartClientY = e.clientY
  hStartPosX    = joyPos.value.x
  hStartPosY    = joyPos.value.y
  const el = e.currentTarget as HTMLElement
  el.setPointerCapture(e.pointerId)
  el.addEventListener('pointermove',   onHandleMove)
  el.addEventListener('pointerup',     onHandleUp)
  el.addEventListener('pointercancel', onHandleUp)
}

function onHandleMove(e: PointerEvent) {
  const dx = e.clientX - hStartClientX
  const dy = e.clientY - hStartClientY
  joyPos.value = {
    x: Math.max(0, Math.min(window.innerWidth  - JOY_SIZE, hStartPosX + dx)),
    y: Math.max(0, Math.min(window.innerHeight - JOY_SIZE, hStartPosY + dy)),
  }
}

function onHandleUp(e: PointerEvent) {
  if (joyPos.value) localStorage.setItem(STORAGE_KEY, JSON.stringify(joyPos.value))
  const el = e.currentTarget as HTMLElement
  el.removeEventListener('pointermove',   onHandleMove)
  el.removeEventListener('pointerup',     onHandleUp)
  el.removeEventListener('pointercancel', onHandleUp)
}

// ── Joystick knob ─────────────────────────────────────────────────
const knobX    = ref(0)
const knobY    = ref(0)
const dragging = ref(false)

const knobStyle = computed(() => ({
  transform: `translate(calc(-50% + ${knobX.value}px), calc(-50% + ${knobY.value}px))`,
}))

let baseX = 0, baseY = 0, lastKey: number | null = null

function onDown(e: PointerEvent) {
  e.preventDefault()
  dragging.value = true
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  baseX = rect.left + rect.width  / 2
  baseY = rect.top  + rect.height / 2
  ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
  update(e.clientX, e.clientY)
}

function onMove(e: PointerEvent) {
  if (!dragging.value) return
  update(e.clientX, e.clientY)
}

function onUp() {
  if (!dragging.value) return
  dragging.value = false
  knobX.value = 0
  knobY.value = 0
  if (lastKey !== null) { emit('stop'); lastKey = null }
}

function update(cx: number, cy: number) {
  const dx    = cx - baseX
  const dy    = cy - baseY
  const dist  = Math.sqrt(dx * dx + dy * dy)
  const clamp = Math.min(dist, BASE_R)
  const angle = Math.atan2(dy, dx)

  knobX.value = Math.cos(angle) * clamp
  knobY.value = Math.sin(angle) * clamp

  if (dist < DEADZONE) {
    if (lastKey !== null) { emit('stop'); lastKey = null }
    return
  }

  const deg = angle * 180 / Math.PI
  let key: number
  if      (deg > -45  && deg <=  45)  key = 39
  else if (deg >  45  && deg <= 135)  key = 40
  else if (deg > -135 && deg <= -45)  key = 38
  else                                key = 37

  if (key !== lastKey) { lastKey = key; emit('move', key) }
}
</script>

<style scoped>
.joy-base {
  position: fixed;
  /* default position — overridden by JS via joyBaseStyle */
  bottom: 24px;
  left: 24px;
  width: 118px;
  height: 118px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(4px);
  border: 2px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.08);
  z-index: 490;
  touch-action: none;
  user-select: none;
  cursor: grab;
}

/* ── Drag handle ── */
.joy-handle {
  position: absolute;
  top: 4px;
  right: 6px;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.5);
  cursor: grab;
  touch-action: none;
  user-select: none;
  z-index: 2;
  border-radius: 4px;
  transition: color 0.15s, background 0.15s;
}
.joy-handle:hover {
  color: rgba(255, 255, 255, 0.9);
  background: rgba(255, 255, 255, 0.1);
}
.joy-handle:active { cursor: grabbing; }

.joy-ring {
  position: absolute;
  inset: 12px;
  border-radius: 50%;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
  pointer-events: none;
}

.joy-knob {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, 0.55), rgba(180, 180, 220, 0.3));
  border: 2px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.5);
  pointer-events: none;
  transition: transform 0.04s linear;
}

.joy-knob.active {
  background: radial-gradient(circle at 35% 35%, rgba(180, 200, 255, 0.8), rgba(100, 120, 220, 0.5));
  box-shadow: 0 0 14px rgba(120, 160, 255, 0.6), 0 3px 10px rgba(0, 0, 0, 0.5);
  transition: none;
}
</style>
