<template>
  <div
    class="joy-base"
    @pointerdown="onDown"
    @pointermove="onMove"
    @pointerup="onUp"
    @pointercancel="onUp"
  >
    <div class="joy-ring"></div>
    <div class="joy-knob" :class="{ active: dragging }" :style="knobStyle"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const emit = defineEmits<{
  move: [keyCode: number]
  stop: []
}>()

const BASE_R   = 55   // half the base diameter — keep in sync with CSS (118px / 2 - border)
const DEADZONE = 18   // px from centre before direction registers

const knobX    = ref(0)
const knobY    = ref(0)
const dragging = ref(false)

const knobStyle = computed(() => ({
  transform: `translate(calc(-50% + ${knobX.value}px), calc(-50% + ${knobY.value}px))`,
}))

let baseX = 0
let baseY = 0
let lastKey: number | null = null

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
  if      (deg > -45  && deg <=  45)  key = 39  // right
  else if (deg >  45  && deg <= 135)  key = 40  // down
  else if (deg > -135 && deg <= -45)  key = 38  // up
  else                                key = 37  // left

  if (key !== lastKey) {
    lastKey = key
    emit('move', key)
  }
}
</script>

<style scoped>
.joy-base {
  position: fixed;
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
