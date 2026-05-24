<template>
  <AppHeader label="JOGO" @nav-click="router.push('/game')" />

  <main>
    <div class="pc-wrap">

      <!-- Header -->
      <div class="pc-header">
        <span class="pc-title">BILL'S PC</span>
        <span class="pc-sub">BOX 1</span>
        <span class="blink">▮</span>
      </div>

      <div class="pc-layout">

        <!-- PC Box -->
        <div class="box-panel">
          <div class="box-grid">
            <div
              v-for="cell in pcCells"
              :key="`pc-${cell}`"
              class="box-cell"
              :class="{ 'drop-glow': dragOver === `pc-${cell - 1}` }"
              @dragover.prevent="dragOver = `pc-${cell - 1}`"
              @dragleave="dragOver = ''"
              @drop="onDropToPC(cell - 1)"
            >
              <template v-if="saveStore.pc[cell - 1]?.id">
                <div
                  class="poke-card"
                  :class="{ 'card-dragging': dragSrc === `pc-${cell - 1}` }"
                  draggable="true"
                  @dragstart="onDragStart('pc', cell - 1)"
                  @dragend="dragSrc = ''; dragOver = ''"
                >
                  <div class="card-type-bar" :style="{ background: typeBgH(saveStore.pc[cell - 1].id) }"></div>
                  <button class="gear-btn gear-btn-card" @click.stop="openMoveEditor(saveStore.pc[cell - 1], 'pc', cell - 1)" title="Manage"><i class="fa-solid fa-gear"></i></button>
                  <div class="type-badges">
                    <div v-for="t in getTypes(saveStore.pc[cell - 1].id)" :key="t"
                         class="type-badge" :style="{ backgroundColor: TYPE_COLORS[t] ?? '#888' }">
                      {{ TYPE_NAMES[t] ?? '???' }}
                    </div>
                  </div>
                  <img :src="`/textures/Mini/Png/${padId(saveStore.pc[cell - 1].id)}.png`" alt="" />
                  <div class="card-name">
                    <span v-if="saveStore.pc[cell - 1].shiny" class="shiny-star">✨</span>
                    {{ saveStore.pc[cell - 1].nickname || pokedex(saveStore.pc[cell - 1].id) }}
                  </div>
                  <div class="card-lvl">LV.{{ saveStore.pc[cell - 1].lvl }}</div>
                  <div class="bar-row">
                    <span class="bar-label">{{ saveStore.pc[cell - 1].hp }}/{{ calcMaxHP(saveStore.pc[cell - 1].id, saveStore.pc[cell - 1].lvl) }}</span>
                    <div class="card-hp-bar">
                      <div class="card-hp-fill"
                        :style="{ width: hpPct(saveStore.pc[cell - 1]) * 100 + '%',
                                  backgroundColor: hpColor(hpPct(saveStore.pc[cell - 1])) }">
                      </div>
                    </div>
                  </div>
                  <div class="bar-row">
                    <span class="bar-label">{{ saveStore.pc[cell - 1].xp }}/{{ xpToNext(saveStore.pc[cell - 1].lvl) }}</span>
                    <div class="card-xp-bar">
                      <div class="card-xp-fill" :style="{ width: xpPct(saveStore.pc[cell - 1]) * 100 + '%' }"></div>
                    </div>
                  </div>
                  <button v-if="saveStore.pc[cell - 1].pendingEvo"
                    class="evolve-btn evolve-btn-card"
                    @click.stop="evolveSlot(saveStore.pc[cell - 1])">
                    ★ EVOLVE
                  </button>
                </div>
              </template>
              <template v-else>
                <div class="cell-empty">·</div>
              </template>
            </div>
          </div>

          <!-- Box footer -->
          <div class="box-footer">
            <span>{{ saveStore.pc.filter(p => p?.id).length }} Pokémon in storage</span>
          </div>
        </div>

        <!-- Team Panel -->
        <div class="team-panel">
          <div class="team-header">
            <span>PARTY</span>
            <button class="heal-all-btn" @click="healAll" title="Heal All">♥ HEAL</button>
          </div>
          <div class="xpshare-row">
            <span class="xpshare-label">XP SHARE</span>
            <button
              class="xpshare-toggle"
              :class="{ active: saveStore.xpShare }"
              @click="saveStore.xpShare = !saveStore.xpShare; saveStore.save()"
            >
              {{ saveStore.xpShare ? 'ON' : 'OFF' }}
            </button>
          </div>
          <div class="xpshare-row">
            <span class="xpshare-label">XP ×</span>
            <div class="xpmult-controls">
              <button class="xpmult-btn" @click="adjustMult(-0.5)">−</button>
              <span class="xpmult-val">{{ saveStore.xpMultiplier }}×</span>
              <button class="xpmult-btn" @click="adjustMult(0.5)">+</button>
            </div>
          </div>
          <div class="team-list">
            <div
              v-for="(slot, i) in saveStore.team"
              :key="`team-${i}`"
              class="team-slot"
              :class="{
                'slot-empty': !slot.id,
                'slot-drop': dragOver === `team-${i}`,
                'slot-dragging': dragSrc === `team-${i}`,
              }"
              :style="slot.id ? slotBorder(slot.id) : {}"
              :draggable="!!slot.id"
              @dragstart="slot.id ? onDragStart('team', i) : undefined"
              @dragover.prevent="dragOver = `team-${i}`"
              @dragleave="dragOver = ''"
              @drop="onDropToTeam(i)"
              @dragend="dragSrc = ''; dragOver = ''"
            >
              <template v-if="slot.id">
                <img :src="`/textures/Mini/Gif/${padId(slot.id)}.gif`" alt="" />
                <div class="slot-info">
                  <div class="slot-name">
                    <span v-if="slot.shiny" class="shiny-star">✨</span>
                    {{ slot.nickname || pokedex(slot.id) }}
                  </div>
                  <div class="bar-row">
                    <span class="bar-label">{{ slot.hp }}/{{ calcMaxHP(slot.id, slot.lvl) }}</span>
                    <div class="slot-hp-bar">
                      <div class="slot-hp-fill"
                        :style="{ width: hpPct(slot) * 100 + '%',
                                  backgroundColor: hpColor(hpPct(slot)) }">
                      </div>
                    </div>
                  </div>
                  <div class="bar-row">
                    <span class="bar-label">{{ slot.xp }}/{{ xpToNext(slot.lvl) }}</span>
                    <div class="slot-xp-bar">
                      <div class="slot-xp-fill" :style="{ width: xpPct(slot) * 100 + '%' }"></div>
                    </div>
                  </div>
                </div>
                <div class="slot-right">
                  <div class="type-chips">
                    <div v-for="t in getTypes(slot.id)" :key="t"
                         class="type-chip" :style="{ backgroundColor: TYPE_COLORS[t] ?? '#888' }">
                      {{ TYPE_NAMES[t] ?? '???' }}
                    </div>
                  </div>
                  <div v-if="slot.pendingEvo" class="evo-warning" :title="`Can evolve into ${pokedex(slot.pendingEvo)}!`">
                    ★ EVO
                  </div>
                  <button class="gear-btn" @click.stop="openMoveEditor(slot, 'team', i)" title="Manage"><i class="fa-solid fa-gear"></i></button>
                </div>
              </template>
              <template v-else>
                <div class="slot-empty-label">— EMPTY —</div>
              </template>
            </div>
          </div>
          <div class="party-count">{{ activeTeam.length }}/6 in party</div>
        </div>
      </div>

      <!-- Tip -->
      <div class="pc-tip">
        <span class="blink-slow">▶</span> DRAG Pokémon to move between PC and Party
      </div>
    </div>

    <!-- Move editor modal -->
    <div v-if="moveEditor.show" class="modal-overlay" @click.self="moveEditor.show = false">
      <div class="modal-box move-modal">

        <!-- Header row -->
        <div class="move-modal-header">
          <div class="modal-title">
            {{ pokedex(moveEditor.slot?.id ?? 0) }} — MOVE SET
            <span class="move-modal-sub">{{ moveEditor.slot?.lvl ? `LV.${moveEditor.slot.lvl}` : '' }}</span>
          </div>
          <div class="view-toggle">
            <button class="view-btn" :class="{ active: moveEditor.view === 'list' }" @click="moveEditor.view = 'list'" title="List view">☰</button>
            <button class="view-btn" :class="{ active: moveEditor.view === 'grid' }" @click="moveEditor.view = 'grid'" title="Grid view">⊞</button>
          </div>
        </div>

        <!-- Nickname -->
        <div class="nickname-row">
          <span class="nickname-label">NICKNAME</span>
          <input
            v-model="moveEditor.nickname"
            class="nickname-input"
            :placeholder="pokedex(moveEditor.slot?.id ?? 0)"
            maxlength="12"
            spellcheck="false"
            autocomplete="off"
          />
        </div>

        <!-- Active move slots preview -->
        <div class="active-moves-row">
          <div v-for="n in 4" :key="n" class="active-move-slot"
               :class="{ filled: moveEditor.active[n-1] }">
            <template v-if="moveEditor.active[n-1]">
              <span class="am-name">{{ MOVES[String(moveEditor.active[n-1])]?.name }}</span>
              <span class="am-type-badge" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(moveEditor.active[n-1])]?.type ?? 1] ?? '#888' }">
                {{ TYPE_NAMES[MOVES[String(moveEditor.active[n-1])]?.type ?? 1] ?? '???' }}
              </span>
            </template>
            <template v-else><span class="am-empty">—</span></template>
          </div>
        </div>

        <!-- Move pool -->
        <div class="move-list" :class="{ 'move-list-grid': moveEditor.view === 'grid' }">
          <template v-for="entry in moveEditor.learnset" :key="entry.id">

            <!-- ── LIST ROW ── -->
            <div v-if="moveEditor.view === 'list'"
                 class="move-row"
                 :class="{
                   'move-active': moveEditor.active.includes(entry.id),
                   'move-locked': entry.level > (moveEditor.slot?.lvl ?? 0),
                   'move-disabled': !moveEditor.active.includes(entry.id) && moveEditor.active.filter(Boolean).length >= 4 && entry.level <= (moveEditor.slot?.lvl ?? 0)
                 }"
                 @click="toggleMove(entry.id, entry.level)">
              <div class="move-row-left">
                <span class="move-check">{{ moveEditor.active.includes(entry.id) ? '✓' : '○' }}</span>
                <span class="move-name">{{ MOVES[String(entry.id)]?.name ?? '???' }}</span>
                <span class="move-type-badge" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(entry.id)]?.type ?? 1] ?? '#888' }">
                  {{ TYPE_NAMES[MOVES[String(entry.id)]?.type ?? 1] ?? '???' }}
                </span>
              </div>
              <div class="move-row-right">
                <span v-if="entry.tag === 'type'" class="move-src-tag">TYPE</span>
                <span v-else-if="entry.level > 0" class="move-lv-tag">Lv.{{ entry.level }}</span>
                <span class="move-pwr" v-if="MOVES[String(entry.id)]?.power > 0">PWR {{ MOVES[String(entry.id)]?.power }}</span>
                <span class="move-pp">PP {{ MOVES[String(entry.id)]?.pp }}</span>
              </div>
              <div v-if="entry.level > (moveEditor.slot?.lvl ?? 0)" class="move-locked-overlay">
                Unlocked at Lv.{{ entry.level }}
              </div>
            </div>

            <!-- ── GRID BOX ── -->
            <div v-else
                 class="move-box"
                 :class="{
                   'move-active': moveEditor.active.includes(entry.id),
                   'move-locked': entry.level > (moveEditor.slot?.lvl ?? 0),
                   'move-disabled': !moveEditor.active.includes(entry.id) && moveEditor.active.filter(Boolean).length >= 4 && entry.level <= (moveEditor.slot?.lvl ?? 0)
                 }"
                 @click="toggleMove(entry.id, entry.level)">
              <span class="move-box-check">{{ moveEditor.active.includes(entry.id) ? '✓' : '' }}</span>
              <span class="move-box-name">{{ MOVES[String(entry.id)]?.name ?? '???' }}</span>
              <span class="move-box-type" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(entry.id)]?.type ?? 1] ?? '#888' }">
                {{ TYPE_NAMES[MOVES[String(entry.id)]?.type ?? 1] ?? '???' }}
              </span>
              <span class="move-box-meta">
                <template v-if="MOVES[String(entry.id)]?.power > 0">{{ MOVES[String(entry.id)]?.power }}</template>
                <template v-else>—</template>
              </span>
              <div v-if="entry.level > (moveEditor.slot?.lvl ?? 0)" class="move-locked-overlay">
                🔒 Lv.{{ entry.level }}
              </div>
            </div>

          </template>
        </div>

        <div class="modal-actions modal-actions-split">
          <button class="modal-btn modal-release" @click="releaseFromModal">✕ RELEASE</button>
          <div class="modal-actions-right">
            <button class="modal-btn modal-cancel" @click="moveEditor.show = false">CANCEL</button>
            <button class="modal-btn modal-confirm" @click="saveMoveEditor">CONFIRM</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Release confirmation modal -->
    <div v-if="releaseTarget.show" class="modal-overlay" @click.self="releaseTarget.show = false">
      <div class="modal-box">
        <div class="modal-title">RELEASE POKÉMON?</div>
        <div class="modal-body">
          Release <span class="modal-poke">{{ releaseTarget.name }}</span> into the wild?<br/>This cannot be undone.
        </div>
        <div class="modal-actions">
          <button class="modal-btn modal-cancel" @click="releaseTarget.show = false">CANCEL</button>
          <button class="modal-btn modal-confirm" @click="confirmRelease">RELEASE</button>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useSaveStore } from '../stores/save'
import { pokedex, padId } from '../data/pokemon'
import statsData from '../data/pokemon-stats.json'
import movesData from '../data/moves.json'
import learnsetsData from '../data/learnsets.json'

type StatsEntry = { hp: number; type1: number; type2: number }
const STATS = statsData as Record<string, StatsEntry>

type MoveEntry = { name: string; type: number; power: number; acc: number; pp: number }
const MOVES = movesData as Record<string, MoveEntry>
const LEARNSETS_DATA = learnsetsData as Record<string, { id: number; level: number }[]>

const router = useRouter()
const saveStore = useSaveStore()

const pcCells = computed(() => Math.max(5, saveStore.pc.filter(p => p?.id).length + 4))

const releaseTarget = reactive({ show: false, source: '' as 'pc' | 'team', index: -1, name: '' })
function askRelease(source: 'pc' | 'team', index: number, name: string) {
  releaseTarget.source = source
  releaseTarget.index = index
  releaseTarget.name = name
  releaseTarget.show = true
}
function confirmRelease() {
  if (releaseTarget.source === 'pc') {
    saveStore.pc.splice(releaseTarget.index, 1)
  } else {
    saveStore.team[releaseTarget.index] = { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: releaseTarget.index + 1 }
  }
  saveStore.save()
  releaseTarget.show = false
}

const dragSrc = ref('')
const dragOver = ref('')

const activeTeam = computed(() => saveStore.team.filter(s => s.id > 0))

// ── Type mapping (decoded from classic/pokemon.sql) ────────────────
const TYPE_COLORS: Record<number, string> = {
  1:  '#9B9B6F', // Normal
  2:  '#E8622C', // Fire
  3:  '#B82820', // Fighting
  4:  '#4A6EDC', // Water
  5:  '#8830A0', // Poison
  6:  '#D4B000', // Electric
  7:  '#C8A840', // Ground
  8:  '#DC3060', // Psychic
  9:  '#A89020', // Rock
  10: '#5AC0D0', // Ice
  11: '#7A9010', // Bug
  12: '#5010E0', // Dragon
  13: '#5040A0', // Ghost
  15: '#50A830', // Grass
  16: '#8070D8', // Flying
}

const TYPE_NAMES: Record<number, string> = {
  1: 'NRM', 2: 'FIRE', 3: 'FGT', 4: 'WTR', 5: 'PSN',
  6: 'ELC', 7: 'GND', 8: 'PSY', 9: 'RCK', 10: 'ICE',
  11: 'BUG', 12: 'DRG', 13: 'GHO', 15: 'GRS', 16: 'FLY',
}

function getPrimaryType(id: number): number {
  const s = STATS[String(id)]
  if (!s) return 1
  return s.type1 !== 1 ? s.type1 : s.type2
}

function getTypes(id: number): number[] {
  const s = STATS[String(id)]
  if (!s) return [1]
  return s.type1 !== s.type2 ? [s.type1, s.type2] : [s.type2]
}

function typeColor(id: number): string {
  return TYPE_COLORS[getPrimaryType(id)] ?? '#888'
}
function typeName(id: number): string {
  return TYPE_NAMES[getPrimaryType(id)] ?? '???'
}

function xpToNext(lvl: number): number {
  return (lvl + 1) ** 3 - lvl ** 3
}
function xpPct(slot: { lvl: number; xp: number }): number {
  return Math.min(1, slot.xp / xpToNext(slot.lvl))
}

// ── Type gradient helpers ──────────────────────────────────────────
/** Horizontal gradient (left → right) for top bars on PC cards */
function typeBgH(id: number): string {
  const s = STATS[String(id)]
  if (!s) return TYPE_COLORS[1] ?? '#888'
  const c1 = TYPE_COLORS[s.type1] ?? '#888'
  const c2 = TYPE_COLORS[s.type2] ?? '#888'
  if (s.type1 === s.type2) return c1
  return `linear-gradient(to right, ${c1}, ${c2})`
}
// ── Card styles ────────────────────────────────────────────────────
function cardStyle(_id: number) { return {} }  // type bar handled by card-type-bar div

/**
 * For the left border of team slots.
 * Single-type  → solid borderLeftColor.
 * Dual-type    → gradient faked via padding-box/border-box background-clip trick:
 *   white covers the padding area, gradient bleeds through the transparent left border.
 */
function slotBorder(id: number) {
  const s = STATS[String(id)]
  if (!s) return { borderLeftColor: TYPE_COLORS[1] ?? '#888' }
  const c1 = TYPE_COLORS[s.type1] ?? '#888'
  const c2 = TYPE_COLORS[s.type2] ?? '#888'
  if (s.type1 === s.type2) return { borderLeftColor: c1 }
  return {
    background: `linear-gradient(#fff, #fff) padding-box,
                 linear-gradient(to bottom, ${c1}, ${c2}) border-box`,
    borderLeftColor: 'transparent',
  }
}

// ── HP helpers ─────────────────────────────────────────────────────
function calcMaxHP(pokeid: number, lvl: number): number {
  return Math.floor(2 * (STATS[String(pokeid)]?.hp ?? 45) * lvl / 100) + lvl + 10
}
function hpPct(slot: { id: number; lvl: number; hp: number }): number {
  return Math.max(0, Math.min(1, slot.hp / calcMaxHP(slot.id, slot.lvl)))
}
function hpColor(pct: number): string {
  return pct > 0.5 ? '#2ecc40' : pct > 0.25 ? '#ffdc00' : '#ff4136'
}

// ── Evolution ─────────────────────────────────────────────────────
function evolveSlot(slot: { id: number; lvl: number; hp: number; pendingEvo?: number; shiny?: boolean }) {
  if (!slot.pendingEvo) return
  const oldMaxHP = calcMaxHP(slot.id, slot.lvl)
  const isShiny = slot.shiny ?? false
  slot.id = slot.pendingEvo
  const newMaxHP = calcMaxHP(slot.id, slot.lvl)
  const hpGain = Math.max(0, newMaxHP - oldMaxHP)
  slot.hp = Math.min(slot.hp + hpGain, newMaxHP)
  slot.pendingEvo = undefined
  // Register evolved form as caught in the Pokédex
  const dexId = String(slot.id)
  if (isShiny) {
    saveStore.shinydex[dexId] = 'caught'
    saveStore.pokedex[dexId] = 'caught'
  } else {
    saveStore.pokedex[dexId] = 'caught'
  }
  saveStore.save()
}

// ── XP multiplier ─────────────────────────────────────────────────
function adjustMult(delta: number) {
  const next = Math.round((saveStore.xpMultiplier + delta) * 10) / 10
  saveStore.xpMultiplier = Math.max(0.5, next)
  saveStore.save()
}

// ── Move editor ───────────────────────────────────────────────────
interface LearnEntry { id: number; level: number; tag?: 'type' | 'legacy' }
const moveEditor = reactive<{
  show: boolean
  slot: typeof saveStore.team[0] | null
  source: 'pc' | 'team'
  sourceIndex: number
  learnset: LearnEntry[]
  active: number[]  // up to 4 move IDs (0 = empty)
  view: 'list' | 'grid'
  nickname: string
}>({ show: false, slot: null, source: 'team', sourceIndex: -1, learnset: [], active: [0, 0, 0, 0], view: 'grid', nickname: '' })

function openMoveEditor(slot: typeof saveStore.team[0], source: 'pc' | 'team' = 'team', sourceIndex = -1) {
  moveEditor.slot = slot
  moveEditor.source = source
  moveEditor.sourceIndex = sourceIndex
  moveEditor.nickname = slot.nickname ?? ''
  const raw = LEARNSETS_DATA[String(slot.id)] ?? []

  // Pokémon's types — used to build the type-pool
  const st = STATS[String(slot.id)]
  const types: number[] = st
    ? (st.type1 !== st.type2 ? [st.type1, st.type2] : [st.type2])
    : []

  // All moves whose type matches the Pokémon's type(s), not already in its learnset
  const typeMoves: LearnEntry[] = Object.entries(MOVES)
    .filter(([id, m]) => types.includes(m.type) && !raw.find(e => e.id === Number(id)))
    .map(([id]) => ({ id: Number(id), level: 0, tag: 'type' as const }))

  // Currently-equipped moves not covered by learnset OR type pool (pre-learnset legacy)
  const knownIds = new Set([...raw.map(e => e.id), ...typeMoves.map(e => e.id)])
  const legacyEntries: LearnEntry[] = slot.moves
    .filter(m => m.id > 0 && MOVES[String(m.id)] && !knownIds.has(m.id))
    .map(m => ({ id: m.id, level: 0, tag: 'legacy' as const }))

  const all = [...raw, ...typeMoves, ...legacyEntries]

  // Unlocked: level ≤ current lvl — learnset moves by level desc, then type/legacy alphabetically
  const unlocked = all
    .filter(e => e.level <= slot.lvl && MOVES[String(e.id)])
    .sort((a, b) => {
      if (a.level !== b.level) return b.level - a.level  // higher level first
      // tie-break: learnset moves before type/legacy
      const tagOrder = (t?: string) => t === 'type' ? 1 : t === 'legacy' ? 2 : 0
      return tagOrder(a.tag) - tagOrder(b.tag)
    })
  const locked = all
    .filter(e => e.level > slot.lvl && MOVES[String(e.id)])
    .sort((a, b) => a.level - b.level)

  moveEditor.learnset = [...unlocked, ...locked]

  // Init active from slot.moves (build fresh array then assign to trigger reactivity)
  const initActive = [0, 0, 0, 0]
  slot.moves.slice(0, 4).forEach((m, i) => { initActive[i] = m.id })
  moveEditor.active = initActive
  moveEditor.show = true
}

function toggleMove(moveId: number, level: number) {
  const slot = moveEditor.slot
  if (!slot) return
  if (level > slot.lvl) return  // locked
  const idx = moveEditor.active.indexOf(moveId)
  if (idx !== -1) {
    moveEditor.active.splice(idx, 1, 0)  // deselect — splice triggers Vue reactivity
  } else {
    const emptyIdx = moveEditor.active.indexOf(0)
    if (emptyIdx === -1) return  // all 4 taken
    moveEditor.active.splice(emptyIdx, 1, moveId)  // select
  }
}

function saveMoveEditor() {
  const slot = moveEditor.slot
  if (!slot) return
  slot.moves = moveEditor.active
    .filter(id => id > 0)
    .map(id => {
      const existing = slot.moves.find(m => m.id === id)
      const entry = MOVES[String(id)]
      return { id, pp: existing?.pp ?? entry?.pp ?? 1 }
    })
  const trimmed = moveEditor.nickname.trim()
  slot.nickname = trimmed || undefined
  saveStore.save()
  moveEditor.show = false
}

function releaseFromModal() {
  const slot = moveEditor.slot
  if (!slot) return
  const displayName = slot.nickname?.trim() || pokedex(slot.id)
  moveEditor.show = false
  // small timeout so move modal fully closes before release confirm appears
  setTimeout(() => askRelease(moveEditor.source, moveEditor.sourceIndex, displayName), 80)
}

// ── Heal all ──────────────────────────────────────────────────────
function healAll() {
  const healSlot = (slot: typeof saveStore.team[0]) => {
    if (!slot.id) return
    slot.hp = calcMaxHP(slot.id, slot.lvl)
    slot.moves.forEach(m => {
      const entry = MOVES[String(m.id)]
      if (entry) m.pp = entry.pp
    })
  }
  saveStore.team.forEach(healSlot)
  saveStore.pc.forEach(healSlot)
  saveStore.save()
}

// ── Drag & drop ──────────────────────────────────────────────────── ────────────────────────────────────────────────────
function onDragStart(area: 'pc' | 'team', idx: number) {
  dragSrc.value = `${area}-${idx}`
}

function onDropToPC(targetIdx: number) {
  dragOver.value = ''
  const src = dragSrc.value
  if (!src) return
  const [srcArea, srcIdxStr] = src.split('-')
  const srcIdx = Number(srcIdxStr)

  if (srcArea === 'team') {
    if (activeTeam.value.length <= 1) return
    const poke = { ...saveStore.team[srcIdx] }
    while (saveStore.pc.length <= targetIdx) {
      saveStore.pc.push({ id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 0 })
    }
    const existing = saveStore.pc[targetIdx]
    if (existing?.id) {
      saveStore.team[srcIdx] = { ...existing, slot: srcIdx + 1 }
      saveStore.pc[targetIdx] = { ...poke, slot: targetIdx + 1 }
    } else {
      saveStore.pc[targetIdx] = { ...poke, slot: targetIdx + 1 }
      saveStore.team[srcIdx] = { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: srcIdx + 1 }
    }
  } else if (srcArea === 'pc') {
    if (srcIdx === targetIdx) return
    while (saveStore.pc.length <= Math.max(srcIdx, targetIdx)) {
      saveStore.pc.push({ id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 0 })
    }
    const a = saveStore.pc[srcIdx] ? { ...saveStore.pc[srcIdx] } : null
    const b = saveStore.pc[targetIdx] ? { ...saveStore.pc[targetIdx] } : null
    saveStore.pc[targetIdx] = a ? { ...a, slot: targetIdx + 1 } : { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: targetIdx + 1 }
    saveStore.pc[srcIdx] = b ? { ...b, slot: srcIdx + 1 } : { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: srcIdx + 1 }
  }

  while (saveStore.pc.length && !saveStore.pc[saveStore.pc.length - 1]?.id) saveStore.pc.pop()
  saveStore.save()
}

function onDropToTeam(targetIdx: number) {
  dragOver.value = ''
  const src = dragSrc.value
  if (!src) return
  const [srcArea, srcIdxStr] = src.split('-')
  const srcIdx = Number(srcIdxStr)

  if (srcArea === 'team') {
    if (srcIdx === targetIdx) return
    const temp = { ...saveStore.team[srcIdx] }
    saveStore.team[srcIdx] = { ...saveStore.team[targetIdx], slot: srcIdx + 1 }
    saveStore.team[targetIdx] = { ...temp, slot: targetIdx + 1 }
  } else if (srcArea === 'pc') {
    const fromPC = { ...saveStore.pc[srcIdx] }
    const fromTeam = { ...saveStore.team[targetIdx] }
    if (activeTeam.value.length >= 6 && fromTeam.id) {
      saveStore.team[targetIdx] = { ...fromPC, slot: targetIdx + 1 }
      saveStore.pc[srcIdx] = { ...fromTeam, slot: srcIdx + 1 }
    } else {
      saveStore.team[targetIdx] = { ...fromPC, slot: targetIdx + 1 }
      saveStore.pc.splice(srcIdx, 1)
      saveStore.pc.forEach((p, i) => { p.slot = i + 1 })
    }
  }

  while (saveStore.pc.length && !saveStore.pc[saveStore.pc.length - 1]?.id) saveStore.pc.pop()
  saveStore.save()
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323:wght@400&display=swap');

/* ═══════════════════════════════════════════════════════
   BILL'S PC  —  Pokémon Center terminal aesthetic
   Palette: sky-blue page · white panels · navy borders
            red header · green party · saturated types
   ═══════════════════════════════════════════════════════ */

* { box-sizing: border-box; }

main {
  min-height: 100dvh;
  padding-top: 132px;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 16px 16px;
}

.pc-wrap {
  max-width: 960px;
  margin: 0 auto;
  padding: 20px 16px 40px;
}

/* ── Header ── */
.pc-header {
  font-family: 'Press Start 2P', monospace;
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  padding: 14px 18px;
  background: #c82020;
  border: 3px solid #881010;
  border-radius: 4px;
  box-shadow: 0 5px 0 #660808;
}
.pc-title {
  font-size: 18px;
  color: #fff;
  text-shadow: 2px 2px 0 #660808;
  letter-spacing: 2px;
}
.pc-sub {
  font-size: 9px;
  color: #ffc8c8;
  margin-top: 2px;
  letter-spacing: 1px;
}
.blink {
  font-size: 18px;
  color: #fff;
  animation: blink-cursor 1s step-end infinite;
}
@keyframes blink-cursor { 0%,100% { opacity: 1 } 50% { opacity: 0 } }

/* ── Layout ── */
.pc-layout {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

/* ── Box Panel ── */
.box-panel {
  flex: 1;
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 5px 0 #1a3080;
}

.box-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
  padding: 12px;
  background: #dceef8;
}

.box-cell {
  aspect-ratio: 1;
  background: #fff;
  border: 2px solid #b0cce0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: border-color 0.15s, box-shadow 0.15s;
  min-height: 72px;
}
.box-cell.drop-glow {
  border-color: #c82020;
  box-shadow: 0 0 0 2px #c82020;
  background: #fff4f4;
}

.cell-empty {
  color: #b8d4e8;
  font-size: 22px;
  font-family: monospace;
  user-select: none;
}

/* ── Pokémon Card ── */
.poke-card {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: grab;
  border: 2px solid transparent;
  border-radius: 4px;
  padding: 3px;
  position: relative;
  background: #fff;
  transition: transform 0.12s, opacity 0.12s;
  user-select: none;
  overflow: hidden;
}

/* Gradient type bar — spans full card width at the very top */
.card-type-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  flex-shrink: 0;
}
.poke-card:hover {
  transform: scale(1.08);
  z-index: 2;
}
.poke-card:active { cursor: grabbing; }
.poke-card.card-dragging { opacity: 0.3; transform: scale(0.9); }

.poke-card > img {
  width: 46px;
  height: 46px;
  image-rendering: pixelated;
  margin: 1px 0;
}

.type-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  padding: 2px 4px;
  border-radius: 2px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.5);
  letter-spacing: 0.5px;
  align-self: flex-start;
}

.shiny-star { font-size: 11px; line-height: 1; }
.card-name {
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #181830;
  text-align: center;
  line-height: 1;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-lvl {
  font-family: 'VT323', monospace;
  font-size: 11px;
  color: #4060a0;
  line-height: 1;
}

.card-hp-bar {
  width: 85%;
  height: 4px;
  background: #d0e4f0;
  border: 1px solid #b0c8e0;
  border-radius: 2px;
  margin-top: 3px;
  overflow: hidden;
}
.card-hp-fill { height: 100%; border-radius: 1px; transition: width 0.3s; }
.card-xp-bar {
  width: 85%;
  height: 3px;
  background: #d0e4f0;
  border: 1px solid #b0c8e0;
  border-radius: 2px;
  margin-top: 2px;
  overflow: hidden;
}
.card-xp-fill { height: 100%; border-radius: 1px; background: #2848a8; transition: width 0.3s; }
.type-badges { display: flex; gap: 2px; flex-wrap: wrap; margin-bottom: 2px; }

/* ── Box Footer ── */
.box-footer {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #4060a0;
  padding: 6px 12px;
  border-top: 2px solid #b0cce0;
  text-align: right;
  background: #f0f8ff;
}

/* ── Team Panel ── */
.team-panel {
  width: 230px;
  flex-shrink: 0;
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 4px;
  overflow: hidden;
  position: sticky;
  top: 148px;
  align-self: flex-start;
  box-shadow: 0 5px 0 #1a3080;
}

.team-header {
  font-family: 'Press Start 2P', monospace;
  font-size: 10px;
  color: #fff;
  padding: 8px 10px;
  background: #2a9030;
  border-bottom: 3px solid #1a6020;
  letter-spacing: 2px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 3px 0 #1a6020;
}
.heal-all-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  background: #f0fff0;
  color: #1a6020;
  border: 2px solid #1a6020;
  border-radius: 2px;
  padding: 6px 10px;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: background 0.15s;
  box-shadow: 0 2px 0 #0e3a10;
}
.heal-all-btn:hover { background: #d8f0d8; }

.xpshare-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  background: #e8f4fc;
  border-bottom: 1px solid #b8d4e8;
}
.xpshare-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #2848a8;
  letter-spacing: 1px;
}
.xpshare-toggle {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  padding: 5px 10px;
  border-radius: 2px;
  border: 2px solid #a0c0d8;
  background: #dceef8;
  color: #4060a0;
  cursor: pointer;
  transition: all 0.15s;
  letter-spacing: 1px;
  box-shadow: 0 2px 0 #80a0c0;
}
.xpmult-controls { display: flex; align-items: center; gap: 6px; }
.xpmult-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 10px;
  width: 22px; height: 22px;
  background: #dceef8;
  color: #2848a8;
  border: 2px solid #a0c0d8;
  border-radius: 2px;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  transition: background 0.15s;
  box-shadow: 0 2px 0 #80a0c0;
}
.xpmult-btn:hover { background: #c8e4f4; }
.xpmult-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  color: #2848a8;
  min-width: 30px;
  text-align: center;
}

.xpshare-toggle.active {
  background: #fff4d8;
  color: #8a4800;
  border-color: #c08020;
  box-shadow: 0 2px 0 #906010;
}

.team-list {
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.team-slot {
  background: #fff;
  border: 2px solid #b8d4e8;
  border-left-width: 5px;
  border-radius: 4px;
  height: 66px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px 6px 8px;
  cursor: grab;
  position: relative;
  overflow: hidden;
  transition: border-color 0.15s, opacity 0.12s;
  user-select: none;
}
.team-slot:active { cursor: grabbing; }
.team-slot.slot-dragging { opacity: 0.3; }
.team-slot.slot-drop {
  border-color: #c82020 !important;
  border-left-color: #c82020 !important;
}
.team-slot.slot-empty {
  cursor: default;
  opacity: 0.4;
  border-left-color: #b8d4e8 !important;
  justify-content: center;
  background: #fff;
}

.team-slot > img {
  width: 48px;
  height: 48px;
  image-rendering: pixelated;
  flex-shrink: 0;
}

.slot-info { flex: 1; min-width: 0; }
.slot-name {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #181830;
  line-height: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.slot-hp-bar {
  width: 100%;
  height: 4px;
  background: #d0e4f0;
  border: 1px solid #b0c8e0;
  border-radius: 2px;
  margin: 4px 0 2px;
  overflow: hidden;
}
.slot-hp-fill { height: 100%; border-radius: 1px; transition: width 0.3s; }
.slot-xp-bar {
  width: 100%;
  height: 3px;
  background: #d0e4f0;
  border: 1px solid #b0c8e0;
  border-radius: 2px;
  margin: 3px 0 1px;
  overflow: hidden;
}
.slot-xp-fill { height: 100%; border-radius: 1px; background: #2848a8; transition: width 0.3s; }
.bar-row {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 3px 0 1px;
  width: 100%;
}
.bar-row .slot-hp-bar,
.bar-row .slot-xp-bar,
.bar-row .card-hp-bar,
.bar-row .card-xp-bar { flex: 1; width: auto; margin: 0; }
.poke-card .bar-row { width: 88%; align-self: center; }
.bar-label {
  font-family: 'VT323', monospace;
  font-size: 11px;
  color: #4060a0;
  white-space: nowrap;
  min-width: 44px;
  text-align: right;
  flex-shrink: 0;
}
.slot-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}
.type-chips { display: flex; flex-direction: column; gap: 3px; align-items: flex-end; }
.evo-warning {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #7020c0;
  letter-spacing: 0.5px;
  cursor: default;
}

.type-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  padding: 3px 5px;
  border-radius: 2px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.5);
  flex-shrink: 0;
  align-self: flex-start;
  margin-top: 4px;
}

.slot-empty-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #a0c0d8;
  letter-spacing: 1px;
}

.party-count {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #4060a0;
  padding: 6px 12px;
  border-top: 2px solid #b8d4e8;
  text-align: center;
  background: #f0f8ff;
}

/* ── Gear on PC card ── */
.gear-btn-card {
  position: absolute !important;
  top: 2px;
  right: 2px;
  width: 18px !important;
  height: 18px !important;
  font-size: 13px !important;
  z-index: 5;
  opacity: 0.5;
}
.poke-card:hover .gear-btn-card { opacity: 1; }

/* ── Modals ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-box {
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 4px;
  padding: 24px 28px;
  max-width: 340px;
  width: 90%;
  box-shadow: 0 6px 0 #1a3080;
  text-align: center;
}
.modal-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 10px;
  color: #c82020;
  text-shadow: 1px 1px 0 #880808;
  margin-bottom: 16px;
  letter-spacing: 1px;
}
.modal-body {
  font-family: 'VT323', monospace;
  font-size: 18px;
  color: #181830;
  line-height: 1.5;
  margin-bottom: 20px;
}
.modal-poke {
  color: #c82020;
  font-weight: bold;
}
.modal-actions { display: flex; gap: 12px; justify-content: center; }
.modal-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  padding: 8px 14px;
  border-radius: 2px;
  cursor: pointer;
  border: 2px solid;
  transition: background 0.15s;
}
.modal-cancel {
  background: #dceef8;
  border-color: #a0c0d8;
  color: #4060a0;
  box-shadow: 0 3px 0 #80a0c0;
}
.modal-cancel:hover { background: #c8e4f4; }
.modal-confirm {
  background: #fff0f0;
  border-color: #c82020;
  color: #c82020;
  box-shadow: 0 3px 0 #881010;
}
.modal-confirm:hover { background: #ffe0e0; }

/* ── Evolve button ── */
.evolve-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  background: #7020c0;
  color: #fff;
  border: 2px solid #4a1090;
  border-radius: 2px;
  padding: 5px 8px;
  cursor: pointer;
  margin-top: 6px;
  width: 100%;
  letter-spacing: 0.5px;
  box-shadow: 0 3px 0 #300880;
  transition: background 0.15s;
}
.evolve-btn:hover { background: #8830d8; }
.evolve-btn-card {
  font-size: 6px;
  padding: 3px 5px;
  margin-top: 4px;
  width: auto;
}

/* ── Tip bar ── */
.pc-tip {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #6080a8;
  text-align: center;
  margin-top: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.blink-slow {
  color: #2a9030;
  animation: blink-cursor 2s step-end infinite;
}

/* ── Gear button ── */
.gear-btn {
  font-size: 20px;
  background: none;
  border: none;
  padding: 0;
  width: 24px;
  height: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: filter 0.15s, transform 0.15s;
}
.gear-btn i {
  background: linear-gradient(160deg, #7090b0 0%, #2848a8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: inherit;
  line-height: 1;
  filter: drop-shadow(0 1px 0 rgba(255,255,255,0.8));
}
.gear-btn:hover {
  transform: rotate(30deg);
  filter: drop-shadow(0 0 3px rgba(40,72,168,0.4));
}

/* ── Move editor modal ── */
.move-modal {
  max-width: 480px;
  width: 95%;
  text-align: left;
  padding: 20px 20px 16px;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  border-radius: 4px;
}
.move-modal .modal-title {
  color: #181830;
  text-shadow: none;
  margin-bottom: 4px;
  font-size: 10px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}
.move-modal-sub {
  font-family: 'VT323', monospace;
  font-size: 15px;
  color: #6080a0;
  font-weight: normal;
}
.move-modal-desc {
  font-family: 'VT323', monospace;
  font-size: 15px;
  color: #5a5a80;
  margin-bottom: 14px;
  line-height: 1.3;
}

/* Header row with title + view toggle */
.move-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 4px;
}
.move-modal-header .modal-title {
  margin-bottom: 0;
  flex: 1;
}
.view-toggle {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
  margin-top: 2px;
}
.view-btn {
  font-size: 14px;
  width: 26px;
  height: 26px;
  background: #dceef8;
  border: 2px solid #a0c0d8;
  border-radius: 2px;
  color: #6080a0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: color 0.12s, border-color 0.12s, background 0.12s;
  box-shadow: 0 2px 0 #80a0c0;
}
.view-btn.active {
  color: #fff;
  border-color: #2848a8;
  background: #2848a8;
  box-shadow: 0 2px 0 #1a3080;
}
.view-btn:hover:not(.active) {
  background: #c8e4f4;
  border-color: #6090c0;
}

/* Active move preview row */
.active-moves-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  margin-bottom: 14px;
}
.active-move-slot {
  background: #fff;
  border: 2px solid #b8d4e8;
  border-radius: 2px;
  padding: 6px 5px;
  min-height: 42px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  text-align: center;
  transition: border-color 0.15s;
}
.active-move-slot.filled {
  border-color: #2848a8;
  background: #eaf2ff;
}
.am-name {
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #181830;
  line-height: 1;
  word-break: break-word;
}
.am-type-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  padding: 2px 4px;
  border-radius: 2px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.4);
}
.am-empty {
  font-family: 'VT323', monospace;
  font-size: 20px;
  color: #b8d4e8;
}

/* Move list */
.move-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 14px;
  padding-right: 4px;
  scrollbar-width: thin;
  scrollbar-color: #a0c0d8 #e8f4fc;
}
.move-list::-webkit-scrollbar { width: 5px; }
.move-list::-webkit-scrollbar-track { background: #e8f4fc; }
.move-list::-webkit-scrollbar-thumb { background: #a0c0d8; border-radius: 2px; }

.move-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  background: #fff;
  border: 2px solid #c8dcea;
  border-radius: 2px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: background 0.12s, border-color 0.12s;
  gap: 8px;
}
.move-row:hover:not(.move-locked):not(.move-disabled) {
  background: #e8f4fc;
  border-color: #6090c0;
}
.move-row.move-active {
  background: #eaf2ff;
  border-color: #2848a8;
  box-shadow: inset 3px 0 0 #2848a8;
}
.move-row.move-disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.move-row.move-locked {
  opacity: 0.5;
  cursor: not-allowed;
}

.move-row-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}
.move-row-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.move-check {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #a0c0d8;
  width: 14px;
  text-align: center;
  flex-shrink: 0;
}
.move-row.move-active .move-check { color: #2848a8; }
.move-name {
  font-family: 'VT323', monospace;
  font-size: 16px;
  color: #181830;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 140px;
}
.move-type-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  padding: 2px 4px;
  border-radius: 2px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.5);
  flex-shrink: 0;
}
.move-lv-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #4060a0;
  background: #e0eeff;
  border: 1px solid #a0c0d8;
  border-radius: 2px;
  padding: 2px 4px;
  flex-shrink: 0;
}
.move-src-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #1a7030;
  background: #e0f8e8;
  border: 1px solid #80c090;
  border-radius: 2px;
  padding: 2px 4px;
  flex-shrink: 0;
}
.move-pwr {
  font-family: 'VT323', monospace;
  font-size: 14px;
  color: #c04020;
}
.move-pp {
  font-family: 'VT323', monospace;
  font-size: 14px;
  color: #4060a0;
}

.move-locked-overlay {
  position: absolute;
  inset: 0;
  background: rgba(240, 248, 255, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #8090b0;
  letter-spacing: 0.5px;
  pointer-events: none;
}

/* ── Grid view ── */
.move-list-grid {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 5px;
}

.move-box {
  position: relative;
  background: #fff;
  border: 2px solid #c8dcea;
  border-radius: 2px;
  padding: 6px 5px 5px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  text-align: center;
  overflow: hidden;
  transition: background 0.12s, border-color 0.12s;
  min-height: 68px;
}
.move-box:hover:not(.move-locked):not(.move-disabled) {
  background: #e8f4fc;
  border-color: #6090c0;
}
.move-box.move-active {
  background: #eaf2ff;
  border-color: #2848a8;
  box-shadow: inset 0 3px 0 #2848a8;
}
.move-box.move-disabled { opacity: 0.4; cursor: not-allowed; }
.move-box.move-locked  { opacity: 0.5; cursor: not-allowed; }

.move-box-check {
  position: absolute;
  top: 3px;
  left: 5px;
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #2848a8;
  line-height: 1;
}
.move-box-name {
  font-family: 'VT323', monospace;
  font-size: 14px;
  color: #181830;
  line-height: 1.15;
  margin-top: 2px;
  word-break: break-word;
  hyphens: auto;
}
.move-box-type {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  padding: 2px 5px;
  border-radius: 2px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.4);
}
.move-box-meta {
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #c04020;
  line-height: 1;
}
.move-box .move-locked-overlay {
  font-size: 5px;
  gap: 3px;
  flex-direction: column;
  padding: 2px;
}

/* ── Nickname row ── */
.nickname-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  padding: 8px 10px;
  background: #e8f4fc;
  border: 2px solid #a0c0d8;
  border-radius: 2px;
}
.nickname-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #2848a8;
  letter-spacing: 1px;
  flex-shrink: 0;
}
.nickname-input {
  flex: 1;
  background: #fff;
  border: 2px solid #a0c0d8;
  border-radius: 2px;
  color: #181830;
  font-family: 'VT323', monospace;
  font-size: 18px;
  padding: 4px 8px;
  outline: none;
  min-width: 0;
  transition: border-color 0.15s;
  letter-spacing: 1px;
}
.nickname-input::placeholder { color: #a0c0d8; }
.nickname-input:focus { border-color: #2848a8; }

/* ── Modal split actions (release | cancel + confirm) ── */
.modal-actions-split {
  display: flex !important;
  justify-content: space-between !important;
  gap: 8px;
}
.modal-actions-right {
  display: flex;
  gap: 10px;
}
.modal-release {
  background: #fff0f0;
  border-color: #c82020;
  color: #c82020;
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  padding: 8px 12px;
  border-radius: 2px;
  cursor: pointer;
  border-width: 2px;
  border-style: solid;
  box-shadow: 0 3px 0 #881010;
  transition: background 0.15s;
  letter-spacing: 0.5px;
}
.modal-release:hover {
  background: #ffe0e0;
}

/* ── Responsive ── */
@media screen and (max-width: 700px) {
  .pc-layout { flex-direction: column; }
  .team-panel { width: 100%; }
  .box-grid { grid-template-columns: repeat(4, 1fr); }
  .pc-title { font-size: 12px; }
}
</style>
