<template>
  <AppHeader @nav-click="doLogout" />

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

  <!-- Debug tile overlay — toggle with backtick ` -->
  <div v-if="debugTiles" class="tile-debug-layer" :style="mapStyle">
    <div
      v-for="(val, i) in tileArray.slice(1)"
      :key="i"
      class="tile-debug-cell"
      :class="`tdbg-${val}`"
      :style="{
        left: ((i % MAP_WIDTH) * TILE_SIZE) + 'px',
        top:  (Math.floor(i / MAP_WIDTH) * TILE_SIZE) + 'px',
        width: TILE_SIZE + 'px',
        height: TILE_SIZE + 'px',
      }"
    >{{ val }}</div>
  </div>

  <!-- Other players (multiplayer) — same transform as map -->
  <div class="other-players-layer" :style="mapStyle">
    <div
      v-for="p in mpStore.playersList" :key="p.id"
      class="other-player"
      :style="otherPlayerTileStyle(p.tile)"
    >
      <!-- Follower Pokémon — overworld sprite, trails behind -->
      <img
        v-if="p.followerId > 0"
        :src="otherPetSrc(p)"
        :class="['other-follower', { walking: p.walking }]"
        :style="otherPetOffset(p.dir)"
        alt=""
      />
      <!-- Name tag -->
      <div class="other-player-name">{{ p.name }}</div>
      <!-- Trainer sprite — same frame system as local player -->
      <img
        :src="`/sprites/trainers/${p.sprite}/${p.walking ? walkFrames[p.dir] : idleFrames[p.dir]}.png`"
        class="other-trainer"
        alt=""
      />
    </div>
  </div>

  <!-- NPCs — share the same transform as the map so they scroll with it smoothly -->
  <div class="npc-layer" :style="mapStyle">
    <div
      v-for="npc in npcs" :key="npc.id"
      class="npc"
      :style="npcTileStyle(npc.tile)"
      @click="walkToNpc(npc)"
    >
      <img :src="npc.overworld" alt="" />

      <!-- Bubble anchored above this NPC's head -->
      <Transition name="bubble">
        <div class="npc-bubble" v-if="activeBubble?.id === npc.id" @click.stop>
          <button class="bubble-close" @click.stop="activeBubble = null">✕</button>
          <div class="bubble-name">{{ npc.name }}</div>
          <div v-if="npc.isTrainer && (saveStore.trainerWins[npc.id] ?? 0) > 0" class="bubble-win-count">
            You only defeated me {{ saveStore.trainerWins[npc.id] }} time{{ saveStore.trainerWins[npc.id] > 1 ? 's' : '' }}!
          </div>
          <div class="bubble-text">{{ npc.dialogue }}</div>
          <button
            v-if="npc.isTrainer"
            class="bubble-fight-btn"
            @click.stop="startTrainerBattle(npc)"
          >⚔ FIGHT</button>
        </div>
      </Transition>
    </div>
  </div>

  <!-- Wild encounter HUD (TrackerBush) -->
  <div id="TrackerBush" :style="{ visibility: tracker.visible ? 'visible' : 'hidden' }">
    <div class="tracker-body">
      <!-- Left: Pokémon image -->
      <div class="trackerdiv">
        <img v-if="tracker.img" id="trackerimg" :src="tracker.img" />
      </div>
      <!-- Right: info + catch ball -->
      <div class="tracker-info">
        <h3 id="trackername">
          <b>{{ tracker.shiny ? '✨ ' : '' }}{{ tracker.name }}</b>
          <!-- Normal caught → regular ball -->
          <img v-if="saveStore.pokedex[String(tracker.number)] === 'caught'"
               src="/textures/HUD/Pokeball.png" class="tracker-name-ball"
               title="Normal caught" />
          <!-- Shiny caught → golden ball -->
          <img v-if="saveStore.shinydex[String(tracker.number)] === 'caught'"
               src="/textures/HUD/Pokeball.png" class="tracker-name-ball tracker-name-ball--shiny"
               title="Shiny caught" />
        </h3>
        <p id="trackerlvl">Nível {{ tracker.level }}</p>
        <div class="tracker-status">
          <span v-if="!saveStore.pokedex[String(tracker.number)]" class="tracker-new">Novo!</span>
        </div>
        <img src="/textures/HUD/Pokeball.png" class="tracker-ball" @click="startBattle" />
      </div>
    </div>
  </div>

  <!-- Multiplayer toast -->
  <Transition name="mp-toast">
    <div v-if="mp.toastMsg.value" class="mp-toast">{{ mp.toastMsg.value }}</div>
  </Transition>

  <!-- First-run modal — name + starter selection for new players -->
  <FirstRunModal
    v-if="showFirstRun"
    :initial-name="saveStore.playerData.nome !== 'Player' ? saveStore.playerData.nome : ''"
    @confirm="onFirstRun"
  />

  <!-- Multiplayer modal -->
  <MultiplayerModal
    v-if="showMpModal"
    :current-tile="playerTileRef"
    :current-dir="currentDir"
    @close="showMpModal = false"
  />

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

  <!-- Team slots — classic party card grid -->
  <div class="slots-panel">
    <div class="slots">
      <template v-for="(slot, i) in saveStore.team" :key="i">
        <!-- Outer wrapper: handles grid, events, pokéball (not clipped) -->
        <div v-if="slot.id > 0"
             class="slot-outer"
             :class="{ dragging: dragFrom === i }"
             draggable="true"
             @click.stop="openSlotInfo(slot, i)"
             @mouseenter="hoveredSlot = i"
             @mouseleave="hoveredSlot = -1"
             @dragstart="dragFrom = i"
             @dragover.prevent
             @drop="onDrop(i)"
             @dragend="dragFrom = -1; dragOver = -1">

          <!-- Pokéball sits at the cut corner, outside the clipped card -->
          <img src="/textures/HUD/Pokeball.png" class="slot-ball" alt="" />

          <!-- Inner card: clipped to diamond-corner shape -->
          <div class="slot-entry">

            <div v-if="slot.pendingEvo || slot.shiny" class="slot-icons">
              <span v-if="slot.pendingEvo" class="slot-evo-badge">↑</span>
              <span v-if="slot.shiny" class="slot-shiny-icon">✨</span>
            </div>
            <img :src="slotImg(slot, hoveredSlot === i)" class="slot-sprite" alt="" />

            <div class="slot-info">
              <div class="slot-name">
                <span class="slot-name-text">{{ slot.nickname || pokedex(slot.id) }}</span>
              </div>
              <div class="slot-lv">Lv.<b>{{ slot.lvl }}</b></div>

              <div class="slot-bars">
                <div class="slot-bar-row">
                  <span class="slot-bar-lbl">HP</span>
                  <div class="slot-hp-bar">
                    <div class="slot-hp-fill" :style="{ width: hpPercent(slot)*100+'%', backgroundColor: hpColor(hpPercent(slot)) }"></div>
                  </div>
                  <span class="slot-hp-vals">{{ slot.hp }}<span class="slot-hp-sep">/</span>{{ slotMaxHP(slot) }}</span>
                </div>
                <div class="slot-bar-row">
                  <span class="slot-bar-lbl">XP</span>
                  <div class="slot-xp-bar">
                    <div class="slot-xp-fill" :style="{ width: xpPercent(slot)*100+'%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Water / swimming indicator -->
  <Transition name="water-hud">
    <div v-if="tileArray[playerTileRef] === 2" class="water-hud">
      <span class="water-hud-icon">🌊</span>
      <span class="water-hud-lbl">SURF</span>
    </div>
  </Transition>

  <!-- Fishing spot indicator -->
  <Transition name="water-hud">
    <div v-if="tileArray[playerTileRef] === 9" class="water-hud fishing-hud">
      <img src="/textures/Items/27.png" class="water-hud-icon fishing-rod-icon" alt="🎣" />
      <span class="water-hud-lbl fishing-lbl">
        fishing<span class="fishing-dots">{{ '.'.repeat(fishingDots) }}</span>
      </span>
    </div>
  </Transition>

  <!-- Party info modal -->
  <PokemonInfoModal
    v-if="inspectedSlot"
    :slot="inspectedSlot"
    @close="inspectedSlot = null"
    @evolve="evolveInspectedSlot"
  />

  <!-- Mobile party strip — circular icons with HP ring (left side) -->
  <div class="mob-party">
    <div
      v-for="slot in saveStore.team.filter(s => s.id > 0)"
      :key="slot.id"
      class="mob-poke-wrap"
      :style="{
        '--ring-pct': (hpPercent(slot) * 360) + 'deg',
        '--ring-color': hpColor(hpPercent(slot)),
      }"
      @click="openSlotInfo(slot, saveStore.team.indexOf(slot))"
    >
      <div class="mob-poke-ring">
        <div class="mob-poke-inner">
          <img :src="slotImg(slot)" alt="" class="mob-poke-img" />
          <span v-if="slot.pendingEvo" class="mob-evo">↑</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick menu (DEX / PC / D-PAD) — below #PlayerStats -->
  <QuickMenu :pad-active="padVisible" :debug-mode="debugTiles" @toggle-dpad="togglePad" @network-click="showMpModal = true" />

  <!-- Virtual joystick -->
  <VirtualJoystick
    v-if="padVisible"
    @move="startPad"
    @stop="stopPad"
  />

</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import QuickMenu from '../components/QuickMenu.vue'
import VirtualJoystick from '../components/VirtualJoystick.vue'
import PokemonInfoModal from '../components/PokemonInfoModal.vue'
import MultiplayerModal from '../components/MultiplayerModal.vue'
import FirstRunModal from '../components/FirstRunModal.vue'
import { useAuthStore } from '../stores/auth'
import { useSaveStore } from '../stores/save'
import { useMultiplayer, autoConnect, broadcastMove, broadcastFollower } from '../composables/useMultiplayer'
import { useMultiplayerStore, type Direction } from '../stores/multiplayer'
import { getOrCreateUUID, isFirstSession } from '../utils/uuid'
import type { TeamSlot } from '../stores/save'
import { pokedex, padId } from '../data/pokemon'
import statsData from '../data/pokemon-stats.json'
type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number }
const STATS = statsData as Record<string, StatsEntry>

const router = useRouter()
const authStore = useAuthStore()
const saveStore = useSaveStore()
const mp = useMultiplayer()
const mpStore = useMultiplayerStore()

// ── Multiplayer UI ───────────────────────────────────────────────
const showMpModal   = ref(false)
const showFirstRun  = ref(false)
const currentDir    = ref<Direction>('down')
// Sprite frames per direction: idle and walking
const idleFrames: Record<Direction, number> = { down: 1, up: 10, left: 4, right: 7 }
const walkFrames: Record<Direction, number> = { down: 2, up: 11, left: 5, right: 9 }

// ── Other-player follower helpers ────────────────────────────────────────────
// Overworld frame: idle / walk per direction (matches local pet frame system)
const petIdleFrame: Record<Direction, number> = { down: 1, up: 7, left: 3, right: 5 }
const petWalkFrame: Record<Direction, number> = { down: 2, up: 8, left: 4, right: 6 }

function otherPetSrc(p: { followerId: number; shiny: boolean; dir: Direction; walking: boolean }): string {
  const frame = p.walking ? petWalkFrame[p.dir] : petIdleFrame[p.dir]
  if (p.shiny) {
    const folder = SHINY_FOLDERS[frame] ?? 'Front'
    return `/textures/Overworld/Shiny/${folder}/${p.followerId}.png`
  }
  return `/textures/Overworld/Normal/${frame}/${p.followerId}.png`
}

// Follower pixel offsets relative to .other-player.
// The div origin is now (tile_top - 30), so offsets = tile-relative values + 30:
//   UP    tile+22  → div+52    (behind trainer, higher z)
//   DOWN  tile-62  → div-32    (in front of trainer)
//   LEFT  tile-18  → div+12    (right side)
//   RIGHT tile-18  → div+12    (left side)
function otherPetOffset(dir: Direction): Record<string, string> {
  switch (dir) {
    case 'up':    return { top: '52px',  left: '-6px',  zIndex: '2' }
    case 'down':  return { top: '-32px', left: '-6px',  zIndex: '0' }
    case 'left':  return { top: '12px',  left: '33px',  zIndex: '0' }
    case 'right': return { top: '12px',  left: '-44px', zIndex: '0' }
  }
}

// ── Map constants (identical to game.php) ───────────────────────
const MAP_WIDTH = 24
const MAP_HEIGHT = 40
const TILE_SIZE = 48
const STEP_MS  = 260   // first step (deliberate)
const CHAIN_MS = 180   // held key (continuous walk)

// ── NPC definitions ──────────────────────────────────────────────
interface NpcDef {
  id: string
  tile: number
  overworld: string
  portrait: string   // trainer.gif — used in battle intro
  still: string      // trainer.png — used in battle outro
  name: string
  dialogue: string
  isTrainer: boolean
  slider?: string
  team?: { id: number; minLvl: number; maxLvl: number }[]
}

// Bias toward Q3: max(r1, r2) distribution median ≈ 0.71 of range
function biasedLevel(min: number, max: number): number {
  const r = Math.max(Math.random(), Math.random())
  return Math.round(min + r * (max - min))
}

const npcs: NpcDef[] = [
  {
    id: 'gym1',
    tile: 198,
    overworld: '/textures/Gym/1/overworld.png',
    portrait:  '/textures/Gym/1/trainer.gif',
    still:     '/textures/Gym/1/trainer.png',
    name: 'Gym Leader Roark',
    dialogue: 'I use Rock-type Pokémon! My Pokémon and I will crush you!',
    isTrainer: true,
    slider: '/textures/Gym/1/slider.png',
    team: [
      { id: 74, minLvl: 10, maxLvl: 16 },  // Geodude
      { id: 95, minLvl: 10, maxLvl: 16 },  // Onix
      { id: 76, minLvl: 12, maxLvl: 18 },  // Golem
    ],
  },
]

// ── State ────────────────────────────────────────────────────────
const tileArray: number[] = []

const mapOffset = reactive({ x: 0, y: 0 })
const moving = ref(false)
const debugTiles = ref(false)
const stepMs = ref(STEP_MS)   // current step duration (switches to CHAIN_MS when chaining)
let pendingKey: number | null = null
let playerPosition = saveStore.mapPos ?? 468
const playerTileRef = ref(playerPosition)   // reactive mirror for NPC adjacency
let facingFrame = saveStore.mapDir ?? 1  // resting sprite frame: 1=down 4=left 7=right 10=up

// ── Party info modal ─────────────────────────────────────────────
const inspectedSlot      = ref<TeamSlot | null>(null)
const inspectedSlotIndex = ref(-1)

function openSlotInfo(slot: TeamSlot, index: number) {
  if (slot.id > 0) {
    inspectedSlot.value      = { ...slot }
    inspectedSlotIndex.value = index
  }
}

function evolveInspectedSlot() {
  const idx = inspectedSlotIndex.value
  if (idx < 0) return
  const slot = saveStore.team[idx]
  if (!slot?.pendingEvo) return
  const oldMax = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  slot.id = slot.pendingEvo
  const newMax = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  slot.hp = Math.min(slot.hp + Math.max(0, newMax - oldMax), newMax)
  slot.pendingEvo = undefined
  saveStore.pokedex[String(slot.id)] = 'caught'
  saveStore.save()
  inspectedSlot.value = null
  // Broadcast follower change if the evolved Pokémon is in slot 0
  if (idx === 0) {
    broadcastFollower(slot.id, saveStore.shinydex?.[String(slot.id)] === 'caught')
  }
}

// ── NPC walk-to state ─────────────────────────────────────────────
let walkTarget: number | null = null

// ── NPC state ────────────────────────────────────────────────────
const activeBubble = ref<NpcDef | null>(null)

const adjacentNpcId = computed(() => {
  const pr = Math.floor((playerTileRef.value - 1) / MAP_WIDTH)
  const pc = (playerTileRef.value - 1) % MAP_WIDTH
  for (const npc of npcs) {
    const nr = Math.floor((npc.tile - 1) / MAP_WIDTH)
    const nc = (npc.tile - 1) % MAP_WIDTH
    if (Math.abs(pr - nr) + Math.abs(pc - nc) <= 1) return npc.id
  }
  return null
})

function npcTileStyle(tile: number) {
  const nr = Math.floor((tile - 1) / MAP_WIDTH)
  const nc = (tile - 1) % MAP_WIDTH
  return {
    left: (nc * TILE_SIZE) + 'px',
    top:  (nr * TILE_SIZE) + 'px',
  }
}

// Other players: shift div 30 px above tile top so the trainer sprite aligns
// with the local player's trainer (which is at playerCenter.top = tile_top - 30).
// Follower offsets below are derived relative to this adjusted origin.
function otherPlayerTileStyle(tile: number) {
  const nr = Math.floor((tile - 1) / MAP_WIDTH)
  const nc = (tile - 1) % MAP_WIDTH
  return {
    left: (nc * TILE_SIZE) + 'px',
    top:  (nr * TILE_SIZE - 30) + 'px',
  }
}

watch(adjacentNpcId, (id) => {
  activeBubble.value = id ? (npcs.find(n => n.id === id) ?? null) : null
})

// Start / stop fishing cycle when player enters or leaves tile 9
watch(playerTileRef, (tile) => {
  if (tileArray[tile] === 9) {
    startFishingCycle()
  } else {
    stopFishingCycle()
  }
})

function startTrainerBattle(npc: NpcDef) {
  if (!npc.team) return
  activeBubble.value = null
  saveStore.encounter.isTrainer = true
  saveStore.encounter.trainerId = npc.id
  saveStore.encounter.trainerName = npc.name
  saveStore.encounter.trainerSlider = npc.slider ?? ''
  saveStore.encounter.trainerPortrait = npc.portrait
  saveStore.encounter.trainerStill = npc.still
  // Generate levels with Q3 bias at battle start
  const teamWithLevels = npc.team.map(p => ({
    id: p.id,
    lvl: biasedLevel(p.minLvl, p.maxLvl),
  }))
  saveStore.encounter.trainerTeam = teamWithLevels
  saveStore.encounter.number = teamWithLevels[0].id
  saveStore.encounter.level  = teamWithLevels[0].lvl
  saveStore.encounter.shiny  = false
  router.push('/battle')
}

// ── Walk-to NPC ───────────────────────────────────────────────────
function findWalkableAdjacentToNpc(npc: NpcDef): number | null {
  const candidates = [npc.tile - 1, npc.tile + 1, npc.tile - MAP_WIDTH, npc.tile + MAP_WIDTH]
  const walkable = candidates.filter(t =>
    t >= 1 && t <= MAP_WIDTH * MAP_HEIGHT &&
    tileArray[t] !== 4 && tileArray[t] !== 6
  )
  if (!walkable.length) return null
  // Pick closest to player
  const pr = Math.floor((playerPosition - 1) / MAP_WIDTH)
  const pc_ = (playerPosition - 1) % MAP_WIDTH
  return walkable.reduce((best, t) => {
    const br = Math.floor((best - 1) / MAP_WIDTH), bc = (best - 1) % MAP_WIDTH
    const tr = Math.floor((t    - 1) / MAP_WIDTH), tc = (t    - 1) % MAP_WIDTH
    const db = Math.abs(pr - br) + Math.abs(pc_ - bc)
    const dt = Math.abs(pr - tr) + Math.abs(pc_ - tc)
    return dt < db ? t : best
  })
}

function stepTowardTarget() {
  if (walkTarget === null) return
  if (playerPosition === walkTarget) { walkTarget = null; return }
  const pr = Math.floor((playerPosition - 1) / MAP_WIDTH), pc_ = (playerPosition - 1) % MAP_WIDTH
  const tr = Math.floor((walkTarget - 1) / MAP_WIDTH),    tc  = (walkTarget - 1) % MAP_WIDTH

  let key = 0
  if      (tc > pc_) key = 39
  else if (tc < pc_) key = 37
  else if (tr > pr)  key = 40
  else               key = 38

  // If blocked horizontally, try vertical and vice-versa
  const next = key === 39 ? playerPosition + 1 : key === 37 ? playerPosition - 1
             : key === 40 ? playerPosition + MAP_WIDTH : playerPosition - MAP_WIDTH
  if (tileArray[next] === 4 || tileArray[next] === 6) {
    if      (tr > pr && key !== 40) key = 40
    else if (tr < pr && key !== 38) key = 38
    else if (tc > pc_ && key !== 39) key = 39
    else if (tc < pc_ && key !== 37) key = 37
    else { walkTarget = null; return }  // truly stuck
  }
  onKey(key)
}

function walkToNpc(npc: NpcDef) {
  // If already adjacent, just toggle the bubble
  const pr = Math.floor((playerPosition - 1) / MAP_WIDTH), pc_ = (playerPosition - 1) % MAP_WIDTH
  const nr = Math.floor((npc.tile - 1) / MAP_WIDTH),       nc  = (npc.tile - 1) % MAP_WIDTH
  if (Math.abs(pr - nr) + Math.abs(pc_ - nc) <= 1) {
    activeBubble.value = activeBubble.value?.id === npc.id ? null : npc
    return
  }
  // Cancel any existing walk
  walkTarget = null
  const target = findWalkableAdjacentToNpc(npc)
  if (target !== null) {
    walkTarget = target
    stepMs.value = CHAIN_MS
    stepTowardTarget()
  }
}

const playerSprite = ref('')
const petSprite = ref('')
let petPosition1 = 1
let petWalkToggle = false  // flips each step: odd=base frame, even=walk frame
let stepToggle = 0  // alternates between 0/1 for walk frames (= classic `position` var)

const tracker = reactive({
  visible: false,
  name: '',
  level: 0,
  img: '',
  number: 0,
  shiny: false,
})

const padOn = ref(localStorage.getItem('pkw_pad') === '1')
const padVisible = ref(localStorage.getItem('pkw_pad') === '1')


// ── CSS position for map (replaces jQuery .animate) ──────────────
const mapStyle = computed(() => ({
  transform: `translate(${mapOffset.x}px, ${mapOffset.y}px)`,
  transition: moving.value ? `transform ${stepMs.value}ms linear` : 'none',
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
const petBounceY = ref(0)   // drives the hop: set to -6, then back to 0
const petStyle = computed(() => ({
  top: petPos.top + 'px',
  left: petPos.left + 'px',
  transform: `translateY(${petBounceY.value}px)`,
  transition: petTransition.value,
}))

function triggerPetHop(dur: number) {
  petBounceY.value = -3
  setTimeout(() => { petBounceY.value = 0 }, Math.round(dur * 0.4))
}

// ── Helpers ──────────────────────────────────────────────────────
const teamCount = computed(() => saveStore.pc.filter(p => p?.id).length)


function slotImg(slot: { id: number }, animated = false) {
  if (!slot.id) return ''
  return animated
    ? `/textures/Mini/Gif/${padId(slot.id)}.gif`
    : `/textures/Mini/Png/${padId(slot.id)}.png`
}

// ── Tile map — 40 rows × 24 cols, 1-indexed in tileArray ─────────
// 0=ground  1=bush/encounter  2=water  4=wall  6=fence(jumpable)  9=special
const MAP_TILES = [
//    1  2  3  4  5  6  7  8  9  10 11 12 13 14 15 16 17 18 19 20 21 22 23 24
  /* 1*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
  /* 2*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
  /* 3*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /* 4*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /* 5*/[4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /* 6*/[4, 4, 6, 6, 6, 6, 6, 6, 4, 4, 6, 6, 6, 6, 6, 6, 0, 0, 0, 0, 0, 0, 4, 4],
  /* 7*/[4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 4],
  /* 8*/[4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 4],
  /* 9*/[4, 4, 0, 0, 0, 4, 0, 0, 4, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 4],
  /*10*/[4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 4],
  /*11*/[4, 4, 6, 6, 6, 6, 6, 6, 4, 4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 4, 4],
  /*12*/[4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*13*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*14*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 4, 4],
  /*15*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 4, 4],
  /*16*/[4, 4, 4, 4, 6, 6, 6, 6, 6, 6, 4, 4, 4, 4, 4, 4, 1, 1, 1, 1, 1, 1, 4, 4],
  /*17*/[4, 4, 4, 4, 0, 0, 0, 0, 0, 0, 4, 4, 4, 4, 4, 4, 1, 1, 1, 1, 1, 1, 4, 4],
  /*18*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 4, 4],
  /*19*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*20*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*21*/[4, 4, 6, 6, 0, 6, 6, 6, 6, 0, 0, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4],
  /*22*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*23*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*24*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*25*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 4, 4],
  /*26*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 4, 4],
  /*27*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 1, 1, 1, 1, 1, 1, 6, 6, 6, 6, 4, 4],
  /*28*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 4, 4],
  /*29*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 4, 4],
  /*30*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*31*/[4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4],
  /*32*/[4, 4, 6, 6, 6, 6, 0, 0, 0, 4, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4],
  /*33*/[4, 4, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 4, 4],
  /*34*/[4, 4, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 4, 4],
  /*35*/[4, 4, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 4, 4],
  /*36*/[4, 4, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, 1, 1, 1, 1, 0, 0, 4, 4],
  /*37*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 1, 1, 4, 4, 4, 4, 4, 4, 4, 0, 9, 4],
  /*38*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 0, 0, 4, 4, 4, 4, 4, 4, 0, 2, 2, 4],
  /*39*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 0, 2, 2, 4],
  /*40*/[4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
] as const

function buildMap() {
  let n = 1
  for (let r = 0; r < MAP_HEIGHT; r++)
    for (let c = 0; c < MAP_WIDTH; c++)
      tileArray[n++] = MAP_TILES[r][c]
}

// ── Battle encounter ─────────────────────────────────────────────
const allDead = computed(() => saveStore.team.filter(s => s.id > 0).every(s => s.hp <= 0))

// Gen-1 water-type Pokémon pool
const WATER_POKEMON = [7,8,9,54,55,60,61,62,72,73,79,80,86,87,90,91,98,99,116,117,118,119,120,121,129,130,131,134,138,139,140,141]

function triggerEncounter(num: number) {
  const activeTeam = saveStore.team.filter(s => s.id > 0)
  const teamLevels = activeTeam.map(s => s.lvl)
  const rawMin = teamLevels.length ? Math.min(...teamLevels) : 2
  const maxLvl  = teamLevels.length ? Math.max(...teamLevels) : 5
  // Solo party: clamp minimum wild level to 3 so early game isn't brutal
  const minLvl  = activeTeam.length === 1 ? Math.min(rawMin, 3) : rawMin
  const lvl = Math.floor(Math.random() * (maxLvl - minLvl + 1)) + minLvl
  const isShiny = Math.floor(Math.random() * 4) === 0
  tracker.number = num
  tracker.level = lvl
  tracker.name = pokedex(num)
  tracker.img = `/textures/Art/${padId(num)}.png`
  tracker.visible = true
  tracker.shiny = isShiny
  saveStore.encounter.isTrainer   = false
  saveStore.encounter.trainerTeam = []
  saveStore.encounter.number = num
  saveStore.encounter.level  = lvl
  saveStore.encounter.shiny  = isShiny
  const id = String(num)
  if (saveStore.pokedex[id] !== 'caught') saveStore.pokedex[id] = 'seen'
  if (isShiny && saveStore.shinydex[id] !== 'caught') saveStore.shinydex[id] = 'seen'
  saveStore.save()
}

function checkBattle() {
  if (allDead.value) { tracker.visible = false; return }
  const tile = tileArray[playerPosition]

  if (tile === 1) {
    // Grass — any Pokémon, 1-in-3 chance
    if (Math.floor(Math.random() * 3) === 0) {
      triggerEncounter(Math.floor(Math.random() * 151) + 1)
    } else { tracker.visible = false }

  } else if (tile === 2) {
    // Water — only water-type Pokémon, 1-in-3 chance
    if (Math.floor(Math.random() * 3) === 0) {
      triggerEncounter(WATER_POKEMON[Math.floor(Math.random() * WATER_POKEMON.length)])
    } else { tracker.visible = false }

  } else if (tile !== 9) {
    // Tile 9 (fishing) is handled by the fishing interval cycle — do nothing here
    tracker.visible = false
  }
}

// ── Fishing cycle (tile 9) — new Pokémon rolls every 3 s ─────────
let fishingInterval:     ReturnType<typeof setInterval> | null = null
let fishingDotsInterval: ReturnType<typeof setInterval> | null = null
const fishingDots = ref(1)   // 1 → 2 → 3 → 1 …

function fishingRoll() {
  if (tileArray[playerPosition] !== 9 || allDead.value) {
    tracker.visible = false
    return
  }
  // 1-in-3 chance, same as bush
  if (Math.floor(Math.random() * 3) === 0) {
    triggerEncounter(WATER_POKEMON[Math.floor(Math.random() * WATER_POKEMON.length)])
  } else {
    tracker.visible = false
  }
}

function startFishingCycle() {
  if (fishingInterval !== null) return

  // Always face south while fishing
  facingFrame = 1
  setPlayerSprite(1)

  // Dot animation — cycles . → .. → ... every 600 ms
  fishingDots.value = 1
  fishingDotsInterval = setInterval(() => {
    fishingDots.value = fishingDots.value >= 3 ? 1 : fishingDots.value + 1
  }, 600)

  fishingRoll()   // immediate first roll
  fishingInterval = setInterval(fishingRoll, 3000)
}

function stopFishingCycle() {
  const wasRunning = fishingInterval !== null
  if (fishingInterval !== null) {
    clearInterval(fishingInterval)
    fishingInterval = null
  }
  if (fishingDotsInterval !== null) {
    clearInterval(fishingDotsInterval)
    fishingDotsInterval = null
  }
  // Only hide the tracker if we were actually fishing — don't interfere
  // with grass/water encounters that checkBattle() may have just triggered
  if (wasRunning) tracker.visible = false
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

// ── Movement ──────────────────────────────────────────────────────
function finishStep() {
  moving.value = false
  savePosition()
  if (pendingKey !== null) {
    const k = pendingKey
    pendingKey = null
    stepMs.value = CHAIN_MS
    onKey(k)
  } else if (walkTarget !== null) {
    stepMs.value = CHAIN_MS
    stepTowardTarget()
  } else {
    stepMs.value = STEP_MS
  }
}

function onKey(keyCode: number) {
  if (moving.value) {
    pendingKey = keyCode   // remember one queued step
    return
  }

  // Capture current duration so all callbacks within this step use a consistent value,
  // even if stepMs.value changes when the next chained step starts mid-flight.
  const dur = stepMs.value

  // Trigger pet hop and flip walk frame
  triggerPetHop(dur)
  petWalkToggle = !petWalkToggle

  if (keyCode === 38) {
    // UP
    if (stepToggle === 0) { setPlayerSprite(11); stepToggle++ }
    else { setPlayerSprite(12); stepToggle = 0 }
    facingFrame = 10
    currentDir.value = 'up'
    setTimeout(() => setPlayerSprite(10), dur * 0.55)

    if (tileArray[playerPosition - MAP_WIDTH] !== 4 && tileArray[playerPosition - MAP_WIDTH] !== 6) {
      moving.value = true
      playerPosition -= MAP_WIDTH
      mapOffset.y += TILE_SIZE
      checkBattle()
      broadcastMove(playerPosition, 'up')
      petTransition.value = `top ${dur}ms linear, left ${dur}ms linear`
      petPosition1 = petWalkToggle ? 8 : 7
      updatePetSprite()
      petPos.top = playerCenter.top + TILE_SIZE + 4
      petPos.left = middle.left
      pet1ZIndex.value = 2
      setTimeout(finishStep, dur)
    }

  } else if (keyCode === 40) {
    // DOWN
    if (stepToggle === 0) { setPlayerSprite(2); stepToggle++ }
    else { setPlayerSprite(3); stepToggle = 0 }
    facingFrame = 1
    currentDir.value = 'down'
    setTimeout(() => setPlayerSprite(1), dur * 0.55)

    if (tileArray[playerPosition + MAP_WIDTH] !== 4 && tileArray[playerPosition + MAP_WIDTH] !== 6) {
      moving.value = true
      playerPosition += MAP_WIDTH
      mapOffset.y -= TILE_SIZE
      checkBattle()
      broadcastMove(playerPosition, 'down')
      petTransition.value = `top ${dur}ms linear, left ${dur}ms linear`
      petPosition1 = petWalkToggle ? 2 : 1
      updatePetSprite()
      petPos.top = playerCenter.top - TILE_SIZE + 16
      petPos.left = middle.left
      pet1ZIndex.value = 0
      setTimeout(finishStep, dur)
    } else if (tileArray[playerPosition + MAP_WIDTH] === 6) {
      moving.value = true
      playerPosition += MAP_WIDTH * 2
      mapOffset.y -= TILE_SIZE * 2
      broadcastMove(playerPosition, 'down')
      petTransition.value = `top ${dur}ms linear, left ${dur}ms linear`
      petPosition1 = petWalkToggle ? 2 : 1
      updatePetSprite()
      petPos.top = playerCenter.top - TILE_SIZE + 16
      petPos.left = middle.left
      pet1ZIndex.value = 0
      setTimeout(finishStep, dur)
    }

  } else if (keyCode === 37) {
    // LEFT
    setPlayerSprite(5)
    facingFrame = 4
    currentDir.value = 'left'
    setTimeout(() => setPlayerSprite(4), dur * 0.55)

    if (tileArray[playerPosition - 1] !== 4 && tileArray[playerPosition - 1] !== 6) {
      moving.value = true
      playerPosition -= 1
      mapOffset.x += TILE_SIZE
      checkBattle()
      broadcastMove(playerPosition, 'left')
      petTransition.value = `top ${dur}ms linear, left ${dur}ms linear`
      petPosition1 = petWalkToggle ? 4 : 3
      updatePetSprite()
      petPos.left = middle.left + 38
      petPos.top = middle.top + 30
      setTimeout(finishStep, dur)
    }

  } else if (keyCode === 39) {
    // RIGHT
    setPlayerSprite(9)
    facingFrame = 7
    currentDir.value = 'right'
    setTimeout(() => setPlayerSprite(7), dur * 0.55)

    if (tileArray[playerPosition + 1] !== 4 && tileArray[playerPosition + 1] !== 6) {
      moving.value = true
      playerPosition += 1
      mapOffset.x -= TILE_SIZE
      checkBattle()
      broadcastMove(playerPosition, 'right')
      petTransition.value = `top ${dur}ms linear, left ${dur}ms linear`
      petPosition1 = petWalkToggle ? 6 : 5
      updatePetSprite()
      petPos.left = middle.left - 38
      petPos.top = middle.top + 30
      setTimeout(finishStep, dur)
    }
  }
}

function savePosition() {
  saveStore.mapPos = playerPosition
  saveStore.mapDir = facingFrame
  playerTileRef.value = playerPosition
  saveStore.save()
}

function handleKeyDown(e: KeyboardEvent) {
  if (e.key === '\\') { debugTiles.value = !debugTiles.value; return }
  if ([37, 38, 39, 40].includes(e.keyCode)) {
    e.preventDefault()
    walkTarget = null   // manual key cancels walk-to
    onKey(e.keyCode)
  }
}

// ── Nav controls ─────────────────────────────────────────────────
function togglePad() {
  padOn.value = !padOn.value
  padVisible.value = padOn.value
  localStorage.setItem('pkw_pad', padOn.value ? '1' : '0')
}

// ── D-pad continuous press ────────────────────────────────────────
let padTimer: ReturnType<typeof setInterval> | null = null

function startPad(keyCode: number) {
  stopPad()
  onKey(keyCode)                          // fire immediately
  padTimer = setInterval(() => onKey(keyCode), 80)  // then repeat at 80ms
}

function stopPad() {
  if (padTimer !== null) { clearInterval(padTimer); padTimer = null }
}

async function doLogout() {
  await authStore.logout()
  router.push('/')
}

// ── Multiplayer helpers ───────────────────────────────────────────
async function doAutoConnect() {
  const followerId = saveStore.team[0]?.id ?? 1
  await autoConnect({
    uuid:   getOrCreateUUID(),
    name:   saveStore.playerData.nome || 'Trainer',
    sprite: String(saveStore.playerData.sprite),
    tile:   playerPosition,
    dir:    currentDir.value,
    followerId,
    shiny:  saveStore.shinydex?.[String(followerId)] === 'caught',
  })
  // Re-broadcast follower in case it changed while away (e.g. swapped lead in PC)
  const lead = saveStore.team[0]
  if (lead?.id > 0) {
    broadcastFollower(lead.id, saveStore.shinydex?.[String(lead.id)] === 'caught')
  }
}

function onFirstRun({ name, starterId }: { name: string; starterId: number }) {
  showFirstRun.value = false
  const isNew = !saveStore.hasSave()
  if (isNew) {
    saveStore.initNewGame(name, saveStore.playerData.sprite, starterId)
  } else {
    saveStore.playerData.nome = name
    saveStore.save()
  }
  doAutoConnect()
}

// ── Healing & team management ─────────────────────────────────────
function calcMaxHP(baseHP: number, level: number) {
  return Math.floor(2 * baseHP * level / 100) + level + 10
}
function slotMaxHP(slot: { id: number; lvl: number }) {
  return calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
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
const dragFrom    = ref(-1)
const hoveredSlot = ref(-1)
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
  // Broadcast new follower if slot 0 changed
  if (a === 0 || i === 0) {
    const lead = saveStore.team[0]
    if (lead?.id > 0) {
      broadcastFollower(lead.id, saveStore.shinydex?.[String(lead.id)] === 'caught')
    }
  }
}

// ── Pet walking animation (identical to game.php setInterval) ────
let petInterval: ReturnType<typeof setInterval>
const slot1Id = computed(() => saveStore.team[0]?.id ?? 1)
const slot1Shiny = computed(() => saveStore.team[0]?.shiny ?? false)

const SHINY_FOLDERS: Record<number, string> = {
  1: 'Front', 2: 'Front2',
  3: 'Left',  4: 'Left2',
  5: 'Right', 6: 'Right2',
  7: 'Back',  8: 'Back2',
}

function updatePetSprite() {
  if (slot1Shiny.value) {
    const folder = SHINY_FOLDERS[petPosition1] ?? 'Front'
    petSprite.value = `/textures/Overworld/Shiny/${folder}/${slot1Id.value}.png`
  } else {
    petSprite.value = `/textures/Overworld/Normal/${petPosition1}/${slot1Id.value}.png`
  }
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

  // Default spawn offset (tile 468, row 19 col 11 — 0-indexed)
  const SPAWN = 468
  const defaultRow = Math.floor((SPAWN - 1) / MAP_WIDTH)
  const defaultCol = (SPAWN - 1) % MAP_WIDTH

  const savedRow = Math.floor((playerPosition - 1) / MAP_WIDTH)
  const savedCol = (playerPosition - 1) % MAP_WIDTH

  const rowDelta = savedRow - defaultRow
  const colDelta = savedCol - defaultCol

  mapOffset.y = ji / 2 - 960 - rowDelta * TILE_SIZE
  mapOffset.x = je / 2 - 577.5 - colDelta * TILE_SIZE

  // Middle1/Middle2 — exact port: Middle1=ji/2-78-10, Middle2=je/2-50.5+3.5
  // -8 offset in both axes to re-centre the 64×64 sprite on the 48px tile
  middle.top = ji / 2 - 78 - 10 - 8
  middle.left = je / 2 - 50.5 + 3.5 - 8

  // Restore pet position + sprite based on saved facing direction
  // facingFrame: 1=down, 4=left, 7=right, 10=up
  if (facingFrame === 10) {
    // UP — pet is behind (above) player, higher z-index
    petPosition1 = 7
    petPos.top = playerCenter.top + TILE_SIZE + 4
    petPos.left = middle.left
    pet1ZIndex.value = 2
  } else if (facingFrame === 4) {
    // LEFT — pet is to the right of player
    petPosition1 = 3
    petPos.top = middle.top + 30
    petPos.left = middle.left + 38
  } else if (facingFrame === 7) {
    // RIGHT — pet is to the left of player
    petPosition1 = 5
    petPos.top = middle.top + 30
    petPos.left = middle.left - 38
  } else {
    // DOWN (default)
    petPosition1 = 1
    petPos.top = playerCenter.top - TILE_SIZE + 16
    petPos.left = middle.left
    pet1ZIndex.value = 0
  }

  // Initial sprites — restore saved facing direction
  setPlayerSprite(facingFrame)
  updatePetSprite()

  // Check if player spawned on a special tile
  if (tileArray[playerPosition] === 9) startFishingCycle()

  // Mobile pad visibility
  if (window.innerWidth < 980) padVisible.value = true

  // Keydown listener
  window.addEventListener('keydown', handleKeyDown)

  // Multiplayer — show first-run modal for new players, otherwise auto-connect silently
  if (isFirstSession()) {
    showFirstRun.value = true
  } else {
    doAutoConnect()
  }

  // Pet idle animation — only runs when the player is standing still
  petInterval = setInterval(() => {
    if (moving.value) return   // step-based walking handles sprite while moving
    if (petPosition1 % 2 !== 0) {
      petPosition1++
    } else {
      petPosition1--
    }
    updatePetSprite()
    triggerPetHop(500)
  }, 500)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  clearInterval(petInterval)
  stopPad()
  stopFishingCycle()
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
  transition: transform 80ms ease-out;
}
.pet > img {
  image-rendering: pixelated;
  width: 64px;
  height: 64px;
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

/* TrackerBush — centered bottom, image left + ball right */
#TrackerBush {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 600;                        /* above joystick (490) */
  background: #333;
  border: 3px solid #000;
  border-bottom: none;
  border-radius: 12px 12px 0 0;
  color: white;
  padding: 10px 14px 12px;
  min-width: 280px;
}
.tracker-body {
  display: flex;
  align-items: center;
  gap: 14px;
}
.trackerdiv {
  width: 110px;
  height: 110px;
  border: 3px solid black;
  flex-shrink: 0;
  border-radius: 50%;
  overflow: hidden;
  background-color: white;
}
.trackerdiv > img { width: 100%; height: 100%; object-fit: cover; }
.tracker-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  flex: 1;
}
#trackername { margin: 0; padding: 0; font-size: 13px; }
#trackerlvl  { margin: 0; padding: 0; font-size: 11px; color: #ccc; }
.tracker-ball {
  width: 54px;
  cursor: pointer;
  opacity: 0.85;
  margin-top: 6px;
  transition: transform 0.1s, opacity 0.1s;
}
.tracker-ball:hover { opacity: 1; transform: scale(1.12); }
.tracker-status { font-size: 11px; font-weight: bold; }
.tracker-name-ball {
  width: 14px;
  height: 14px;
  image-rendering: pixelated;
  vertical-align: middle;
  margin-left: 5px;
  opacity: 0.9;
}
.tracker-name-ball--shiny {
  filter: sepia(1) saturate(4) hue-rotate(10deg) brightness(1.3);  /* golden tint */
}
.tracker-seen { color: #aaa; }
.tracker-new { color: #22cc66; }

/* Player tracker + stats — hidden for now */
#PlayerTracker,
#PlayerStats { display: none; }

/* ── Party panel — positional anchor only ── */
.slots-panel {
  position: fixed;
  bottom: 8px;
  right: 8px;
  z-index: 2;
}

/* ── Party cards grid ── */
.slots {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
  width: 400px;
}

/* ── Outer wrapper: grid item, events, pokéball anchor ── */
.slot-outer {
  position: relative;
  cursor: pointer;
  user-select: none;
  /* drop-shadow applied here so it's outside the inner clip-path */
  filter: drop-shadow(0 3px 0 rgba(0,0,0,0.55));
  transition: filter 0.12s;
}
.slot-outer:hover  { filter: drop-shadow(0 3px 0 rgba(0,0,0,0.55)) brightness(1.13); }
.slot-outer:active { filter: drop-shadow(0 3px 0 rgba(0,0,0,0.55)) brightness(0.88); cursor: grabbing; }
.slot-outer.dragging { opacity: 0.35; }

/* Right column sits slightly higher */
.slots > .slot-outer:nth-child(even) {
  transform: translateY(-8px);
}

/* ── Pokéball — sits at the cut corner, outside the clip ── */
.slot-ball {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  image-rendering: pixelated;
  opacity: 0.90;
  z-index: 2;
}

/* ── Inner card — clipped to cut-corner lozenge ── */
.slot-entry {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px 6px 8px;
  /* layered background: diagonal border line + dark green fill */
  background:
    linear-gradient(135deg,
      transparent        22px,
      #3a6030            22px,
      #3a6030            24px,
      transparent        24px
    ),
    linear-gradient(180deg, #2a4a22 0%, #162e10 100%);
  /* clip to pentagon with cut top-left corner */
  clip-path: polygon(24px 0, 100% 0, 100% 100%, 0 100%, 0 24px);
  min-width: 0;
  overflow: hidden;
}

/* Icons row — top-right: evo arrow + shiny sparkle */
.slot-icons {
  position: absolute;
  top: 3px;
  right: 5px;
  display: flex;
  align-items: center;
  gap: 3px;
}
.slot-shiny-icon {
  font-size: 10px;
  line-height: 1;
  filter: drop-shadow(0 0 3px #ffc000);
  z-index: 1;
}

/* Sprite — only animates on outer hover */
.slot-sprite {
  width: 48px;
  height: 48px;
  image-rendering: pixelated;
  flex-shrink: 0;
  margin-left: 14px;   /* clear the cut corner */
  margin-top: 2px;
  transition: transform 0.18s ease;
}
.slot-outer:hover .slot-sprite {
  transform: translateY(-5px);
}

/* Info column */
.slot-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.slot-name {
  display: flex;
  align-items: center;
  gap: 4px;
  padding-right: 14px;
  overflow: visible;
}
.slot-name-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #fff;
  text-shadow: 1px 1px 0 rgba(0,0,0,0.55);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}
.slot-evo-badge {
  font-size: 10px;
  line-height: 1;
  color: #c084fc;
  text-shadow: 0 0 4px #fff, 0 0 8px #fff, 0 0 14px #fff;
  animation: evo-pulse 1.4s ease-in-out infinite;
}
@keyframes evo-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}

.slot-lv {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: rgba(255,255,255,0.80);
}
.slot-lv b { font-size: 6px; }

/* Glued HP + XP bars */
.slot-bars {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 2px;
}
.slot-bar-row {
  display: flex;
  align-items: center;
  gap: 3px;
}
.slot-bar-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: rgba(255,255,255,0.88);
  flex-shrink: 0;
  min-width: 10px;
}

.slot-hp-bar {
  flex: 1;
  height: 5px;
  background: rgba(0,0,0,0.35);
  border-radius: 1px;
  overflow: hidden;
}
.slot-hp-fill {
  height: 100%;
  border-radius: 1px;
  transition: width 0.3s, background-color 0.3s;
}
.slot-hp-vals {
  font-family: 'Press Start 2P', monospace;
  font-size: 4px;
  color: rgba(255,255,255,0.85);
  letter-spacing: 0.2px;
  white-space: nowrap;
}
.slot-hp-sep { color: rgba(255,255,255,0.45); }

.slot-xp-bar {
  flex: 1;
  height: 5px;
  background: rgba(0,0,0,0.35);
  border-radius: 1px;
  overflow: hidden;
}
.slot-xp-fill {
  height: 100%;
  background: linear-gradient(90deg, #d4a800, #f0d000);
  border-radius: 1px;
  transition: width 0.4s;
}



/* ── NPC layer — same transform as .loveit/.map2, sits above overlay ── */
.npc-layer {
  position: fixed;
  top: 0; left: 0;
  z-index: 4;
  will-change: transform;
  pointer-events: none;
}
.npc {
  position: absolute;
  width: 48px;
  height: 48px;
  cursor: pointer;
  pointer-events: all;
  transition: filter 0.15s;
}
.npc > img {
  width: 48px;
  height: 48px;
  object-fit: contain;
  image-rendering: pixelated;
  display: block;
}


/* ── NPC Speech Bubble — floats above the NPC sprite ── */
.npc-bubble {
  position: absolute;
  bottom: calc(100% + 10px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  width: 200px;
  background: #fff;
  border: 3px solid #181830;
  border-radius: 6px;
  box-shadow: 3px 3px 0 #181830;
  padding: 10px 12px 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  pointer-events: all;
}
/* Tail pointing down toward the NPC */
.npc-bubble::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 8px solid transparent;
  border-top-color: #181830;
}
.npc-bubble::before {
  content: '';
  position: absolute;
  top: calc(100% - 2px);
  left: 50%;
  transform: translateX(-50%);
  border: 7px solid transparent;
  border-top-color: #fff;
  z-index: 1;
}
.bubble-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #c82020;
  letter-spacing: 0.4px;
  padding-bottom: 5px;
  border-bottom: 2px solid #e0e8f0;
}
.bubble-text {
  font-size: 11px;
  color: #181830;
  line-height: 1.55;
}
.bubble-fight-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  padding: 7px 10px;
  background: #c82020;
  color: #fff;
  border: none;
  border-radius: 3px;
  box-shadow: 0 3px 0 #7a1010;
  cursor: pointer;
  align-self: flex-start;
  transition: opacity 0.15s, transform 0.1s;
  letter-spacing: 0.4px;
  margin-top: 2px;
}
.bubble-fight-btn:hover { opacity: 0.85; }
.bubble-fight-btn:active { transform: translateY(2px); box-shadow: 0 1px 0 #7a1010; }
.bubble-win-count {
  font-size: 10px;
  color: #c82020;
  font-style: italic;
  font-weight: 600;
}
.bubble-close {
  position: absolute;
  top: 4px; right: 6px;
  background: none;
  border: none;
  color: #aaa;
  font-size: 12px;
  cursor: pointer;
  line-height: 1;
  padding: 2px 3px;
  transition: color 0.15s;
}
.bubble-close:hover { color: #181830; }

.bubble-enter-active { animation: bubble-in 0.18s ease-out; }
.bubble-leave-active { animation: bubble-in 0.12s ease-in reverse; }
@keyframes bubble-in {
  from { opacity: 0; transform: translateX(-50%) translateY(6px); }
  to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* Water / surf HUD */
.water-hud {
  position: fixed;
  top: 88px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 5;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  background: rgba(14, 60, 140, 0.88);
  border: 2px solid rgba(80, 160, 255, 0.7);
  border-radius: 20px;
  backdrop-filter: blur(4px);
  box-shadow: 0 3px 12px rgba(0,0,0,0.4);
  pointer-events: none;
}
.water-hud-icon { font-size: 14px; }
.fishing-rod-icon { width: 18px; height: 18px; image-rendering: pixelated; }
.water-hud-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #80c8ff;
  letter-spacing: 1px;
}
.fishing-hud {
  background: rgba(20, 80, 20, 0.88);
  border-color: rgba(80, 200, 80, 0.7);
  min-width: 130px;   /* stop layout shift as dots grow */
}
.fishing-hud .water-hud-lbl { color: #90ee90; }
.fishing-lbl {
  display: inline-flex;
  align-items: baseline;
  gap: 0;
  font-variant-numeric: tabular-nums;
}
.fishing-dots {
  display: inline-block;
  min-width: 18px;     /* holds '...' without shifting siblings */
  letter-spacing: 0;
}
.water-hud-enter-active { animation: wh-in 0.2s ease-out; }
.water-hud-leave-active { animation: wh-in 0.15s ease-in reverse; }
@keyframes wh-in { from { opacity: 0; transform: translateX(-50%) translateY(-6px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }

/* ── Debug tile overlay ── */
.tile-debug-layer {
  position: fixed;
  top: 0; left: 0;
  width: 1155px;
  height: 1920px;
  overflow: hidden;
  pointer-events: none;
  z-index: 3;
}
.tile-debug-cell {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
  font-family: monospace;
  border: 1px solid rgba(255,255,255,0.12);
  box-sizing: border-box;
  color: rgba(255,255,255,0.9);
  text-shadow: 0 0 3px #000, 0 0 6px #000;
}
.tdbg-0 { background: rgba(0,   200,  0,   0.18); }  /* ground  — green  */
.tdbg-1 { background: rgba(0,   160,  0,   0.45); }  /* bush    — dark green */
.tdbg-2 { background: rgba(30,  120, 255,  0.45); }  /* water   — blue  */
.tdbg-4 { background: rgba(180,  40,  40,  0.45); }  /* wall    — red   */
.tdbg-6 { background: rgba(220, 160,   0,  0.45); }  /* fence   — amber */
.tdbg-9 { background: rgba(0,   200, 160,  0.55); }  /* fishing — teal  */

/* Mobile — medium (tablet / large phone) */
@media screen and (max-width: 980px) {
  #pad-toggle { bottom: 8px; right: 8px; }
  #TrackerBush {
    min-width: 0;
    width: calc(100vw - 160px);   /* leave room for joystick on the left */
    left: 50%;
    transform: translateX(-50%);
  }
  /* #PlayerTracker and #PlayerStats already hidden globally */
}

/* ── Mobile party strip ─────────────────────────────────────────── */
.mob-party {
  display: none; /* shown only on mobile via media query */
}

/* Mobile — small phone */
@media screen and (max-width: 600px) {
  /* Hide desktop party panel */
  .slots-panel { display: none !important; }

  /* Show mobile party strip */
  .mob-party {
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: fixed;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 400;
  }

  .mob-poke-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    /* Conic-gradient HP ring */
    background: conic-gradient(
      var(--ring-color) var(--ring-pct),
      rgba(0, 0, 0, 0.55) 0deg
    );
    padding: 4px;
    cursor: pointer;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.6));
    transition: filter 0.15s;
  }
  .mob-poke-wrap:active { filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4)) brightness(0.85); }

  .mob-poke-ring {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(20, 45, 20, 0.88);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
  }

  .mob-poke-img {
    width: 36px;
    height: 36px;
    image-rendering: pixelated;
    object-fit: contain;
  }

  .mob-evo {
    position: absolute;
    bottom: 0;
    right: 0;
    font-size: 9px;
    color: #c084fc;
    text-shadow: 0 0 4px #fff;
    line-height: 1;
  }
}

/* Mobile — TrackerBush: narrower to avoid joystick overlap */
@media screen and (max-width: 500px) {
  #TrackerBush {
    width: calc(100vw - 150px);
    left: 50%;
    transform: translateX(-50%);
    min-width: 0;
  }
}

/* ── Multiplayer ──────────────────────────────────────────────── */
.other-players-layer {
  position: fixed; top: 0; left: 0; z-index: 4; pointer-events: none;
}
.other-player {
  position: absolute;
  overflow: visible;
  /* Smoothly interpolate between tile positions (matches CHAIN_MS = 180ms) */
  transition: left 180ms linear, top 180ms linear;
}
/* Trainer: same width as local player sprite, block so it sits in flow */
.other-trainer {
  display: block;
  width: 55px; image-rendering: pixelated;
}
/* Follower: same 64×64 as .pet > img on the local player */
.other-follower {
  position: absolute;
  width: 64px; height: 64px;
  image-rendering: pixelated;
}
/* Bounce when walking — mirrors local triggerPetHop (-3px then back) */
.other-follower.walking {
  animation: other-pet-hop 280ms ease-out;
}
@keyframes other-pet-hop {
  0%   { transform: translateY(0); }
  40%  { transform: translateY(-3px); }
  100% { transform: translateY(0); }
}
.other-player-name {
  position: absolute; top: -18px; left: 50%; transform: translateX(-50%);
  font-size: 9px; color: #fff; white-space: nowrap;
  background: rgba(0,0,0,0.55); border-radius: 4px; padding: 1px 5px;
  font-family: 'Pokemon GB', monospace; z-index: 2;
}


.mp-toast {
  position: fixed; bottom: 120px; left: 50%; transform: translateX(-50%);
  background: rgba(6,14,22,0.92); border: 1px solid #2a4268;
  color: #c8e0f0; font-size: 11px; padding: 6px 14px;
  font-family: 'Pokemon GB', monospace; z-index: 300; pointer-events: none;
  white-space: nowrap;
}
.mp-toast-enter-active, .mp-toast-leave-active { transition: opacity 0.3s; }
.mp-toast-enter-from, .mp-toast-leave-to { opacity: 0; }
</style>
