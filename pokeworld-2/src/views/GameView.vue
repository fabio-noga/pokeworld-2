<template>
  <AppHeader label="LOGOUT" @nav-click="doLogout" />

  <!-- Green background -->
  <div class="bg-green"></div>

  <!-- Map layer 1 (base) — moves with mapOffset -->
  <div class="loveit" :style="mapStyle">
    <img src="/textures/Map/Mapa.png" style="height:1920px;width:1155px;image-rendering:pixelated" alt="" />
  </div>

  <!-- Pet layer -->
  <div class="pet1" :style="{ zIndex: pet1ZIndex }">
    <div class="pet" :style="petStyle">
      <img id="PetImg" :src="petSprite" alt="" />
    </div>
  </div>

  <!-- Player layer -->
  <div class="player-wrap">
    <div class="player" :style="playerStyle">
      <img id="PlayerImg" :src="playerSprite" alt="" style="width:55px" />
    </div>
  </div>

  <!-- Map layer 2 (overlay) — moves in sync -->
  <div class="map2" :style="mapStyle">
    <img src="/textures/Map/Mapa2.png" style="height:1920px;width:1155px;image-rendering:pixelated" alt="" />
  </div>

  <!-- Wild encounter HUD (TrackerBush) -->
  <div id="TrackerBush" :style="{ visibility: tracker.visible ? 'visible' : 'hidden' }">
    <h3 id="trackername"><b>{{ tracker.name }}</b></h3>
    <div class="trackerdiv">
      <img v-if="tracker.img" id="trackerimg" :src="tracker.img" />
    </div>
    <p id="trackerlvl">Nível {{ tracker.level }}</p>
    <div class="tracker-status">
      <span v-if="saveStore.pokedex[String(tracker.number)] === 'caught'" class="tracker-caught">
        <img src="/textures/HUD/Pokeball.png" class="tracker-status-ball" /> Apanhado
      </span>
      <span v-else-if="saveStore.pokedex[String(tracker.number)] === 'seen'" class="tracker-seen">
        Visto
      </span>
      <span v-else class="tracker-new">
        Novo!
      </span>
    </div>
    <img src="/textures/HUD/Pokeball.png" class="tracker-ball" @click="startBattle" />
  </div>

  <!-- Player tracker (top right) -->
  <div id="PlayerTracker">
    <img :src="`/sprites/trainers/${saveStore.playerData.sprite}/image.png`" alt="" />
  </div>

  <!-- Player stats -->
  <div id="PlayerStats">
    <p style="display:inline-block;width:70%"><b>{{ teamCount }}</b></p>
    <img src="/textures/Nav/Poke/0.png" style="display:inline-block;width:18px;height:18px" alt="" />
    <p><b>${{ saveStore.playerData.dinheiro }}</b></p>
  </div>

  <!-- Team slots (right side) — drag to reorder -->
  <div class="slots">
    <template v-for="(slot, i) in saveStore.team" :key="i">
      <div v-if="slot.id > 0"
           class="slot-entry"
           :class="{ dragging: dragFrom === i }"
           draggable="true"
           @dragstart="dragFrom = i"
           @dragover.prevent
           @drop="onDrop(i)"
           @dragend="dragFrom = -1; dragOver = -1">
        <div class="slot-text">
          <p>{{ slotLabel(slot) }}</p>
          <div class="slot-hp-bar">
            <div class="slot-hp-fill" :style="{ width: hpPercent(slot)*100+'%', backgroundColor: hpColor(hpPercent(slot)) }"></div>
          </div>
          <div class="slot-xp-bar">
            <div class="slot-xp-fill" :style="{ width: xpPercent(slot)*100+'%' }"></div>
          </div>
        </div>
        <div class="slot-img"><img :src="slotImg(slot)" alt="" /></div>
      </div>
    </template>
  </div>

  <!-- Navigator (bottom center) -->
  <div id="navegator">
    <img src="/textures/Nav/Pokedex/0.png" alt="Pokédex" @click="router.push('/pokedex')" />
    <img :src="`/textures/Nav/Pad/${padOn ? 1 : 0}.png`" id="Padpng" @click="togglePad" alt="" />
    <img :src="`/textures/Nav/Poke/${pokeNavOn ? 1 : 0}.png`" id="Pokepng" @click="togglePokeNav" alt="" />
    <div class="pc-btn" @click="router.push('/pc')" title="PC">PC</div>
  </div>

  <!-- GamePad (mobile) -->
  <div id="GamePad" :style="{ display: padVisible ? 'block' : 'none' }">
    <div style="height:64px;margin:0 52px" @mousedown="onKey(38)"></div>
    <div style="margin-top:-12px;height:46px;width:64px" @mousedown="onKey(37)"></div>
    <div style="margin:-46px 0 0 86px;height:46px;width:64px" @mousedown="onKey(39)"></div>
    <div style="height:64px;margin:-12px 52px" @mousedown="onKey(40)"></div>
  </div>

</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useAuthStore } from '../stores/auth'
import { useSaveStore } from '../stores/save'
import { pokedex, padId } from '../data/pokemon'
import statsData from '../data/pokemon-stats.json'
type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number }
const STATS = statsData as Record<string, StatsEntry>

const router = useRouter()
const authStore = useAuthStore()
const saveStore = useSaveStore()

// ── Map constants (identical to game.php) ───────────────────────
const MAP_WIDTH = 24
const MAP_HEIGHT = 40
const TILE_SIZE = 48

// ── State ────────────────────────────────────────────────────────
const tileArray: number[] = []

const mapOffset = reactive({ x: 0, y: 0 })
const moving = ref(false)
let playerPosition = 468  // exact classic PlayerPosition value from game.php

const playerSprite = ref('')
const petSprite = ref('')
let petPosition1 = 1
let stepToggle = 0  // alternates between 0/1 for walk frames (= classic `position` var)

const tracker = reactive({
  visible: false,
  name: '',
  level: 0,
  img: '',
  number: 0,
})

const padOn = ref(false)
const padVisible = ref(false)
const pokeNavOn = ref(false)

// ── CSS position for map (replaces jQuery .animate) ──────────────
const mapStyle = computed(() => ({
  transform: `translate(${mapOffset.x}px, ${mapOffset.y}px)`,
  transition: moving.value ? 'transform 0.4s' : 'none',
}))

// Player stays fixed center — exact port of classic's .player CSS positioning
const playerCenter = reactive({ top: 0, left: 0 })
const playerStyle = computed(() => ({ top: playerCenter.top + 'px', left: playerCenter.left + 'px' }))

// Middle1/Middle2 — classic pet resting center (ji/2-78-10, je/2-50.5+3.5)
const middle = reactive({ top: 0, left: 0 })

// Pet position — animates step-and-return like classic jQuery
const petPos = reactive({ top: 0, left: 0 })
const petTransition = ref('none')
const pet1ZIndex = ref(0)
const petStyle = computed(() => ({
  top: petPos.top + 'px',
  left: petPos.left + 'px',
  transition: petTransition.value,
}))

// ── Helpers ──────────────────────────────────────────────────────
const teamCount = computed(() => saveStore.pc.filter(p => p?.id).length)

function slotLabel(slot: { id: number; lvl: number }) {
  if (!slot.id) return ''
  return `${pokedex(slot.id)} | LVL: ${slot.lvl}`
}
function slotImg(slot: { id: number }) {
  if (!slot.id) return ''
  return `/textures/Mini/Gif/${padId(slot.id)}.gif`
}

// ── Build tile array (exact port of game.php buildMap JS) ────────
function buildMap() {
  let n = 1
  for (let i = 1; i <= MAP_HEIGHT; i++) {
    for (let j = 1; j <= MAP_WIDTH; j++) {
      let x: number
      if ((j === 22 || j === 23) && i === 37) { x = 9 }
      else if ((i >= MAP_HEIGHT - 1) || (j >= MAP_WIDTH - 1) || (i <= 2) || (j <= 2)) { x = 4 }
      else if ((j === 9 || j === 10) && (i > 4 && i < 13)) { x = 4 }
      else if ((j === 3 || j === 4 || (j > 10 && j < 17)) && (i === 16 || i === 17)) { x = 4 }
      else if ((j > 0 && j < 13) && (i === 26 || i === 27)) { x = 4 }
      else if ((i === 37 || i === 38) && (j < 13 || j > 14)) { x = 4 }
      else if (j === 10 && i === 32) { x = 4 }
      // Fences
      else if ((j > 2 && j < 9) && (i === 6)) { x = 6 }
      else if ((j > 10 && j < 17) && (i === 6)) { x = 6 }
      else if ((j > 2 && j < 9) && (i === 11)) { x = 6 }
      else if ((j > 4 && j < 11) && (i === 16)) { x = 6 }
      else if ((j > 2 && j < 5) && (i === 21)) { x = 6 }
      else if ((j > 5 && j < 10) && (i === 21)) { x = 6 }
      else if ((j > 11 && j < 24) && (i === 21)) { x = 6 }
      else if ((j > 18 && j < 24) && (i === 27)) { x = 6 }
      else if ((j > 2 && j < 7) && (i === 32)) { x = 6 }
      else if ((j > 10 && j < 24) && (i === 32)) { x = 6 }
      // Bushes (encounter zones)
      else if ((j > 10 && j < 24) && (i > 6 && i < 12)) { x = 1 }
      else if ((j > 16 && j < 24) && (i > 13 && i < 19)) { x = 1 }
      else if ((j > 12 && j < 19) && (i > 24 && i < 30)) { x = 1 }
      else if ((j > 4 && j < 12) && (i > 32 && i < 35)) { x = 1 }
      else if ((j > 17 && j < 23) && (i > 32 && i < 35)) { x = 1 }
      else if ((j > 15 && j < 21) && (i > 34 && i < 37)) { x = 1 }
      else if ((j > 2 && j < 10) && (i > 34 && i < 37)) { x = 1 }
      else if ((j > 12 && j < 15) && (i > 35 && i < 38)) { x = 1 }
      else { x = 0 }

      tileArray[n] = x
      n++
    }
  }
}

// ── Battle encounter (exact port of game.php battle()) ───────────
const allDead = computed(() => saveStore.team.filter(s => s.id > 0).every(s => s.hp <= 0))

function checkBattle() {
  if (allDead.value) {
    tracker.visible = false
    return
  }
  if (tileArray[playerPosition] === 1) {
    const rande = Math.floor(Math.random() * 3) + 1
    if (rande === 1) {
      let num = Math.floor(Math.random() * 151) + 1
      const teamLevels = saveStore.team.filter(s => s.id > 0).map(s => s.lvl)
      const minLvl = teamLevels.length ? Math.min(...teamLevels) : 2
      const maxLvl = teamLevels.length ? Math.max(...teamLevels) : 5
      const lvl = Math.max(1, Math.floor(Math.random() * (maxLvl - minLvl + 1)) + minLvl)
      tracker.number = num
      tracker.level = lvl
      tracker.name = pokedex(num)
      tracker.img = `/textures/Art/${padId(num)}.png`
      tracker.visible = true
      saveStore.encounter.number = num
      saveStore.encounter.level = lvl
    } else {
      tracker.visible = false
    }
  } else {
    tracker.visible = false
  }
}

function startBattle() {
  if (allDead.value) {
    router.push('/pc')
    return
  }
  router.push('/battle')
}

// ── Player sprite helpers ────────────────────────────────────────
const tex = computed(() => saveStore.playerData.sprite)

function setPlayerSprite(frame: number) {
  playerSprite.value = `/sprites/trainers/${tex.value}/${frame}.png`
}

// ── Movement (exact port of move.js KeyDown) ─────────────────────
function onKey(keyCode: number) {
  if (moving.value) return  // queue guard (replaces jQuery queue check)

  if (keyCode === 38) {
    // UP
    if (stepToggle === 0) { setPlayerSprite(11); stepToggle++ }
    else { setPlayerSprite(12); stepToggle = 0 }
    setTimeout(() => setPlayerSprite(10), 250)

    if (tileArray[playerPosition - MAP_WIDTH] !== 4 && tileArray[playerPosition - MAP_WIDTH] !== 6) {
      moving.value = true
      playerPosition -= MAP_WIDTH
      mapOffset.y += TILE_SIZE
      checkBattle()
      petTransition.value = 'top 0.4s, left 0.4s'
      petPos.top += TILE_SIZE
      petPosition1 = 7
      setTimeout(() => {
        petTransition.value = 'top 0.22s, left 0.22s'
        petPos.top = middle.top + 50
        petPos.left = middle.left
        pet1ZIndex.value = 2
      }, 220)
      setTimeout(() => { moving.value = false }, 400)
    }

  } else if (keyCode === 40) {
    // DOWN
    if (stepToggle === 0) { setPlayerSprite(2); stepToggle++ }
    else { setPlayerSprite(3); stepToggle = 0 }
    setTimeout(() => setPlayerSprite(1), 250)

    if (tileArray[playerPosition + MAP_WIDTH] !== 4 && tileArray[playerPosition + MAP_WIDTH] !== 6) {
      moving.value = true
      playerPosition += MAP_WIDTH
      mapOffset.y -= TILE_SIZE
      checkBattle()
      petTransition.value = 'top 0.4s, left 0.4s'
      petPos.top -= TILE_SIZE
      setTimeout(() => {
        petPosition1 = 1
        updatePetSprite()
        petTransition.value = 'top 0.22s, left 0.22s'
        petPos.top = middle.top
        petPos.left = middle.left
        pet1ZIndex.value = 0
      }, 220)
      setTimeout(() => { moving.value = false }, 400)
    } else if (tileArray[playerPosition + MAP_WIDTH] === 6) {
      moving.value = true
      playerPosition += MAP_WIDTH * 2
      mapOffset.y -= TILE_SIZE * 2
      petTransition.value = 'top 0.4s, left 0.4s'
      petPos.top -= TILE_SIZE * 2
      setTimeout(() => {
        petPosition1 = 1
        updatePetSprite()
        petTransition.value = 'top 0.22s, left 0.22s'
        petPos.top = middle.top
        petPos.left = middle.left
        pet1ZIndex.value = 0
      }, 220)
      setTimeout(() => { moving.value = false }, 400)
    }

  } else if (keyCode === 37) {
    // LEFT
    setPlayerSprite(5)
    setTimeout(() => setPlayerSprite(4), 250)

    if (tileArray[playerPosition - 1] !== 4 && tileArray[playerPosition - 1] !== 6) {
      moving.value = true
      playerPosition -= 1
      mapOffset.x += TILE_SIZE
      checkBattle()
      petTransition.value = 'top 0.4s, left 0.4s'
      petPos.left += TILE_SIZE
      setTimeout(() => {
        petPosition1 = 3
        updatePetSprite()
        petTransition.value = 'top 0.22s, left 0.22s'
        petPos.left = middle.left + 38
        petPos.top = middle.top + 30
      }, 220)
      setTimeout(() => { moving.value = false }, 400)
    }

  } else if (keyCode === 39) {
    // RIGHT
    setPlayerSprite(9)
    setTimeout(() => setPlayerSprite(7), 250)

    if (tileArray[playerPosition + 1] !== 4 && tileArray[playerPosition + 1] !== 6) {
      moving.value = true
      playerPosition += 1
      mapOffset.x -= TILE_SIZE
      checkBattle()
      // Pet steps right with map
      petTransition.value = 'top 0.4s, left 0.4s'
      petPos.left -= TILE_SIZE
      setTimeout(() => {
        petPosition1 = 5
        updatePetSprite()
        petTransition.value = 'top 0.22s, left 0.22s'
        petPos.left = middle.left - 38
        petPos.top = middle.top + 30
      }, 220)
      setTimeout(() => { moving.value = false }, 400)
    }
  }
}

function handleKeyDown(e: KeyboardEvent) {
  if ([37, 38, 39, 40].includes(e.keyCode)) {
    e.preventDefault()
    onKey(e.keyCode)
  }
}

// ── Nav controls ─────────────────────────────────────────────────
function togglePad() {
  padOn.value = !padOn.value
  padVisible.value = padOn.value
}
function togglePokeNav() {
  pokeNavOn.value = !pokeNavOn.value
  const el1 = document.getElementById('PlayerStats')
  const el2 = document.getElementById('PlayerTracker')
  if (el1) el1.style.visibility = pokeNavOn.value ? 'hidden' : 'visible'
  if (el2) el2.style.visibility = pokeNavOn.value ? 'hidden' : 'visible'
}

async function doLogout() {
  await authStore.logout()
  router.push('/')
}

// ── Healing & team management ─────────────────────────────────────
function calcMaxHP(baseHP: number, level: number) {
  return Math.floor(2 * baseHP * level / 100) + level + 10
}
function hpPercent(slot: { id: number; hp: number; lvl: number }) {
  const max = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  return Math.max(0, Math.min(1, slot.hp / max))
}
function hpColor(pct: number) {
  return pct > 0.5 ? '#2ecc40' : pct > 0.25 ? '#ffdc00' : '#ff4136'
}
function xpPercent(slot: { xp: number; lvl: number }) {
  const threshold = slot.lvl * 10
  return Math.min(1, (slot.xp % threshold) / threshold)
}

// ── Drag and drop reorder ─────────────────────────────────────────
const dragFrom = ref(-1)
const dragOver = ref(-1)
function onDrop(i: number) {
  if (dragFrom.value < 0 || dragFrom.value === i) return
  const a = dragFrom.value
  const temp = { ...saveStore.team[a] }
  saveStore.team[a] = { ...saveStore.team[i] }
  saveStore.team[i] = temp
  dragFrom.value = -1
  dragOver.value = -1
  saveStore.save()
}

// ── Pet walking animation (identical to game.php setInterval) ────
let petInterval: ReturnType<typeof setInterval>
const petType = 'Normal'
const slot1Id = computed(() => saveStore.team[0]?.id ?? 1)

function updatePetSprite() {
  petSprite.value = `/textures/Overworld/${petType}/${petPosition1}/${slot1Id.value}.png`
}

// ── Init (equivalent to game.php draw()) ─────────────────────────
onMounted(() => {
  if (saveStore.team.filter(s => s.id > 0).every(s => s.hp <= 0)) {
    router.push('/pc')
    return
  }
  buildMap()

  const ji = window.innerHeight
  const je = window.innerWidth

  // Player fixed center — exact port: $(".player").css('top', ji/2-78)
  playerCenter.top = ji / 2 - 78
  playerCenter.left = je / 2 - 50.5

  // Classic formula: center the map image on screen, identical to game.php draw()
  // mapH=1920 → ji/2-960; mapW=1155 → je/2-577.5
  // This places the player at map pixel (527, 882) = tile 443 (row 19 col 11)
  mapOffset.y = ji / 2 - 960
  mapOffset.x = je / 2 - 577.5

  // Middle1/Middle2 — exact port: Middle1=ji/2-78-10, Middle2=je/2-50.5+3.5
  middle.top = ji / 2 - 78 - 10
  middle.left = je / 2 - 50.5 + 3.5

  // Pet starts at Middle1/Middle2 (DOWN resting position)
  petPos.top = middle.top
  petPos.left = middle.left

  // Initial sprites
  setPlayerSprite(1)
  updatePetSprite()

  // Mobile pad visibility
  if (window.innerWidth < 980) padVisible.value = true

  // Keydown listener
  window.addEventListener('keydown', handleKeyDown)

  // Pet walk animation (exact port of game.php setInterval 500ms)
  petInterval = setInterval(() => {
    if (petPosition1 % 2 !== 0) {
      updatePetSprite()
      petPosition1++
    } else {
      updatePetSprite()
      petPosition1--
    }
  }, 500)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  clearInterval(petInterval)
})
</script>

<style scoped>
/* Identical structure to game.php inline styles */
.bg-green {
  position: fixed;
  background-color: rgb(74, 182, 138);
  top: 0; right: 0; bottom: 0; left: 0;
  z-index: -2;
}
.loveit {
  position: fixed;
  z-index: -1;
  top: 0; left: 0;
  will-change: transform;
}
.map2 {
  position: fixed;
  z-index: 2;
  top: 0; left: 0;
  will-change: transform;
}
.pet1 {
  position: fixed;
  top: 0; right: 0; bottom: 0; left: 0;
}
.pet {
  position: absolute;
}
.pet > img {
  image-rendering: pixelated;
  width: 48px;
  height: 48px;
}
.player-wrap {
  position: fixed;
  top: 0; right: 0; bottom: 0; left: 0;
  z-index: 1;
}
.player {
  position: absolute;
}
.player > img {
  image-rendering: pixelated;
}

/* TrackerBush */
#TrackerBush {
  position: absolute;
  bottom: 0; left: 0;
  width: 200px;
  z-index: 6;
  border-right: 3px solid black;
  border-top: 3px solid black;
  background-color: #333;
  border-top-right-radius: 10px;
  color: white;
  text-align: center;
  padding-bottom: 10px;
}
#TrackerBush > h3 { padding-top: 10px; }
#TrackerBush > p { padding-top: 4px; margin: 0; }
.trackerdiv {
  width: 123px;
  height: 123px;
  border: 4px solid black;
  display: inline-block;
  border-radius: 100%;
  overflow: hidden;
  background-color: white;
}
.trackerdiv > img { width: 115px; height: 115px; object-fit: cover; }
.tracker-ball {
  width: 56px;
  cursor: pointer;
  display: block;
  margin: 8px auto 0;
  opacity: 0.85;
  transition: transform 0.1s, opacity 0.1s;
}
.tracker-ball:hover { opacity: 1; transform: scale(1.12); }
.tracker-status { font-size: 11px; margin: 4px 0 0; font-weight: bold; }
.tracker-caught { color: #ff4444; display: flex; align-items: center; justify-content: center; gap: 4px; }
.tracker-status-ball { width: 12px; height: 12px; }
.tracker-seen { color: #aaa; }
.tracker-new { color: #22cc66; }

/* Player tracker */
#PlayerTracker {
  z-index: 2;
  width: 120px; height: 120px;
  background-image: url('/textures/HUD/PlayerBack.jpg');
  position: absolute;
  top: 80px; right: 0;
  border-bottom: 3px solid black;
  border-left: 3px solid black;
  background-size: cover;
}
#PlayerTracker > img { width: 117px; height: 117px; image-rendering: pixelated; }

/* Player stats */
#PlayerStats {
  width: 120px;
  background-color: #5F2890;
  color: white;
  text-align: right;
  position: absolute;
  top: 200px; right: 0;
  border-bottom: 3px solid black;
  border-left: 3px solid black;
  border-bottom-left-radius: 10px;
}
#PlayerStats > p { padding: 0; margin: 0; }

/* Team slots — classic layout with extras */
.slots {
  width: 500px;
  position: absolute;
  bottom: 0; right: 0;
  text-align: right;
  z-index: 2;
}
.slot-entry {
  cursor: grab;
  user-select: none;
  padding: 2px 0;
}
.slot-entry:active { cursor: grabbing; }
.slot-entry.dragging { opacity: 0.35; }
.slot-text {
  display: inline-block;
  vertical-align: middle;
  margin: 0 10px 0 0;
  text-align: right;
}
.slot-text > p {
  font-weight: bold;
  margin: 0;
  font-size: 14px;
}
.slot-hp-bar {
  width: 100px;
  height: 4px;
  background: rgba(0,0,0,0.18);
  border-radius: 2px;
  overflow: hidden;
  margin: 2px 0 0 auto;
}
.slot-hp-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s, background-color 0.3s;
}
.slot-xp-bar {
  width: 100px;
  height: 2px;
  background: rgba(0,0,0,0.18);
  border-radius: 2px;
  overflow: hidden;
  margin: 2px 0 0 auto;
}
.slot-xp-fill {
  height: 100%;
  background: #f0c000;
  border-radius: 2px;
  transition: width 0.4s;
}
.slot-img {
  width: 40px;
  float: right;
  display: block;
}
.slot-img > img {
  display: inline-block;
  background-color: white;
  border-radius: 100%;
  border: 2px solid black;
  width: 38px;
  height: 38px;
  image-rendering: pixelated;
}

/* Navigator */
#navegator {
  height: 50px;
  background-color: transparent;
  margin: auto;
  margin-top: 82px;
  width: 260px;
  position: relative;
  z-index: 3;
}
#navegator > img { width: 50px; cursor: pointer; }
.pc-btn {
  display: inline-block;
  width: 50px; height: 50px;
  line-height: 50px;
  text-align: center;
  font-weight: bold;
  font-size: 13px;
  background: #ff1c1c;
  color: white;
  border-radius: 6px;
  cursor: pointer;
  vertical-align: top;
}
.pc-btn:hover { background: #cc0000; }

/* GamePad */
#GamePad {
  position: absolute;
  z-index: 3;
  bottom: 10px; left: 10px;
  width: 150px; height: 150px;
  background: url('/textures/Nav/Pad/0.png');
  background-size: cover;
}


/* Mobile */
@media screen and (max-width: 980px) {
  #GamePad { display: block; }
  #navegator { margin-right: 0; }
  #TrackerBush {
    right: 0; left: auto;
    border-left: 3px solid black;
    border-right: 0;
    border-top-right-radius: 0;
    border-top-left-radius: 10px;
  }
  #PlayerTracker, #PlayerStats { visibility: hidden; }
}
</style>
