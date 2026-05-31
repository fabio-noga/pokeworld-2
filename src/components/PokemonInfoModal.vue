<template>
  <Teleport to="body">
    <div class="pm-backdrop" @click.self="$emit('close')">
      <div class="pm-panel" :style="{ borderTopColor: primaryColor }">

        <!-- Header -->
        <div class="pm-header" :style="{ background: `linear-gradient(135deg, ${primaryColor}22 0%, transparent 60%)` }">
          <button class="pm-close" @click="$emit('close')">✕</button>

          <div class="pm-hero">
            <img class="pm-art"
              :src="slot.shiny
                ? `/textures/Battle/Shiny/Front/Png/${slot.id}.png`
                : `/textures/Battle/Normal/Front/Png/${slot.id}.png`"
              :alt="pokeName" />

            <div class="pm-hero-info">
              <div class="pm-name-row">
                <span class="pm-name">{{ slot.nickname || pokeName }}</span>
                <span v-if="slot.shiny" class="pm-shiny" title="Shiny">✨</span>
                <span class="pm-num">#{{ padId(slot.id) }}</span>
              </div>

              <div class="pm-types">
                <span v-for="t in types" :key="t" class="type-badge"
                  :style="{ backgroundColor: TYPE_COLORS[t] ?? '#888' }">
                  {{ TYPE_NAMES[t] ?? '???' }}
                </span>
              </div>

              <div class="pm-level">LV. {{ slot.lvl }}</div>

              <!-- HP -->
              <div class="pm-bar-row">
                <span class="pm-bar-lbl">HP</span>
                <div class="pm-bar-track">
                  <div class="pm-bar-fill" :style="{ width: hpPct * 100 + '%', backgroundColor: hpColor }"></div>
                </div>
                <span class="pm-bar-val">{{ slot.hp }} / {{ maxHP }}</span>
              </div>

              <!-- XP -->
              <div class="pm-bar-row">
                <span class="pm-bar-lbl">XP</span>
                <div class="pm-bar-track">
                  <div class="pm-bar-fill xp" :style="{ width: xpPct * 100 + '%' }"></div>
                </div>
                <span class="pm-bar-val">{{ slot.xp % xpThreshold }} / {{ xpThreshold }}</span>
              </div>

              <!-- Level-up evolve -->
              <button v-if="slot.pendingEvo" class="pm-evolve-btn" @click="$emit('evolve')">
                ↑ EVOLVE → {{ pokedex(slot.pendingEvo) }}
              </button>
              <!-- Special evolve (stone / trade) -->
              <button v-if="specialEvo && !slot.pendingEvo" class="pm-evolve-btn pm-evolve-special" @click="$emit('evolve-special', specialEvo.to)">
                {{ specialEvo.icon }} {{ specialEvo.label }} → {{ pokedex(specialEvo.to) }}
              </button>
              <!-- Eevee: three evolution choices -->
              <div v-if="isEevee && !slot.pendingEvo" class="pm-eevee-evos">
                <button v-for="e in EEVEE_SPECIAL" :key="e.to" class="pm-evolve-btn pm-evolve-special" @click="$emit('evolve-special', e.to)">
                  {{ e.icon }} {{ e.label }} → {{ pokedex(e.to) }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Base Stats -->
        <div class="pm-section">
          <div class="pm-section-title">BASE STATS</div>
          <div v-for="s in statRows" :key="s.key" class="stat-row">
            <span class="stat-lbl">{{ s.label }}</span>
            <span class="stat-val">{{ s.value }}</span>
            <div class="stat-bar-bg">
              <div class="stat-bar-fill" :style="{ width: Math.round(s.value / 255 * 100) + '%', backgroundColor: s.color }"></div>
            </div>
          </div>
          <div class="stat-total">TOTAL <span class="stat-total-val">{{ statTotal }}</span></div>
        </div>

        <!-- Moves -->
        <div class="pm-section pm-moves-section">
          <div class="pm-section-title">MOVES</div>
          <div v-if="moveRows.length === 0" class="pm-no-moves">No moves</div>
          <div v-for="m in moveRows" :key="m.id" class="move-row">
            <span class="move-name">{{ m.name }}</span>
            <span class="move-type" :style="{ backgroundColor: TYPE_COLORS[m.type] ?? '#888' }">
              {{ TYPE_NAMES[m.type] ?? '???' }}
            </span>
            <span class="move-pwr">{{ m.power > 0 ? m.power : '—' }}</span>
            <div class="move-pp-track">
              <div class="move-pp-fill" :style="{ width: (m.currentPP / m.maxPP) * 100 + '%', backgroundColor: ppColor(m.currentPP, m.maxPP) }"></div>
            </div>
            <span class="move-pp-val">{{ m.currentPP }}/{{ m.maxPP }}</span>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { pokedex, padId } from '../data/pokemon'
import statsData from '../data/pokemon-stats.json'
import movesData from '../data/moves.json'
import type { TeamSlot } from '../stores/save'

type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number; type1: number; type2: number }
type MoveEntry  = { name: string; type: number; power: number; acc: number; pp: number }
const STATS = statsData as Record<string, StatsEntry>
const MOVES = movesData as Record<string, MoveEntry>

const props = defineProps<{ slot: TeamSlot }>()
defineEmits<{ close: []; evolve: []; 'evolve-special': [to: number] }>()

// ── Special evolutions (stone / trade) ──────────────────────────
const SPECIAL_EVOS: Record<number, { to: number; icon: string; label: string }> = {
  // Fire Stone
  37:  { to: 38,  icon: '🔥', label: 'Fire Stone'    },  // Vulpix → Ninetales
  58:  { to: 59,  icon: '🔥', label: 'Fire Stone'    },  // Growlithe → Arcanine
  // Water Stone
  61:  { to: 62,  icon: '💧', label: 'Water Stone'   },  // Poliwhirl → Poliwrath
  90:  { to: 91,  icon: '💧', label: 'Water Stone'   },  // Shellder → Cloyster
  120: { to: 121, icon: '💧', label: 'Water Stone'   },  // Staryu → Starmie
  // Thunder Stone
  25:  { to: 26,  icon: '⚡', label: 'Thunder Stone' },  // Pikachu → Raichu
  // Leaf Stone
  44:  { to: 45,  icon: '🌿', label: 'Leaf Stone'    },  // Gloom → Vileplume
  70:  { to: 71,  icon: '🌿', label: 'Leaf Stone'    },  // Weepinbell → Victreebel
  102: { to: 103, icon: '🌿', label: 'Leaf Stone'    },  // Exeggcute → Exeggutor
  // Moon Stone
  30:  { to: 31,  icon: '🌙', label: 'Moon Stone'    },  // Nidorina → Nidoqueen
  33:  { to: 34,  icon: '🌙', label: 'Moon Stone'    },  // Nidorino → Nidoking
  35:  { to: 36,  icon: '🌙', label: 'Moon Stone'    },  // Clefairy → Clefable
  39:  { to: 40,  icon: '🌙', label: 'Moon Stone'    },  // Jigglypuff → Wigglytuff
  // Eevee branches
  133: { to: 134, icon: '💧', label: 'Water Stone'   },  // Eevee → Vaporeon (default; UI shows all 3)
  // Trade evolutions
  64:  { to: 65,  icon: '🔄', label: 'Trade'         },  // Kadabra → Alakazam
  67:  { to: 68,  icon: '🔄', label: 'Trade'         },  // Machoke → Machamp
  75:  { to: 76,  icon: '🔄', label: 'Trade'         },  // Graveler → Golem
  93:  { to: 94,  icon: '🔄', label: 'Trade'         },  // Haunter → Gengar
}

// Eevee gets three separate buttons — handled via specialEvoList
const EEVEE_SPECIAL = [
  { to: 134, icon: '💧', label: 'Water Stone'   },
  { to: 135, icon: '⚡', label: 'Thunder Stone' },
  { to: 136, icon: '🔥', label: 'Fire Stone'    },
]

const specialEvo     = computed(() => props.slot.id === 133 ? null : (SPECIAL_EVOS[props.slot.id] ?? null))
const isEevee        = computed(() => props.slot.id === 133)

const TYPE_COLORS: Record<number, string> = {
  1: '#7a7a5a', 2: '#d85020', 3: '#982018', 4: '#3a5ab8',
  5: '#702888', 6: '#a88c00', 7: '#a87830', 8: '#b82050',
  9: '#887010', 10: '#4aaab0', 11: '#5a7808', 12: '#3808b8',
  13: '#382878', 15: '#389820', 16: '#6050b8',
}
const TYPE_NAMES: Record<number, string> = {
  1: 'NORMAL', 2: 'FIRE', 3: 'FIGHT', 4: 'WATER', 5: 'POISON',
  6: 'ELECTR', 7: 'GROUND', 8: 'PSYCHC', 9: 'ROCK', 10: 'ICE',
  11: 'BUG', 12: 'DRAGON', 13: 'GHOST', 15: 'GRASS', 16: 'FLYING',
}

const pokeName = computed(() => pokedex(props.slot.id))
const entry    = computed(() => STATS[String(props.slot.id)])

const types = computed(() => {
  if (!entry.value) return [1]
  return entry.value.type1 !== entry.value.type2
    ? [entry.value.type1, entry.value.type2]
    : [entry.value.type2]
})
const primaryColor = computed(() => TYPE_COLORS[types.value[0]] ?? '#444')

// HP / XP
const maxHP = computed(() => {
  const base = entry.value?.hp ?? 45
  return Math.floor(2 * base * props.slot.lvl / 100) + props.slot.lvl + 10
})
const hpPct   = computed(() => Math.max(0, Math.min(1, props.slot.hp / maxHP.value)))
const hpColor = computed(() => hpPct.value > 0.5 ? '#2ecc40' : hpPct.value > 0.25 ? '#ffdc00' : '#ff4136')
const xpThreshold = computed(() => props.slot.lvl * 10)
const xpPct = computed(() => {
  const t = xpThreshold.value
  return t > 0 ? Math.min(1, (props.slot.xp % t) / t) : 0
})

// Stats
const statRows = computed(() => {
  const s = entry.value
  if (!s) return []
  return [
    { key: 'hp',  label: 'HP',   value: s.hp,  color: '#ef4444' },
    { key: 'atk', label: 'ATK',  value: s.atk, color: '#f97316' },
    { key: 'def', label: 'DEF',  value: s.def, color: '#eab308' },
    { key: 'spa', label: 'SP.A', value: s.spa, color: '#3b82f6' },
    { key: 'spd', label: 'SP.D', value: s.spd, color: '#22c55e' },
    { key: 'spe', label: 'SPE',  value: s.spe, color: '#a855f7' },
  ]
})
const statTotal = computed(() => statRows.value.reduce((s, r) => s + r.value, 0))

// Moves
const moveRows = computed(() =>
  props.slot.moves
    .filter(m => m.id > 0)
    .map(m => {
      const def = MOVES[String(m.id)]
      return {
        id: m.id,
        name: def?.name ?? `Move ${m.id}`,
        type: def?.type ?? 1,
        power: def?.power ?? 0,
        maxPP: def?.pp ?? m.pp,
        currentPP: m.pp,
      }
    })
)

function ppColor(cur: number, max: number) {
  const r = cur / max
  return r > 0.5 ? '#2ecc40' : r > 0.25 ? '#ffdc00' : '#ff4136'
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
*, *::before, *::after { box-sizing: border-box; }

.pm-backdrop {
  position: fixed;
  inset: 0;
  z-index: 700;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.pm-panel {
  width: 420px;
  max-width: calc(100vw - 32px);
  max-height: 90vh;
  overflow-y: auto;
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-top: 5px solid #444;
  border-radius: 6px;
  box-shadow: 0 8px 0 #1a3080, 0 16px 48px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
  animation: pm-in 0.18s cubic-bezier(.34,1.4,.64,1);
  transition: border-top-color 0.3s;
}
@keyframes pm-in {
  from { opacity: 0; transform: scale(0.88) translateY(20px); }
  to   { opacity: 1; transform: scale(1)    translateY(0);    }
}

/* Header */
.pm-header {
  padding: 14px 14px 12px;
  border-bottom: 2px solid #c0d4f0;
  position: relative;
  flex-shrink: 0;
}
.pm-close {
  position: absolute;
  top: 8px; right: 10px;
  background: rgba(0,0,0,0.08);
  border: 2px solid rgba(0,0,0,0.15);
  border-radius: 3px;
  color: #444;
  width: 26px; height: 26px;
  font-size: 11px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
}
.pm-close:hover { background: rgba(200,32,32,0.12); color: #c82020; }

.pm-hero {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding-right: 30px;
}
.pm-art {
  width: 80px; height: 80px;
  image-rendering: pixelated;
  flex-shrink: 0;
  background: rgba(255,255,255,0.5);
  border-radius: 4px;
}
.pm-hero-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 5px; }

.pm-name-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  flex-wrap: wrap;
}
.pm-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 9px;
  color: #181830;
  word-break: break-word;
}
.pm-shiny { font-size: 12px; }
.pm-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #6080a0;
}
.pm-level {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #2848a8;
  letter-spacing: 0.3px;
}

.pm-types { display: flex; gap: 4px; flex-wrap: wrap; }
.type-badge {
  font-size: 8px;
  font-weight: 700;
  color: #fff;
  padding: 2px 7px;
  border-radius: 3px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.4);
  letter-spacing: 0.3px;
}

/* Bars */
.pm-bar-row {
  display: flex;
  align-items: center;
  gap: 6px;
}
.pm-bar-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #6080a0;
  min-width: 14px;
  flex-shrink: 0;
}
.pm-bar-track {
  flex: 1;
  height: 6px;
  background: rgba(0,0,0,0.12);
  border-radius: 3px;
  overflow: hidden;
}
.pm-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease-out, background-color 0.3s;
}
.pm-bar-fill.xp { background: #f0c000 !important; }
.pm-bar-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #444;
  min-width: 52px;
  text-align: right;
  flex-shrink: 0;
}

/* Sections */
.pm-section {
  padding: 12px 14px;
  border-bottom: 2px solid #c0d4f0;
}
.pm-moves-section { border-bottom: none; }
.pm-section-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #c82020;
  letter-spacing: 0.5px;
  margin-bottom: 10px;
}

/* Stat rows */
.stat-row { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
.stat-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #6080a0;
  min-width: 28px;
  flex-shrink: 0;
}
.stat-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  color: #181830;
  min-width: 24px;
  text-align: right;
  flex-shrink: 0;
}
.stat-bar-bg {
  flex: 1;
  height: 7px;
  background: #d0e4f0;
  border-radius: 2px;
  overflow: hidden;
  border: 1px solid #b0c8e0;
}
.stat-bar-fill { height: 100%; border-radius: 2px; transition: width 0.4s ease-out; }
.stat-total {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #6080a0;
  text-align: right;
  margin-top: 4px;
}
.stat-total-val { color: #181830; margin-left: 5px; }

/* Move rows */
.pm-no-moves {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #a0b0c0;
  text-align: center;
  padding: 8px;
}
.move-row {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 6px 0;
  border-bottom: 1px solid #dde8f4;
}
.move-row:last-child { border-bottom: none; }
.move-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #181830;
  flex: 1;
  min-width: 0;
}
.move-type {
  font-size: 7px;
  font-weight: 700;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.4);
  flex-shrink: 0;
  white-space: nowrap;
}
.move-pwr {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #c04020;
  min-width: 18px;
  text-align: center;
  flex-shrink: 0;
}
.move-pp-track {
  width: 44px;
  height: 5px;
  background: rgba(0,0,0,0.12);
  border-radius: 3px;
  overflow: hidden;
  flex-shrink: 0;
}
.move-pp-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
.move-pp-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  color: #6080a0;
  min-width: 34px;
  text-align: right;
  flex-shrink: 0;
}

/* Evolve button */
.pm-evolve-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px;
  letter-spacing: 0.4px;
  color: #fff;
  background: linear-gradient(135deg, #7c3aed, #a855f7);
  border: 2px solid #5b21b6;
  border-radius: 3px;
  padding: 7px 12px;
  cursor: pointer;
  box-shadow: 0 3px 0 #3b0764, 0 0 10px rgba(168,85,247,0.4);
  text-shadow: 0 0 6px #fff, 0 1px 0 rgba(0,0,0,0.4);
  transition: filter 0.12s, box-shadow 0.12s;
  align-self: flex-start;
  white-space: nowrap;
  animation: evo-pulse-btn 1.6s ease-in-out infinite;
}
.pm-evolve-btn:hover {
  filter: brightness(1.15);
  box-shadow: 0 3px 0 #3b0764, 0 0 18px rgba(168,85,247,0.65);
}
.pm-evolve-btn:active {
  transform: translateY(2px);
  box-shadow: 0 1px 0 #3b0764;
}
.pm-evolve-special {
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  border-color: #1e3a8a;
  box-shadow: 0 3px 0 #1e3a8a, 0 0 10px rgba(59,130,246,0.35);
  animation: none;
}
.pm-evolve-special:hover {
  box-shadow: 0 3px 0 #1e3a8a, 0 0 16px rgba(59,130,246,0.6);
}
.pm-eevee-evos {
  display: flex;
  flex-direction: column;
  gap: 5px;
  align-self: flex-start;
}
@keyframes evo-pulse-btn {
  0%, 100% { box-shadow: 0 3px 0 #3b0764, 0 0 10px rgba(168,85,247,0.4); }
  50%       { box-shadow: 0 3px 0 #3b0764, 0 0 20px rgba(168,85,247,0.75); }
}
</style>
