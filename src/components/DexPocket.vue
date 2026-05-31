<template>
  <div class="dp-root" ref="panelRef" :style="dragStyle">

    <!-- Header strip — drag handle -->
    <div class="dp-bar" @pointerdown="onHandlePointerDown" style="cursor:grab;touch-action:none">
      <img src="/textures/Nav/Pokedex/0.png" class="dp-bar-icon" alt="" />
      <span class="dp-bar-title">POKÉDEX</span>
      <button class="dp-close-btn dp-fs-btn" @click="openFullscreen" @pointerdown.stop title="Fullscreen">⛶</button>
      <button class="dp-close-btn" @click="$emit('close')" @pointerdown.stop title="Close">✕</button>
    </div>

    <div class="dp-body">

      <!-- ① LIST -->
      <div class="dp-list">
        <div class="list-head">
          <div class="list-title-row">
            <span class="list-title">DEX</span>
            <button class="tpe-btn" :class="{ active: tpeMode }" @click="tpeMode = !tpeMode" title="True Player Experience">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <div class="list-counts">
            <div class="cnt-row">
              <span class="cnt-caught">{{ caughtCount }}✓</span>
              <span class="cnt-seen">{{ seenCount }}👁</span>
            </div>
            <div class="cnt-row">
              <span class="cnt-shiny-caught">{{ shinyCaughtCount }}✨✓</span>
              <span class="cnt-shiny-seen">{{ shinySeenCount }}✨👁</span>
            </div>
          </div>
        </div>
        <div class="list-search-bar">
          <input v-model="search" class="list-input" placeholder="Search…" aria-label="Search Pokémon" />
        </div>
        <div class="list-scroll" role="listbox">
          <div
            v-for="i in 151" :key="i"
            v-show="matchesSearch(i)"
            class="list-item"
            :class="{ active: selectedId === i }"
            role="option" :aria-selected="selectedId === i"
            @click="selectPokemon(i)"
          >
            <img :src="`/textures/Mini/Png/${padId(i)}.png`" :alt="pokedex(i)" width="20" height="20" loading="lazy"
                 :class="{ 'sprite-unknown': !isKnown(i) }" />
            <span class="li-num">#{{ padId(i) }}</span>
            <span class="li-name">{{ isKnown(i) ? pokedex(i) : '???' }}</span>
            <template v-if="isKnown(i)">
              <span v-if="saveStore.pokedex[String(i)] === 'caught'" class="li-dot dot-caught">●</span>
              <span v-else-if="saveStore.pokedex[String(i)] === 'seen'" class="li-dot dot-seen">●</span>
              <span v-if="saveStore.shinydex[String(i)] === 'caught'" class="li-dot dot-shiny-caught">✨</span>
              <span v-else-if="saveStore.shinydex[String(i)] === 'seen'" class="li-dot dot-shiny-seen">✨</span>
            </template>
          </div>
        </div>
      </div>

      <!-- ② DETAIL COLUMN -->
      <div class="dp-detail">

        <!-- Title -->
        <div class="detail-title" :style="selectedKnown ? heroStyle : {}">
          <img class="hero-gif" :src="`/textures/Mini/Gif/${padId(selectedId)}.gif`" alt="" width="40" height="40"
               :class="{ 'sprite-unknown': !selectedKnown }" />
          <div class="hero-info">
            <div class="hero-top">
              <h2 class="hero-name">{{ selectedKnown ? selectedName : '???' }}</h2>
              <span class="hero-num">#{{ padId(selectedId) }}</span>
            </div>
            <div class="hero-bottom">
              <div class="hero-types">
                <template v-if="selectedKnown">
                  <span v-for="t in selectedTypes" :key="t" class="type-badge" :style="{ backgroundColor: TYPE_COLORS[t] ?? '#888' }">
                    {{ TYPE_NAMES[t] ?? '???' }}
                  </span>
                </template>
                <span v-else class="type-badge" style="background:#555">???</span>
              </div>
              <template v-if="selectedKnown">
                <span v-if="saveStore.pokedex[String(selectedId)] === 'caught'" class="dex-status caught">● CAUGHT</span>
                <span v-else-if="saveStore.pokedex[String(selectedId)] === 'seen'" class="dex-status seen">● SEEN</span>
                <span v-else class="dex-status unknown">? UNKNOWN</span>
                <span v-if="saveStore.shinydex[String(selectedId)] === 'caught'" class="dex-status shiny-caught">✨ SHINY</span>
              </template>
              <span v-else class="dex-status unknown">? UNKNOWN</span>
            </div>
          </div>
        </div>

        <!-- TV -->
        <div class="art-tv">
          <div class="art-tv-screen" :class="{ 'is-off': !tvOn }">
            <div class="art-tv-crt-wrap">
              <Transition name="channel" mode="out-in">
                <div class="art-tv-content" :key="selectedId">
                  <template v-if="selectedKnown">
                    <img class="art-img" :src="`/textures/Art/${padId(selectedId)}.png`" :alt="selectedName" width="300" height="300" />
                    <template v-if="pokemonDescription">
                      <div class="art-dialogue" v-show="bubbleVisible">
                        <button class="dlg-toggle" @click.stop="bubbleVisible = false" aria-label="Hide description">▼</button>
                        <p class="art-dialogue-text">{{ pokemonDescription }}<span class="art-dlg-cur">♥</span></p>
                      </div>
                      <button class="dlg-show-tab" v-show="!bubbleVisible" @click="bubbleVisible = true" aria-label="Show description">▲</button>
                    </template>
                  </template>
                  <template v-else>
                    <img class="art-img" src="/textures/Art/000.png" alt="???" width="300" height="300" />
                    <div class="art-dialogue">
                      <p class="art-dialogue-text">You haven't seen this Pokémon yet.<span class="art-dlg-cur">♥</span></p>
                    </div>
                  </template>
                </div>
              </Transition>
            </div>
          </div>
          <div class="art-bezel-bar">
            <span class="art-led" :class="{ 'led-off': !tvOn }" @click="tvOn = !tvOn" title="Power"></span>
            <div class="art-speaker"><span></span><span></span><span></span><span></span><span></span></div>
            <span class="art-model">PDX-151</span>
          </div>
        </div>

        <!-- Evolution -->
        <div class="detail-section detail-evo">
          <div class="section-heading">EVOLUTION</div>
          <template v-for="shiny in (showShinyEvo ? [false, true] : [false])" :key="String(shiny)">
            <div v-if="shiny" class="evo-divider"><span>✨ Shiny</span></div>
            <div v-else-if="showShinyEvo" class="evo-divider"><span>Normal</span></div>
            <div v-if="evoChain" class="evo-chain" :class="{ 'evo-chain-shiny': shiny }">
              <template v-for="stage in evoChain" :key="stage.id">
                <button class="evo-stage" :class="{ 'evo-current': selectedId === stage.id, 'evo-unknown': !isKnown(stage.id) }"
                        @click="selectPokemon(stage.id)" :title="isKnown(stage.id) ? pokedex(stage.id) : '???'">
                  <img class="evo-sprite" :src="spriteSrc(stage.id, shiny)" :class="{ 'sprite-unknown': !isKnown(stage.id) }"
                       :alt="isKnown(stage.id) ? pokedex(stage.id) : '???'" width="32" height="32" />
                  <span class="evo-name">{{ isKnown(stage.id) ? pokedex(stage.id) : '???' }}</span>
                </button>
                <div v-if="stage.next" class="evo-connector">
                  <span class="evo-arr">▶</span>
                  <span class="evo-trig">{{ triggerLabel(stage.next) }}</span>
                </div>
              </template>
            </div>
            <div v-else-if="isEeveeFamily" class="evo-chain evo-eevee" :class="{ 'evo-chain-shiny': shiny }">
              <button class="evo-stage" :class="{ 'evo-current': selectedId === EEVEE_BASE_ID, 'evo-unknown': !isKnown(EEVEE_BASE_ID) }"
                      @click="selectPokemon(EEVEE_BASE_ID)" :title="isKnown(EEVEE_BASE_ID) ? 'Eevee' : '???'">
                <img class="evo-sprite" :src="spriteSrc(EEVEE_BASE_ID, shiny)" :class="{ 'sprite-unknown': !isKnown(EEVEE_BASE_ID) }" alt="Eevee" width="32" height="32" />
                <span class="evo-name">{{ isKnown(EEVEE_BASE_ID) ? 'Eevee' : '???' }}</span>
              </button>
              <div class="evo-branches">
                <div v-for="branch in EEVEE_BRANCHES" :key="branch.id" class="evo-branch">
                  <div class="evo-connector">
                    <span class="evo-arr">▶</span>
                    <span class="evo-trig">{{ branch.stone }}</span>
                  </div>
                  <button class="evo-stage" :class="{ 'evo-current': selectedId === branch.id, 'evo-unknown': !isKnown(branch.id) }"
                          @click="selectPokemon(branch.id)" :title="isKnown(branch.id) ? pokedex(branch.id) : '???'">
                    <img class="evo-sprite" :src="spriteSrc(branch.id, shiny)" :class="{ 'sprite-unknown': !isKnown(branch.id) }"
                         :alt="isKnown(branch.id) ? pokedex(branch.id) : '???'" width="32" height="32" />
                    <span class="evo-name">{{ isKnown(branch.id) ? pokedex(branch.id) : '???' }}</span>
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="evo-chain">
              <button class="evo-stage evo-current" :class="{ 'evo-unknown': !selectedKnown }" @click="selectPokemon(selectedId)">
                <img class="evo-sprite" :src="spriteSrc(selectedId, shiny)" :class="{ 'sprite-unknown': !selectedKnown }"
                     :alt="selectedKnown ? selectedName : '???'" width="32" height="32" />
                <span class="evo-name">{{ selectedKnown ? selectedName : '???' }}</span>
              </button>
            </div>
          </template>
        </div>

        <!-- Base Stats -->
        <div class="detail-section detail-stats">
          <div class="section-heading">BASE STATS</div>
          <template v-if="selectedCaught">
            <div v-for="s in statRows" :key="s.key" class="stat-row">
              <span class="stat-lbl">{{ s.label }}</span>
              <span class="stat-val">{{ s.value }}</span>
              <div class="stat-bar-bg"><div class="stat-bar-fill" :style="{ width: Math.round(s.value / 255 * 100) + '%', backgroundColor: s.color }"></div></div>
            </div>
            <div class="stats-total">TOTAL <span class="stats-total-val">{{ statTotal }}</span></div>
          </template>
          <template v-else>
            <div v-for="s in statRows" :key="s.key" class="stat-row">
              <span class="stat-lbl">{{ s.label }}</span>
              <span class="stat-val stat-unknown">???</span>
              <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:0%"></div></div>
            </div>
            <div class="stats-total">TOTAL <span class="stats-total-val stat-unknown">???</span></div>
          </template>
        </div>

        <!-- Level-up Moves -->
        <div class="detail-section detail-moves">
          <div class="section-heading-red">
            <span>LEVEL-UP MOVES</span>
            <span class="section-count">{{ selectedCaught ? learnsetRows.length + ' moves' : '???' }}</span>
          </div>
          <div class="learnset-table" role="table">
            <div class="lt-head" role="row">
              <span class="lt-lv">LV</span>
              <span class="lt-name">MOVE</span>
              <span class="lt-type">TYPE</span>
              <span class="lt-pwr">PWR</span>
              <span class="lt-pp">PP</span>
            </div>
            <template v-if="selectedCaught">
              <div v-for="entry in learnsetRows" :key="entry.id" class="lt-row" role="row">
                <span class="lt-lv">{{ entry.level === 0 ? '—' : entry.level }}</span>
                <span class="lt-name">{{ entry.name }}</span>
                <span class="lt-type"><span class="type-badge-sm" :style="{ backgroundColor: TYPE_COLORS[entry.type] ?? '#888' }">{{ TYPE_NAMES[entry.type] ?? '???' }}</span></span>
                <span class="lt-pwr">{{ entry.power > 0 ? entry.power : '—' }}</span>
                <span class="lt-pp">{{ entry.pp }}</span>
              </div>
              <div v-if="learnsetRows.length === 0" class="lt-empty">No learnset data</div>
            </template>
            <div v-else-if="selectedKnown" class="lt-catch-hint">Catch this Pokémon to unlock</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useSaveStore } from '../stores/save'
import { useModalStore } from '../stores/modals'
import { useDraggable } from '../composables/useDraggable'
import { pokedex, padId } from '../data/pokemon'
import { findChain, isEeveeLine, EEVEE_BASE_ID, EEVEE_BRANCHES, type EvoTrigger } from '../data/evo-chains'
import statsData        from '../data/pokemon-stats.json'
import movesData        from '../data/moves.json'
import learnsetsData    from '../data/learnsets.json'
import descriptionsData from '../data/pokedex-descriptions.json'

const props = withDefaults(defineProps<{ initialId?: number }>(), { initialId: 1 })
const emit  = defineEmits<{ close: [] }>()
const modalStore = useModalStore()

function openFullscreen() {
  emit('close')
  modalStore.openDex(props.initialId)
}

const panelRef = ref<HTMLElement | null>(null)
const { dragStyle, onHandlePointerDown } = useDraggable('pkw_dex_pos', panelRef)

type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number; type1: number; type2: number }
type MoveEntry  = { name: string; type: number; power: number; acc: number; pp: number }

const STATS        = statsData        as Record<string, StatsEntry>
const MOVES        = movesData        as Record<string, MoveEntry>
const LEARNSETS    = learnsetsData    as Record<string, { id: number; level: number }[]>
const DESCRIPTIONS = descriptionsData as Record<string, string>

const saveStore = useSaveStore()

onMounted(() => { window.addEventListener('keydown', onKeyDown) })
onUnmounted(() => { window.removeEventListener('keydown', onKeyDown) })
function onKeyDown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
  if (e.key === 'ArrowRight' && selectedId.value < 151) selectPokemon(selectedId.value + 1)
  if (e.key === 'ArrowLeft'  && selectedId.value > 1)   selectPokemon(selectedId.value - 1)
}

const TYPE_COLORS: Record<number, string> = {
  1:  '#7a7a5a', 2:  '#d85020', 3:  '#982018', 4:  '#3a5ab8',
  5:  '#702888', 6:  '#a88c00', 7:  '#a87830', 8:  '#b82050',
  9:  '#887010', 10: '#4aaab0', 11: '#5a7808', 12: '#3808b8',
  13: '#382878', 15: '#389820', 16: '#6050b8',
}
const TYPE_NAMES: Record<number, string> = {
  1: 'NORMAL', 2: 'FIRE', 3: 'FIGHT', 4: 'WATER', 5: 'POISON',
  6: 'ELECTR', 7: 'GROUND', 8: 'PSYCHC', 9: 'ROCK', 10: 'ICE',
  11: 'BUG', 12: 'DRAGON', 13: 'GHOST', 15: 'GRASS', 16: 'FLYING',
}

const tpeMode = ref(true)
function isKnown(id: number): boolean { return !tpeMode.value || !!saveStore.pokedex[String(id)] }

const tvOn          = ref(true)
const bubbleVisible = ref(false)

const search = ref('')
const caughtCount      = computed(() => Object.values(saveStore.pokedex).filter(v => v === 'caught').length)
const seenCount        = computed(() => Object.values(saveStore.pokedex).filter(v => v === 'seen').length)
const shinyCaughtCount = computed(() => Object.values(saveStore.shinydex).filter(v => v === 'caught').length)
const shinySeenCount   = computed(() => Object.values(saveStore.shinydex).filter(v => v === 'seen').length)
function matchesSearch(id: number): boolean {
  if (!search.value) return true
  const q = search.value.toLowerCase()
  return pokedex(id).toLowerCase().includes(q) || String(id).includes(q)
}

const selectedId     = ref(props.initialId >= 1 && props.initialId <= 151 ? props.initialId : 1)
const selectedName   = computed(() => pokedex(selectedId.value))
const selectedKnown  = computed(() => isKnown(selectedId.value))
const selectedCaught = computed(() => !tpeMode.value || saveStore.pokedex[String(selectedId.value)] === 'caught')

function selectPokemon(id: number) {
  if (id < 1 || id > 151) return
  selectedId.value = id
}

const selectedTypes = computed(() => {
  const s = STATS[String(selectedId.value)]
  if (!s) return [1]
  return s.type1 !== s.type2 ? [s.type1, s.type2] : [s.type2]
})
const primaryColor = computed(() => TYPE_COLORS[selectedTypes.value[0]] ?? '#444')
const heroStyle    = computed(() => ({ borderTopColor: primaryColor.value, borderTopWidth: '4px' }))

const pokemonDescription = computed(() => DESCRIPTIONS[String(selectedId.value)] ?? '')

const statRows = computed(() => {
  const s = STATS[String(selectedId.value)]
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
const statTotal = computed(() => statRows.value.reduce((sum, r) => sum + r.value, 0))

const evoChain      = computed(() => findChain(selectedId.value))
const isEeveeFamily = computed(() => isEeveeLine(selectedId.value))
const showShinyEvo  = computed(() => saveStore.shinydex[String(selectedId.value)] === 'caught')

function spriteSrc(id: number, shiny: boolean): string {
  return shiny
    ? `/textures/Battle/Shiny/Front/Png/${id}.png`
    : `/textures/Battle/Normal/Front/Png/${id}.png`
}
function triggerLabel(t: EvoTrigger): string {
  if (t.type === 'level') return `Lv.${t.level}`
  if (t.type === 'stone') return t.stone
  return 'Trade'
}

interface LearnRow { id: number; level: number; name: string; type: number; power: number; acc: number; pp: number }
const learnsetRows = computed((): LearnRow[] => {
  const raw = LEARNSETS[String(selectedId.value)] ?? []
  return raw
    .map(e => { const m = MOVES[String(e.id)]; if (!m) return null; return { id: e.id, level: e.level, name: m.name, type: m.type, power: m.power, acc: m.acc, pp: m.pp } })
    .filter(Boolean)
    .sort((a, b) => a!.level - b!.level) as LearnRow[]
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=VT323:wght@400&display=swap');
*, *::before, *::after { box-sizing: border-box; }

/* ── Root ── */
.dp-root {
  position: fixed;
  bottom: 90px;
  right: 16px;
  z-index: 495;
  max-height: 85vh;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 22px 22px;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 48px rgba(0,0,0,0.55), 0 0 0 3px rgba(255,255,255,0.08);
  animation: pop-in 0.2s cubic-bezier(.34,1.4,.64,1);
  width: fit-content;
}
@keyframes pop-in {
  from { opacity: 0; transform: scale(0.88) translateY(20px); }
  to   { opacity: 1; transform: scale(1)    translateY(0);    }
}

/* ── Header ── */
.dp-bar {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 10px;
  padding: 8px 14px;
  background: #c82020;
  border-bottom: 3px solid #881010;
  box-shadow: 0 3px 0 #660808;
  flex-shrink: 0;
  border-radius: 8px 0 0 0;
}
.dp-bar-icon { width: 20px; height: 20px; image-rendering: pixelated; filter: invert(1); opacity: 0.9; }
.dp-bar-title { font-family: 'Press Start 2P', monospace; font-size: 10px; color: #fff; letter-spacing: 2px; text-shadow: 1px 1px 0 #660808; flex: 1; }
.dp-close-btn { font-family: 'Press Start 2P', monospace; font-size: 10px; background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.35); color: #fff; width: 28px; height: 28px; border-radius: 2px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.15s; }
.dp-close-btn:hover { background: rgba(255,255,255,0.3); }

/* ── Body row ── */
.dp-body {
  display: flex;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* ── List ── */
.dp-list {
  width: 160px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 6px;
  overflow: hidden;
  margin: 16px;
}
.list-head { display: flex; align-items: center; justify-content: space-between; padding: 9px 9px 7px; background: #c82020; border-bottom: 3px solid #881010; flex-shrink: 0; }
.list-title-row { display: flex; align-items: center; gap: 7px; }
.list-title { font-size: 10px; font-weight: 700; color: #fff; letter-spacing: 0.5px; text-shadow: 1px 1px 0 #660808; }
.list-counts { font-family: 'VT323', monospace; font-size: 13px; display: flex; flex-direction: column; align-items: flex-end; gap: 1px; }
.cnt-row { display: flex; gap: 6px; }
.cnt-caught { color: #c8ffc8; } .cnt-seen { color: #ffe0c0; }
.cnt-shiny-caught { color: #ffe97a; } .cnt-shiny-seen { color: #ffd0f0; }
.tpe-btn { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; color: rgba(255,255,255,0.5); width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 10px; transition: background 0.15s, color 0.15s; }
.tpe-btn.active { background: rgba(255,255,255,0.9); color: #c82020; }
.tpe-btn:not(.active):hover { background: rgba(255,255,255,0.3); color: #fff; }
.list-search-bar { padding: 5px 7px; border-bottom: 1px solid #b8d4e8; background: #e8f4fc; flex-shrink: 0; }
.list-input { width: 100%; background: #fff; border: 2px solid #a0c0d8; border-radius: 2px; color: #181830; font-family: 'VT323', monospace; font-size: 14px; padding: 3px 5px; outline: none; transition: border-color 0.15s; }
.list-input::placeholder { color: #a0c0d8; }
.list-input:focus { border-color: #2848a8; }
.list-scroll { flex: 1; overflow-y: auto; min-height: 0; scrollbar-width: thin; scrollbar-color: #a0c0d8 #e8f4fc; }
.list-scroll::-webkit-scrollbar { width: 4px; }
.list-scroll::-webkit-scrollbar-thumb { background: #a0c0d8; border-radius: 2px; }
.list-item { display: flex; align-items: center; gap: 4px; padding: 3px 7px; cursor: pointer; border-bottom: 1px solid #d0e8f4; min-height: 28px; transition: background 0.1s; }
.list-item:hover { background: #e8f4fc; }
.list-item.active { background: #fff4f4; border-left: 3px solid #c82020; padding-left: 4px; }
.list-item img { image-rendering: pixelated; flex-shrink: 0; }
.li-num  { font-family: 'VT323', monospace; font-size: 11px; color: #6080a0; min-width: 26px; flex-shrink: 0; }
.li-name { font-family: 'VT323', monospace; font-size: 13px; color: #181830; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.li-dot  { font-size: 8px; flex-shrink: 0; }
.dot-caught { color: #1a8030; } .dot-seen { color: #c07010; }
.sprite-unknown { filter: brightness(0); }

/* ── Detail column ── */
.dp-detail {
  width: 260px;
  flex-shrink: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-right: 16px;
  padding-bottom: 16px;
  scrollbar-width: thin;
  scrollbar-color: #a0c0d8 transparent;
}
.dp-detail::-webkit-scrollbar { width: 4px; }
.dp-detail::-webkit-scrollbar-thumb { background: #a0c0d8; border-radius: 2px; }

/* Title */
.detail-title {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px 8px;
  background: #f8f4ff;
  border-bottom: 3px solid #2848a8;
  flex-shrink: 0;
  margin-top: 16px;
  transition: border-top-color 0.3s;
}
.hero-gif { image-rendering: pixelated; flex-shrink: 0; }
.hero-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.hero-top { display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap; }
.hero-name { font-size: 13px; font-weight: 700; color: #181830; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hero-num  { font-family: 'VT323', monospace; font-size: 14px; color: #6080a0; flex-shrink: 0; }
.hero-bottom { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.hero-types { display: flex; gap: 3px; flex-wrap: wrap; }
.type-badge { font-size: 8px; font-weight: 700; color: #fff; padding: 2px 6px; border-radius: 3px; text-shadow: 0 1px 0 rgba(0,0,0,0.4); }
.dex-status { font-size: 8px; font-weight: 600; padding: 2px 5px; border-radius: 3px; border: 1px solid; white-space: nowrap; }
.dex-status.caught       { color: #1a6020; border-color: #1a6020; background: #e8f8e8; }
.dex-status.seen         { color: #8a4800; border-color: #c07010; background: #fff8e0; }
.dex-status.unknown      { color: #a0b0c0; border-color: #b8d4e8; background: #f0f8ff; }
.dex-status.shiny-caught { color: #7a5500; border-color: #e6b800; background: #fffbe0; }
.evo-unknown { opacity: 0.45; }
.stat-unknown { color: #aaa; font-style: italic; }

/* TV — full width of detail column */
.art-tv {
  background: linear-gradient(160deg, #252838 0%, #181a28 100%);
  flex-shrink: 0;
}
@keyframes crt-flicker { 0%,89%,91%,94%,100% { filter:brightness(1); } 90% { filter:brightness(0.96); } 92% { filter:brightness(0.98); } 95% { filter:brightness(0.97); } }
.art-tv-screen { margin: 8px 8px 0; border-radius: 4px; overflow: hidden; background: #fff; display: flex; flex-direction: column; position: relative; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1), inset 2px 2px 10px rgba(0,0,0,0.06); animation: crt-flicker 6s infinite; }
.art-tv-screen::after { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at center, transparent 52%, rgba(0,0,0,0.18) 100%), repeating-linear-gradient(0deg, transparent 0px, transparent 2px, rgba(0,0,0,0.022) 2px, rgba(0,0,0,0.022) 4px); pointer-events:none; z-index:10; }
.art-tv-screen::before { content:''; position:absolute; top:0; left:0; right:0; height:28%; background:linear-gradient(180deg,rgba(255,255,255,0.13) 0%,transparent 100%); pointer-events:none; z-index:11; }
.art-tv-content { display:flex; flex-direction:column; }
.channel-leave-active { animation: ch-out 0.1s ease-in  forwards; }
.channel-enter-active { animation: ch-in  0.2s ease-out forwards; }
@keyframes ch-out { 0%{opacity:1;filter:brightness(1) blur(0px);transform:scaleY(1);}60%{opacity:1;filter:brightness(3) blur(1px);transform:scaleY(0.98);}100%{opacity:0;filter:brightness(8) blur(2px);transform:scaleY(0.95);} }
@keyframes ch-in  { 0%{opacity:0;filter:brightness(6) blur(2px);transform:scaleY(0.95);}45%{opacity:1;filter:brightness(1.6) blur(0.5px);transform:scaleY(1.01);}100%{opacity:1;filter:brightness(1) blur(0px);transform:scaleY(1);} }
.art-img { display:block; width:100%; height:auto; aspect-ratio:1; object-fit:contain; image-rendering:pixelated; position:relative; z-index:1; }
.art-dialogue { position:absolute; bottom:5px; left:5px; right:5px; z-index:5; padding:6px 9px 8px; background:#fff; border:2px solid #181830; border-radius:5px; box-shadow:0 0 0 2px #fff,0 0 0 4px #181830; }
.dlg-toggle { position:absolute; top:-13px; right:0; width:18px; height:13px; background:#fff; border:2px solid #181830; border-bottom:none; border-radius:3px 3px 0 0; color:#181830; font-size:6px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; transition:background 0.15s; }
.dlg-show-tab { position:absolute; bottom:5px; right:5px; width:20px; height:14px; background:#181830; border:2px solid #181830; border-radius:3px; color:#fff; font-size:6px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; z-index:5; }
.art-dialogue-text { font-family:'VT323',monospace; font-size:13px; line-height:1.5; color:#181830; margin:0; }
.art-dlg-cur { color:#c82020; margin-left:2px; }
.art-bezel-bar { display:flex; align-items:center; justify-content:space-between; padding:5px 10px 7px; }
.art-tv-screen.is-off { background:#060608; animation:none; }
.art-tv-crt-wrap { display:flex; flex-direction:column; position:relative; transform-origin:center center; transition:transform 0.18s ease-in,opacity 0.12s ease-in,filter 0.18s ease-in; }
.art-tv-screen.is-off .art-tv-crt-wrap { transform:scaleY(0.03); filter:brightness(4); opacity:0; }
.art-led { width:5px; height:5px; border-radius:50%; background:#c82020; box-shadow:0 0 4px #c82020; flex-shrink:0; cursor:pointer; transition:background 0.2s,box-shadow 0.2s; }
.art-led.led-off { background:#3a0808; box-shadow:none; }
.art-speaker { display:flex; gap:2px; align-items:center; }
.art-speaker span { display:block; width:2px; height:8px; background:rgba(255,255,255,0.18); border-radius:1px; }
.art-model { font-family:'VT323',monospace; font-size:10px; color:rgba(255,255,255,0.22); letter-spacing:1px; flex-shrink:0; }

/* Shared section card */
.detail-section {
  background: #f0f8ff;
  border-top: 3px solid #2848a8;
  flex-shrink: 0;
}

/* Section headings */
.section-heading {
  font-size: 9px; font-weight: 700; color: #c82020;
  letter-spacing: 0.5px; padding: 8px 12px 6px;
  border-bottom: 1px solid #d0e4f0;
}
.section-heading-red {
  display: flex; align-items: center; justify-content: space-between;
  padding: 7px 12px; background: #c82020;
  border-bottom: 3px solid #881010;
  font-size: 9px; font-weight: 700; color: #fff;
  letter-spacing: 0.5px; text-shadow: 1px 1px 0 #660808;
}
.section-count { font-family: 'VT323', monospace; font-size: 14px; color: #ffc8c8; font-weight: normal; }

/* Evolution */
.detail-evo { padding: 6px 10px 8px; overflow-x: auto; }
.evo-chain { display:flex; align-items:center; gap:3px; flex-wrap:nowrap; min-width: max-content; }
.evo-chain-shiny { background:linear-gradient(90deg,rgba(255,220,80,0.08),rgba(255,180,240,0.08)); border-radius:6px; padding:3px 5px; }
.evo-divider { width:100%; display:flex; align-items:center; gap:5px; margin:1px 0; }
.evo-divider span { font-family:'Press Start 2P',monospace; font-size:6px; color:#2848a8; opacity:0.7; }
.evo-divider::after { content:''; flex:1; height:1px; background:#c0d4f0; }
.evo-eevee { flex-wrap:nowrap; align-items:flex-start; }
.evo-branches { display:flex; flex-direction:column; gap:3px; }
.evo-branch { display:flex; align-items:center; gap:5px; }
.evo-stage { display:flex; flex-direction:column; align-items:center; gap:1px; padding:2px 5px; background:none; border:2px solid transparent; border-radius:6px; cursor:pointer; transition:background 0.15s,border-color 0.15s; }
.evo-stage:hover { background:#e8f0fc; border-color:#2848a8; }
.evo-stage.evo-current { background:#dce8ff; border-color:#2848a8; }
.evo-stage.evo-current .evo-name { color:#2848a8; font-weight:bold; }
.evo-sprite { image-rendering:pixelated; width:24px; height:24px; }
.evo-name { font-family:'VT323',monospace; font-size:11px; color:#181830; line-height:1; white-space:nowrap; }
.evo-connector { display:flex; flex-direction:column; align-items:center; gap:1px; flex-shrink:0; }
.evo-arr  { font-size:8px; color:#4060a0; line-height:1; }
.evo-trig { font-family:'VT323',monospace; font-size:10px; color:#4060a0; white-space:nowrap; line-height:1; }

/* Stats */
.detail-stats { padding: 8px 12px 12px; }
.stat-row { display:flex; align-items:center; gap:7px; margin-bottom:7px; }
.stat-lbl { font-size:9px; font-weight:600; color:#6080a0; min-width:32px; flex-shrink:0; }
.stat-val { font-family:'VT323',monospace; font-size:15px; color:#181830; min-width:28px; text-align:right; flex-shrink:0; }
.stat-bar-bg { flex:1; height:7px; background:#d0e4f0; border-radius:2px; overflow:hidden; border:1px solid #b0c8e0; }
.stat-bar-fill { height:100%; border-radius:2px; transition:width 0.4s ease-out; }
.stats-total { font-size:9px; font-weight:600; color:#6080a0; text-align:right; margin-top:4px; }
.stats-total-val { color:#181830; margin-left:4px; }
.stat-unknown { color:#aaa; font-style:italic; }

/* Moves */
.detail-moves { flex-shrink: 0; }
.learnset-table { overflow-x: hidden; background: #fff; }
.lt-head, .lt-row { display:grid; grid-template-columns:34px 1fr 58px 36px 32px; align-items:center; padding:0 10px; border-bottom:1px solid #d0e8f4; }
.lt-head { font-size:8px; font-weight:700; color:#6080a0; padding-top:6px; padding-bottom:6px; letter-spacing:0.3px; background:#e8f4fc; border-bottom:2px solid #b0cce0; }
.lt-row { padding-top:4px; padding-bottom:4px; transition:background 0.1s; }
.lt-row:nth-child(even) { background:#f4fbff; }
.lt-row:hover { background:#e0f0fc; }
.lt-row:last-child { border-bottom:0; }
.lt-lv   { font-family:'VT323',monospace; font-size:14px; color:#2848a8; text-align:center; }
.lt-name { font-family:'VT323',monospace; font-size:14px; color:#181830; padding-left:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.lt-type { display:flex; align-items:center; }
.lt-pwr, .lt-pp { font-family:'VT323',monospace; font-size:13px; color:#6080a0; text-align:center; }
.lt-pwr { color:#c04020; }
.type-badge-sm { font-size:7px; font-weight:700; color:#fff; padding:1px 4px; border-radius:3px; text-shadow:0 1px 0 rgba(0,0,0,0.5); white-space:nowrap; }
.lt-empty { font-family:'VT323',monospace; font-size:15px; color:#a0c0d8; text-align:center; padding:18px; }
.lt-catch-hint { font-family:'VT323',monospace; font-size:14px; color:#8090b0; font-style:italic; text-align:center; padding:14px 10px; }
</style>
