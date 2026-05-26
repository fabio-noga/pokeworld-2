<template>
  <AppHeader @navClick="doLogout" />

  <div class="dex-page">
    <div class="dex-wrap">
      <div class="bento-grid">

        <!-- ① LIST  — col 1-2 · row 1-9 -->
        <div class="bento-cell cell-list">
          <div class="list-head">
            <div class="list-title-row">
              <span class="list-title">POKÉDEX</span>
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

        <!-- ② PREV  — col 3 · row 1 -->
        <button
          class="bento-cell cell-prev"
          :disabled="selectedId <= 1"
          @click="selectPokemon(selectedId - 1)"
          :aria-label="selectedId > 1 ? `Previous: ${pokedex(selectedId - 1)}` : 'No previous'"
        >
          <span class="nav-arrow">◀</span>
          <template v-if="selectedId > 1">
            <img class="nav-sprite"
                 :src="`/textures/Mini/Png/${padId(selectedId - 1)}.png`"
                 :alt="isKnown(selectedId - 1) ? pokedex(selectedId - 1) : '???'"
                 :class="{ 'sprite-unknown': !isKnown(selectedId - 1) }" />
            <div class="nav-info nav-info-l">
              <span class="nav-name">{{ isKnown(selectedId - 1) ? pokedex(selectedId - 1) : '???' }}</span>
              <span class="nav-num">#{{ padId(selectedId - 1) }}</span>
            </div>
          </template>
        </button>

        <!-- ③ TITLE / HERO  — col 4-6 · row 1 -->
        <div class="bento-cell cell-hero" :style="selectedKnown ? heroStyle : {}">
          <img class="hero-gif"
               :src="`/textures/Mini/Gif/${padId(selectedId)}.gif`"
               alt="" width="48" height="48"
               :class="{ 'sprite-unknown': !selectedKnown }" />
          <div class="hero-info">
            <div class="hero-row">
              <h1 class="hero-name">{{ selectedKnown ? selectedName : '???' }}</h1>
              <span class="hero-num">#{{ padId(selectedId) }}</span>
            </div>
            <div class="hero-row">
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
                <span v-if="saveStore.shinydex[String(selectedId)] === 'caught'" class="dex-status shiny-caught">✨ SHINY CAUGHT</span>
                <span v-else-if="saveStore.shinydex[String(selectedId)] === 'seen'" class="dex-status shiny-seen">✨ SHINY SEEN</span>
              </template>
              <span v-else class="dex-status unknown">? UNKNOWN</span>
            </div>
          </div>
        </div>

        <!-- ④ NEXT  — col 7 · row 1 -->
        <button
          class="bento-cell cell-next"
          :disabled="selectedId >= 151"
          @click="selectPokemon(selectedId + 1)"
          :aria-label="selectedId < 151 ? `Next: ${pokedex(selectedId + 1)}` : 'No next'"
        >
          <template v-if="selectedId < 151">
            <div class="nav-info nav-info-r">
              <span class="nav-name">{{ isKnown(selectedId + 1) ? pokedex(selectedId + 1) : '???' }}</span>
              <span class="nav-num">#{{ padId(selectedId + 1) }}</span>
            </div>
            <img class="nav-sprite"
                 :src="`/textures/Mini/Png/${padId(selectedId + 1)}.png`"
                 :alt="isKnown(selectedId + 1) ? pokedex(selectedId + 1) : '???'"
                 :class="{ 'sprite-unknown': !isKnown(selectedId + 1) }" />
          </template>
          <span class="nav-arrow">▶</span>
        </button>

        <!-- ⑤ PHOTO + DESC  — col 3-4 · row 2-4 -->
        <div class="bento-cell cell-art" :style="artStyle">
          <div class="art-tv">
            <div class="art-tv-screen" :class="{ 'is-off': !tvOn }">
              <!-- content stays in DOM so layout never shifts; animation handles visibility -->
              <div class="art-tv-crt-wrap">
                <Transition name="channel" mode="out-in">
                  <div class="art-tv-content" :key="selectedId">
                    <template v-if="selectedKnown">
                      <img class="art-img"
                           :src="`/textures/Art/${padId(selectedId)}.png`"
                           :alt="selectedName"
                           width="300" height="300" />
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
              <div class="art-speaker">
                <span></span><span></span><span></span><span></span><span></span>
              </div>
              <span class="art-model">PDX-151</span>
            </div>
          </div>
        </div>

        <!-- ⑥ BASE STATS  — col 5-7 · row 2-4 -->
        <div class="bento-cell cell-stats">
          <div class="cell-heading">BASE STATS</div>
          <template v-if="selectedCaught">
            <div v-for="s in statRows" :key="s.key" class="stat-row">
              <span class="stat-lbl">{{ s.label }}</span>
              <span class="stat-val">{{ s.value }}</span>
              <div class="stat-bar-bg">
                <div class="stat-bar-fill" :style="{ width: Math.round(s.value / 255 * 100) + '%', backgroundColor: s.color }"></div>
              </div>
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

        <!-- ⑦ EVOLVES  — col 3-7 · row 5 -->
        <div class="bento-cell cell-evo">

          <!-- helper: one chain row (normal or shiny) -->
          <template v-for="shiny in (showShinyEvo ? [false, true] : [false])" :key="String(shiny)">

            <div v-if="shiny" class="evo-divider"><span>✨ Shiny</span></div>
            <div v-else-if="showShinyEvo" class="evo-divider"><span>Normal</span></div>

            <!-- Linear chain -->
            <div v-if="evoChain" class="evo-chain" :class="{ 'evo-chain-shiny': shiny }">
              <template v-for="stage in evoChain" :key="stage.id">
                <button
                  class="evo-stage"
                  :class="{ 'evo-current': selectedId === stage.id, 'evo-unknown': !isKnown(stage.id) }"
                  @click="selectPokemon(stage.id)"
                  :title="isKnown(stage.id) ? pokedex(stage.id) : '???'"
                >
                  <img class="evo-sprite" :src="spriteSrc(stage.id, shiny)"
                       :class="{ 'sprite-unknown': !isKnown(stage.id) }"
                       :alt="isKnown(stage.id) ? pokedex(stage.id) : '???'" width="32" height="32" />
                  <span class="evo-name">{{ isKnown(stage.id) ? pokedex(stage.id) : '???' }}</span>
                </button>
                <div v-if="stage.next" class="evo-connector">
                  <span class="evo-arr">▶</span>
                  <span class="evo-trig">{{ triggerLabel(stage.next) }}</span>
                </div>
              </template>
            </div>

            <!-- Eevee branching -->
            <div v-else-if="isEeveeFamily" class="evo-chain evo-eevee" :class="{ 'evo-chain-shiny': shiny }">
              <button
                class="evo-stage"
                :class="{ 'evo-current': selectedId === EEVEE_BASE_ID, 'evo-unknown': !isKnown(EEVEE_BASE_ID) }"
                @click="selectPokemon(EEVEE_BASE_ID)"
                :title="isKnown(EEVEE_BASE_ID) ? 'Eevee' : '???'"
              >
                <img class="evo-sprite" :src="spriteSrc(EEVEE_BASE_ID, shiny)"
                     :class="{ 'sprite-unknown': !isKnown(EEVEE_BASE_ID) }"
                     alt="Eevee" width="32" height="32" />
                <span class="evo-name">{{ isKnown(EEVEE_BASE_ID) ? pokedex(EEVEE_BASE_ID) : '???' }}</span>
              </button>
              <div class="evo-branches">
                <div v-for="branch in EEVEE_BRANCHES" :key="branch.id" class="evo-branch">
                  <div class="evo-connector">
                    <span class="evo-arr">▶</span>
                    <span class="evo-trig">{{ branch.stone }}</span>
                  </div>
                  <button
                    class="evo-stage"
                    :class="{ 'evo-current': selectedId === branch.id, 'evo-unknown': !isKnown(branch.id) }"
                    @click="selectPokemon(branch.id)"
                    :title="isKnown(branch.id) ? pokedex(branch.id) : '???'"
                  >
                    <img class="evo-sprite" :src="spriteSrc(branch.id, shiny)"
                         :class="{ 'sprite-unknown': !isKnown(branch.id) }"
                         :alt="isKnown(branch.id) ? pokedex(branch.id) : '???'" width="32" height="32" />
                    <span class="evo-name">{{ isKnown(branch.id) ? pokedex(branch.id) : '???' }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- No evolution -->
            <div v-else class="evo-chain">
              <button
                class="evo-stage evo-current"
                :class="{ 'evo-unknown': !selectedKnown }"
                @click="selectPokemon(selectedId)"
              >
                <img class="evo-sprite"
                     :src="spriteSrc(selectedId, shiny)"
                     :class="{ 'sprite-unknown': !selectedKnown }"
                     :alt="selectedKnown ? selectedName : '???'"
                     width="32" height="32" />
                <span class="evo-name">{{ selectedKnown ? selectedName : '???' }}</span>
              </button>
            </div>

          </template>

        </div>

        <!-- ⑧ LEVEL-UP MOVES  — col 3-7 · row 6-9 -->
        <div class="bento-cell cell-learnset">
          <div class="cell-heading-red">
            <span>LEVEL-UP MOVES</span>
            <span class="section-count">{{ selectedCaught ? learnsetRows.length + ' moves' : '???' }}</span>
          </div>
          <div class="learnset-table" role="table" aria-label="Level-up moves">
            <div class="lt-head" role="row">
              <span class="lt-lv"   role="columnheader">LV</span>
              <span class="lt-name" role="columnheader">MOVE</span>
              <span class="lt-type" role="columnheader">TYPE</span>
              <span class="lt-pwr"  role="columnheader">PWR</span>
              <span class="lt-pp"   role="columnheader">PP</span>
              <span class="lt-acc"  role="columnheader">ACC</span>
            </div>
            <template v-if="selectedCaught">
              <div v-for="entry in learnsetRows" :key="entry.id" class="lt-row" role="row">
                <span class="lt-lv"   role="cell">{{ entry.level === 0 ? '—' : entry.level }}</span>
                <span class="lt-name" role="cell">{{ entry.name }}</span>
                <span class="lt-type" role="cell">
                  <span class="type-badge-sm" :style="{ backgroundColor: TYPE_COLORS[entry.type] ?? '#888' }">
                    {{ TYPE_NAMES[entry.type] ?? '???' }}
                  </span>
                </span>
                <span class="lt-pwr"  role="cell">{{ entry.power > 0 ? entry.power : '—' }}</span>
                <span class="lt-pp"   role="cell">{{ entry.pp }}</span>
                <span class="lt-acc"  role="cell">{{ entry.acc }}%</span>
              </div>
              <div v-if="learnsetRows.length === 0" class="lt-empty">No learnset data</div>
            </template>
            <div v-else-if="selectedKnown" class="lt-catch-hint" role="row">
              <span>Catch this Pokémon to know more</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useAuthStore } from '../stores/auth'
import { useSaveStore } from '../stores/save'
import { pokedex, padId } from '../data/pokemon'
import { findChain, isEeveeLine, EEVEE_BASE_ID, EEVEE_BRANCHES, type EvoTrigger } from '../data/evo-chains'
import statsData        from '../data/pokemon-stats.json'
import movesData        from '../data/moves.json'
import learnsetsData    from '../data/learnsets.json'
import descriptionsData from '../data/pokedex-descriptions.json'

// ── Types ────────────────────────────────────────────────────────────
type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number; type1: number; type2: number }
type MoveEntry  = { name: string; type: number; power: number; acc: number; pp: number }

const STATS        = statsData        as Record<string, StatsEntry>
const MOVES        = movesData        as Record<string, MoveEntry>
const LEARNSETS    = learnsetsData    as Record<string, { id: number; level: number }[]>
const DESCRIPTIONS = descriptionsData as Record<string, string>

// ── Stores / router ──────────────────────────────────────────────────
const route     = useRoute()
const router    = useRouter()
const authStore = useAuthStore()
const saveStore = useSaveStore()

// ── Type constants ───────────────────────────────────────────────────
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

// ── True Player Experience ───────────────────────────────────────────
const tpeMode = ref(true)

function isKnown(id: number): boolean {
  if (!tpeMode.value) return true
  return !!saveStore.pokedex[String(id)]
}
const selectedKnown  = computed(() => isKnown(selectedId.value))
const selectedCaught = computed(() => !tpeMode.value || saveStore.pokedex[String(selectedId.value)] === 'caught')

// ── TV power ─────────────────────────────────────────────────────────
const tvOn         = ref(true)
const bubbleVisible = ref(true)

// ── Search ───────────────────────────────────────────────────────────
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

// ── Selected Pokémon ─────────────────────────────────────────────────
const selectedId   = computed(() => { const n = Number(route.query.id); return n >= 1 && n <= 151 ? n : 1 })
const selectedName = computed(() => pokedex(selectedId.value))

function selectPokemon(id: number) {
  router.push({ path: '/pokedex', query: { id } })
}
async function doLogout() {
  await authStore.logout()
  router.push('/')
}

// ── Types & colors ───────────────────────────────────────────────────
const selectedTypes = computed(() => {
  const s = STATS[String(selectedId.value)]
  if (!s) return [1]
  return s.type1 !== s.type2 ? [s.type1, s.type2] : [s.type2]
})
const primaryColor = computed(() => TYPE_COLORS[selectedTypes.value[0]] ?? '#444')

const heroStyle = computed(() => ({
  borderTopColor: primaryColor.value,
  borderTopWidth: '4px',
}))
const artStyle = computed(() => ({
  borderTopColor: primaryColor.value,
  borderTopWidth: '4px',
}))

// ── Description ──────────────────────────────────────────────────────
const pokemonDescription = computed(() => DESCRIPTIONS[String(selectedId.value)] ?? '')

// ── Stats ────────────────────────────────────────────────────────────
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

// ── Evolution ────────────────────────────────────────────────────────
const evoChain      = computed(() => findChain(selectedId.value))
const isEeveeFamily = computed(() => isEeveeLine(selectedId.value))
const showShinyEvo = computed(() => saveStore.shinydex[String(selectedId.value)] === 'caught')

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

// ── Learnset ─────────────────────────────────────────────────────────
interface LearnRow { id: number; level: number; name: string; type: number; power: number; acc: number; pp: number }

const learnsetRows = computed((): LearnRow[] => {
  const raw = LEARNSETS[String(selectedId.value)] ?? []
  return raw
    .map(e => {
      const m = MOVES[String(e.id)]
      if (!m) return null
      return { id: e.id, level: e.level, name: m.name, type: m.type, power: m.power, acc: m.acc, pp: m.pp }
    })
    .filter(Boolean)
    .sort((a, b) => a!.level - b!.level) as LearnRow[]
})

// ── Init ─────────────────────────────────────────────────────────────
onMounted(() => {
  document.querySelector('.list-item.active')?.scrollIntoView({ block: 'center' })
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=VT323:wght@400&display=swap');

*, *::before, *::after { box-sizing: border-box; }

/* ═══════════════════════════════════════════════════════════
   POKÉDEX — bento grid
   7 columns × 9 rows
   Col 1-2 : list (2×9)
   Col 3   : prev (1×1)
   Col 4-6 : hero/title (3×1)
   Col 7   : next (1×1)
   Col 3-4 : art+desc (2×3) — rows 2-4
   Col 5-7 : stats (3×3)   — rows 2-4
   Col 3-7 : evo (5×1)     — row 5
   Col 3-7 : learnset (5×4)— rows 6-9
   ═══════════════════════════════════════════════════════════ */

.dex-page {
  min-height: 100dvh;
  padding-top: 132px;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 22px 22px;
  display: flex;
  justify-content: center;
  font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
}

.dex-wrap {
  width: 100%;
  max-width: 1240px;
  padding: 16px 16px 48px;
}

/* ── Grid ── */
.bento-grid {
  display: grid;
  /* 2 list cols (fixed) + 5 equal content cols */
  grid-template-columns: 95px 95px repeat(5, 1fr);
  grid-template-rows: auto auto auto auto auto auto auto auto auto;
  gap: 10px;
  align-items: stretch;
}

/* ── Shared bento cell ── */
.bento-cell {
  background: #f0f8ff;
  border: 3px solid #2848a8;
  border-radius: 4px;
  box-shadow: 0 5px 0 #1a3080;
  overflow: hidden;
  /* reset for <button> */
  font-family: inherit;
  cursor: default;
}

/* ── Grid placements ── */
.cell-list     { grid-column: 1 / 3;  grid-row: 1 / 10; display: flex; flex-direction: column; align-self: start; position: sticky; top: 148px; max-height: calc(100dvh - 162px); }
.cell-prev     { grid-column: 3 / 4;  grid-row: 1 / 2;  cursor: pointer; }
.cell-hero     { grid-column: 4 / 7;  grid-row: 1 / 2;  transition: border-top-color 0.3s; }
.cell-next     { grid-column: 7 / 8;  grid-row: 1 / 2;  cursor: pointer; }
.cell-art      { grid-column: 3 / 5;  grid-row: 2 / 5;  transition: border-top-color 0.3s; }
.cell-stats    { grid-column: 5 / 8;  grid-row: 2 / 5; }
.cell-evo      { grid-column: 3 / 8;  grid-row: 5 / 6; }
.cell-learnset { grid-column: 3 / 8;  grid-row: 6 / 10; }

/* ═══════════════════════════════════════
   ① LIST
   ═══════════════════════════════════════ */
.list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 10px 8px;
  background: #c82020;
  border-bottom: 3px solid #881010;
  flex-shrink: 0;
}
.list-title {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.5px;
  text-shadow: 1px 1px 0 #660808;
}
.list-counts {
  font-family: 'VT323', monospace;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
}
.cnt-row {
  display: flex;
  gap: 8px;
}
.cnt-caught       { color: #c8ffc8; }
.cnt-seen         { color: #ffe0c0; }
.cnt-shiny-caught { color: #ffe97a; }
.cnt-shiny-seen   { color: #ffd0f0; }

/* ── TPE toggle ──────────────────────────────────────────────────── */
.list-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.tpe-btn {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.3);
  border-radius: 4px;
  color: rgba(255,255,255,0.5);
  width: 24px; height: 24px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  font-size: 11px;
  transition: background 0.15s, color 0.15s;
}
.tpe-btn.active { background: rgba(255,255,255,0.9); color: #c82020; }
.tpe-btn:not(.active):hover { background: rgba(255,255,255,0.3); color: #fff; }

/* ── Unknown / silhouette states ─────────────────────────────────── */
.sprite-unknown {
  filter: brightness(0);
}
.evo-unknown {
  opacity: 0.45;
}
.stat-unknown { color: #aaa; font-style: italic; }
.art-unknown-screen {
  width: 100%;
  flex: 1;
  background: #fff;
  border-radius: 4px;
}

.list-search-bar {
  padding: 6px 8px;
  border-bottom: 1px solid #b8d4e8;
  background: #e8f4fc;
  flex-shrink: 0;
}
.list-input {
  width: 100%;
  background: #fff;
  border: 2px solid #a0c0d8;
  border-radius: 2px;
  color: #181830;
  font-family: 'VT323', monospace;
  font-size: 15px;
  padding: 4px 6px;
  outline: none;
  transition: border-color 0.15s;
}
.list-input::placeholder { color: #a0c0d8; }
.list-input:focus { border-color: #2848a8; }

.list-scroll {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  scrollbar-width: thin;
  scrollbar-color: #a0c0d8 #e8f4fc;
}
.list-scroll::-webkit-scrollbar { width: 4px; }
.list-scroll::-webkit-scrollbar-thumb { background: #a0c0d8; border-radius: 2px; }

.list-item {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 4px 8px;
  cursor: pointer;
  border-bottom: 1px solid #d0e8f4;
  min-height: 32px;
  transition: background 0.1s;
  touch-action: manipulation;
}
.list-item:hover  { background: #e8f4fc; }
.list-item.active { background: #fff4f4; border-left: 3px solid #c82020; padding-left: 5px; }
.list-item img { image-rendering: pixelated; flex-shrink: 0; }
.li-num  { font-family: 'VT323', monospace; font-size: 12px; color: #6080a0; min-width: 28px; flex-shrink: 0; }
.li-name { font-family: 'VT323', monospace; font-size: 14px; color: #181830; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.li-dot  { font-size: 9px; flex-shrink: 0; }
.dot-caught      { color: #1a8030; }
.dot-seen        { color: #c07010; }
.dot-shiny-caught { font-size: 9px; }
.dot-shiny-seen   { font-size: 9px; opacity: 0.5; }

/* ═══════════════════════════════════════
   ② PREV  &  ④ NEXT
   ═══════════════════════════════════════ */
.cell-prev,
.cell-next {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  text-align: center;
  border: none;
  outline: none;
  transition: background 0.15s, box-shadow 0.1s, transform 0.1s;
  touch-action: manipulation;
}
.cell-prev:hover:not(:disabled),
.cell-next:hover:not(:disabled) { background: #d8eef8; }
.cell-prev:active:not(:disabled),
.cell-next:active:not(:disabled) { transform: translateY(3px); box-shadow: 0 2px 0 #1a3080; }
.cell-prev:disabled,
.cell-next:disabled { opacity: 0.25; cursor: not-allowed; box-shadow: none; }

/* prev: arrow | sprite | info-left */
.cell-prev { flex-direction: row; justify-content: flex-start; }
/* next: info-right | sprite | arrow */
.cell-next { flex-direction: row; justify-content: flex-end; }

.nav-arrow {
  font-size: 12px;
  font-weight: 700;
  color: #2848a8;
  flex-shrink: 0;
  line-height: 1;
}
.nav-sprite {
  width: 36px;
  height: 36px;
  image-rendering: pixelated;
  flex-shrink: 0;
}
.nav-info {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}
.nav-info-l { align-items: flex-start; }
.nav-info-r { align-items: flex-end; }
.nav-name {
  font-size: 11px;
  font-weight: 600;
  color: #181830;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 90px;
  line-height: 1;
}
.nav-num {
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #6080a0;
  line-height: 1;
}

/* ═══════════════════════════════════════
   ③ HERO / TITLE
   ═══════════════════════════════════════ */
.cell-hero {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
}
.hero-gif { image-rendering: pixelated; flex-shrink: 0; }
.hero-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
.hero-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.hero-name {
  font-size: 21px;
  font-weight: 700;
  color: #181830;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.hero-num {
  font-family: 'VT323', monospace;
  font-size: 20px;
  color: #6080a0;
  flex-shrink: 0;
}
.hero-types { display: flex; gap: 5px; flex-wrap: wrap; }
.type-badge {
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  padding: 3px 9px;
  border-radius: 3px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.4);
  letter-spacing: 0.3px;
}
.dex-status {
  font-size: 10px;
  font-weight: 600;
  padding: 4px 9px;
  border-radius: 3px;
  border: 2px solid;
  letter-spacing: 0.2px;
}
.dex-status.caught       { color: #1a6020; border-color: #1a6020; background: #e8f8e8; }
.dex-status.seen         { color: #8a4800; border-color: #c07010; background: #fff8e0; }
.dex-status.unknown      { color: #a0b0c0; border-color: #b8d4e8; background: #f0f8ff; }
.dex-status.shiny-caught { color: #7a5500; border-color: #e6b800; background: #fffbe0; }
.dex-status.shiny-seen   { color: #8a3070; border-color: #d080b0; background: #fdf0f8; }

/* ═══════════════════════════════════════
   ⑤ ART — full TV panel
   ═══════════════════════════════════════ */
.cell-art {
  display: flex;
  flex-direction: column;
  background: transparent;
  border: none;
  border-radius: 0;
  box-shadow: none;
  overflow: visible;
}

/* Outer TV bezel — fills the bento cell */
.art-tv {
  flex: 1;
  background: linear-gradient(160deg, #252838 0%, #181a28 100%);
  border-radius: 8px;
  box-shadow: 0 4px 0 #0d0e1a;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Soft CRT flicker — barely noticeable, runs every ~6s */
@keyframes crt-flicker {
  0%, 89%, 91%, 94%, 100% { filter: brightness(1); }
  90%  { filter: brightness(0.96); }
  92%  { filter: brightness(0.98); }
  95%  { filter: brightness(0.97); }
}

/* White screen — floats inside the dark bezel */
.art-tv-screen {
  margin: 9px 9px 0;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1), inset 2px 2px 10px rgba(0,0,0,0.06);
  animation: crt-flicker 6s infinite;
}

/* Vignette + scanlines combined */
.art-tv-screen::after {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at center, transparent 52%, rgba(0,0,0,0.18) 100%),
    repeating-linear-gradient(
      0deg,
      transparent 0px, transparent 2px,
      rgba(0,0,0,0.022) 2px, rgba(0,0,0,0.022) 4px
    );
  pointer-events: none;
  z-index: 10;
}

/* Top glare */
.art-tv-screen::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 28%;
  background: linear-gradient(180deg, rgba(255,255,255,0.13) 0%, transparent 100%);
  pointer-events: none;
  z-index: 11;
}

/* Content wrapper needed for Transition */
.art-tv-content {
  display: flex;
  flex-direction: column;
}

/* Channel-change transition */
.channel-leave-active { animation: ch-out 0.1s ease-in  forwards; }
.channel-enter-active { animation: ch-in  0.2s ease-out forwards; }

@keyframes ch-out {
  0%   { opacity: 1; filter: brightness(1)   blur(0px);   transform: scaleY(1);    }
  60%  { opacity: 1; filter: brightness(3)   blur(1px);   transform: scaleY(0.98); }
  100% { opacity: 0; filter: brightness(8)   blur(2px);   transform: scaleY(0.95); }
}
@keyframes ch-in {
  0%   { opacity: 0; filter: brightness(6)   blur(2px);   transform: scaleY(0.95); }
  45%  { opacity: 1; filter: brightness(1.6) blur(0.5px); transform: scaleY(1.01); }
  100% { opacity: 1; filter: brightness(1)   blur(0px);   transform: scaleY(1);    }
}

.art-img {
  display: block;
  width: 100%;
  height: auto;
  aspect-ratio: 1;
  object-fit: contain;
  image-rendering: pixelated;
  position: relative;
  z-index: 1;
}

/* FireRed-style dialogue box — absolute, anchored to bottom of screen.
   Grows upward when text wraps, never affects the TV's height.       */
.art-dialogue {
  position: absolute;
  bottom: 6px;
  left: 6px;
  right: 6px;
  z-index: 5;
  padding: 7px 10px 9px;
  background: #fff;
  border: 2px solid #181830;
  border-radius: 5px;
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px #181830;
}
/* ▼ hide tab — sits just above the dialogue, top-right corner */
.dlg-toggle {
  position: absolute;
  top: -14px;
  right: 0;
  width: 20px;
  height: 14px;
  background: #fff;
  border: 2px solid #181830;
  border-bottom: none;
  border-radius: 3px 3px 0 0;
  color: #181830;
  font-size: 7px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: background 0.15s, color 0.15s;
}
.dlg-toggle:hover { background: #e8f0fc; }

/* ▲ show tab — bottom-right of screen when bubble is hidden */
.dlg-show-tab {
  position: absolute;
  bottom: 6px;
  right: 6px;
  width: 22px;
  height: 16px;
  background: #181830;
  border: 2px solid #181830;
  border-radius: 3px;
  color: #fff;
  font-size: 7px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  z-index: 5;
  transition: background 0.15s;
}
.dlg-show-tab:hover { background: #2848a8; border-color: #2848a8; }

.art-dialogue-text {
  font-family: 'VT323', monospace;
  font-size: 15px;
  line-height: 1.55;
  color: #181830;
  margin: 0;
}
.art-dlg-cur {
  color: #c82020;
  margin-left: 3px;
}

/* Bezel bottom bar */
.art-bezel-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 11px 8px;
}
/* Dark screen when off */
.art-tv-screen.is-off { background: #060608; animation: none; }

/* Content wrapper — always in DOM so height never changes.
   position: relative so the dialogue can be anchored absolutely.
   transform: scaleY is used for the CRT collapse effect because
   transform does NOT affect layout — the space is preserved.       */
.art-tv-crt-wrap {
  display: flex;
  flex-direction: column;
  position: relative;
  transform-origin: center center;
  transition:
    transform 0.18s ease-in,
    opacity   0.12s ease-in,
    filter    0.18s ease-in;
}
.art-tv-screen.is-off .art-tv-crt-wrap {
  transform: scaleY(0.03);
  filter: brightness(4);
  opacity: 0;
  transition:
    transform 0.18s ease-in,
    opacity   0.1s  0.08s ease-in,   /* opacity lags slightly behind collapse */
    filter    0.06s ease-in;
}

.art-led {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #c82020;
  box-shadow: 0 0 5px #c82020, 0 0 2px #ff4040;
  flex-shrink: 0;
  cursor: pointer;
  transition: background 0.2s, box-shadow 0.2s;
}
.art-led.led-off {
  background: #3a0808;
  box-shadow: none;
}
.art-speaker {
  display: flex;
  gap: 3px;
  align-items: center;
}
.art-speaker span {
  display: block;
  width: 2px;
  height: 9px;
  background: rgba(255,255,255,0.18);
  border-radius: 1px;
}
.art-model {
  font-family: 'VT323', monospace;
  font-size: 11px;
  color: rgba(255,255,255,0.22);
  letter-spacing: 1.5px;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════
   ⑥ BASE STATS
   ═══════════════════════════════════════ */
.cell-stats { padding: 14px 16px; }
.cell-heading {
  font-size: 11px;
  font-weight: 700;
  color: #c82020;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}
.stat-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}
.stat-lbl {
  font-size: 10px;
  font-weight: 600;
  color: #6080a0;
  min-width: 36px;
  flex-shrink: 0;
  letter-spacing: 0.3px;
}
.stat-val {
  font-family: 'VT323', monospace;
  font-size: 17px;
  color: #181830;
  min-width: 32px;
  text-align: right;
  flex-shrink: 0;
}
.stat-bar-bg {
  flex: 1;
  height: 9px;
  background: #d0e4f0;
  border-radius: 2px;
  overflow: hidden;
  border: 1px solid #b0c8e0;
}
.stat-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease-out;
}
.stats-total {
  font-size: 11px;
  font-weight: 600;
  color: #6080a0;
  margin-top: 8px;
  text-align: right;
  letter-spacing: 0.3px;
}
.stats-total-val { color: #181830; margin-left: 6px; }

/* ═══════════════════════════════════════
   ⑦ EVOLVES
   ═══════════════════════════════════════ */
.cell-evo {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 10px 16px;
  overflow-x: auto;
}

/* Flex row of stages + connectors */
.evo-chain {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.evo-chain-shiny {
  background: linear-gradient(90deg, rgba(255,220,80,0.08), rgba(255,180,240,0.08));
  border-radius: 6px;
  padding: 4px 6px;
}

/* Divider label between normal and shiny rows */
.evo-divider {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 2px 0;
}
.evo-divider span {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #2848a8;
  letter-spacing: 0.5px;
  opacity: 0.7;
}
.evo-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #c0d4f0;
}

/* Eevee branches — base left, branches stacked right */
.evo-eevee { flex-wrap: nowrap; align-items: flex-start; }
.evo-branches {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.evo-branch {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Clickable stage button */
.evo-stage {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 4px 8px;
  background: none;
  border: 2px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
  text-decoration: none;
}
.evo-stage:hover {
  background: #e8f0fc;
  border-color: #2848a8;
}
.evo-stage.evo-current {
  background: #dce8ff;
  border-color: #2848a8;
}
.evo-stage.evo-current .evo-name {
  color: #2848a8;
  font-weight: bold;
}

.evo-sprite {
  image-rendering: pixelated;
  width: 32px;
  height: 32px;
}

.evo-name {
  font-family: 'VT323', monospace;
  font-size: 13px;
  color: #181830;
  line-height: 1;
  white-space: nowrap;
}

/* Arrow + trigger label between stages */
.evo-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  flex-shrink: 0;
}
.evo-arr {
  font-size: 10px;
  color: #4060a0;
  line-height: 1;
}
.evo-trig {
  font-family: 'VT323', monospace;
  font-size: 12px;
  color: #4060a0;
  white-space: nowrap;
  line-height: 1;
}

/* No-evo label */
.evo-none {
  font-family: 'VT323', monospace;
  font-size: 18px;
  color: #8090b0;
}

/* ═══════════════════════════════════════
   ⑧ LEVEL-UP MOVES
   ═══════════════════════════════════════ */
.cell-heading-red {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: #c82020;
  border-bottom: 3px solid #881010;
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.5px;
  text-shadow: 1px 1px 0 #660808;
}
.section-count {
  font-family: 'VT323', monospace;
  font-size: 15px;
  color: #ffc8c8;
  font-weight: normal;
}
.learnset-table { overflow-x: auto; }
.lt-head, .lt-row {
  display: grid;
  grid-template-columns: 44px 1fr 80px 52px 44px 52px;
  align-items: center;
  padding: 0 12px;
  border-bottom: 1px solid #d0e8f4;
}
.lt-head {
  font-size: 10px;
  font-weight: 700;
  color: #6080a0;
  padding-top: 8px;
  padding-bottom: 8px;
  letter-spacing: 0.3px;
  background: #e8f4fc;
  border-bottom: 2px solid #b0cce0;
}
.lt-row {
  padding-top: 6px;
  padding-bottom: 6px;
  background: #fff;
  transition: background 0.1s;
}
.lt-row:nth-child(even) { background: #f4fbff; }
.lt-row:hover { background: #e0f0fc; }
.lt-row:last-child { border-bottom: 0; }

.lt-lv   { font-family: 'VT323', monospace; font-size: 16px; color: #2848a8; text-align: center; }
.lt-name { font-family: 'VT323', monospace; font-size: 16px; color: #181830; padding-left: 4px; }
.lt-type { display: flex; align-items: center; }
.lt-pwr, .lt-pp, .lt-acc { font-family: 'VT323', monospace; font-size: 15px; color: #6080a0; text-align: center; }
.lt-pwr  { color: #c04020; }

.type-badge-sm {
  font-size: 9px;
  font-weight: 700;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  text-shadow: 0 1px 0 rgba(0,0,0,0.5);
  white-space: nowrap;
}
.lt-empty {
  font-family: 'VT323', monospace;
  font-size: 18px;
  color: #a0c0d8;
  text-align: center;
  padding: 24px;
  background: #fff;
}
.lt-catch-hint {
  font-family: 'VT323', monospace;
  font-size: 17px;
  color: #8090b0;
  font-style: italic;
  text-align: center;
  padding: 20px 12px;
  background: #fff;
  border-bottom: 1px solid #d0e8f4;
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .bento-grid {
    /* collapse to: list(2col) + content(3col) */
    grid-template-columns: 80px 80px 1fr 1fr 1fr;
    grid-template-rows: auto auto auto auto auto;
  }
  .cell-list     { grid-column: 1 / 3; grid-row: 1 / 6; }
  .cell-prev     { grid-column: 3 / 4; grid-row: 1; }
  .cell-hero     { grid-column: 4 / 6; grid-row: 1; }
  .cell-next     { grid-column: 3 / 4; grid-row: 2; } /* stack next below prev */
  .cell-art      { grid-column: 4 / 6; grid-row: 2 / 4; }
  .cell-stats    { grid-column: 3 / 6; grid-row: 4; }
  .cell-evo      { grid-column: 3 / 6; grid-row: 5; }
  .cell-learnset { grid-column: 1 / 6; grid-row: 6; }
}

@media (max-width: 600px) {
  .bento-grid {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: none;
  }
  .cell-list     { grid-column: 1 / 3; grid-row: 1; max-height: 200px; }
  .cell-prev     { grid-column: 1;     grid-row: 2; }
  .cell-next     { grid-column: 2;     grid-row: 2; }
  .cell-hero     { grid-column: 1 / 3; grid-row: 3; }
  .cell-art      { grid-column: 1 / 3; grid-row: 4; }
  .cell-stats    { grid-column: 1 / 3; grid-row: 5; }
  .cell-evo      { grid-column: 1 / 3; grid-row: 6; }
  .cell-learnset { grid-column: 1 / 3; grid-row: 7; }

  .hero-name { font-size: 17px; }
  .lt-head, .lt-row { grid-template-columns: 36px 1fr 68px 44px 36px 44px; padding: 0 8px; }
}

@media (max-width: 380px) {
  .lt-acc { display: none; }
  .lt-head, .lt-row { grid-template-columns: 36px 1fr 66px 40px 36px; }
}
</style>
