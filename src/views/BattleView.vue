<template>
  <div class="battle-page">
  <main>
    <!-- Arena -->
    <div class="monitor" :style="{ backgroundImage: `url(/textures/Battle/Arena/background${arenaNum}.png)` }">

      <!-- Enemy HUD -->
      <div class="enemy-hud">
        <div class="hud-name">
          <span v-if="isEnemyShiny" class="shiny-badge" title="Shiny!">✨</span>
          {{ enemyName }}
          <img v-if="saveStore.pokedex[String(rivalNum)] === 'caught'"
               src="/textures/HUD/Pokeball.png" class="hud-ball" title="Normal caught" />
          <img v-if="saveStore.shinydex[String(rivalNum)] === 'caught'"
               src="/textures/HUD/Pokeball.png" class="hud-ball hud-ball--shiny" title="Shiny caught" />
          <span class="hud-lv">Lv.{{ enemyLevel }}</span>
        </div>
        <div class="hp-bar-wrap"><div class="hp-bar" :style="enemyHpStyle"></div></div>
        <div class="hud-hp">{{ enemyHP }}/{{ enemyMaxHP }}</div>
      </div>

      <!-- Player HUD -->
      <div class="player-hud">
        <div class="hud-name">
          <span v-if="isPlayerShiny" class="shiny-badge" title="Shiny!">✨</span>
          {{ playerName }} <span class="hud-lv">Lv.{{ playerLvl }}</span>
        </div>
        <div class="hp-bar-wrap"><div class="hp-bar" :style="playerHpStyle"></div></div>
        <div class="hud-hp">{{ playerHP }}/{{ playerMaxHP }}</div>
      </div>

      <!-- Enemy area: trainer intro/outro OR wild/battle Pokémon -->
      <div id="rival" v-show="!catching">
        <img v-if="trainerVisible"
             :src="trainerSrc"
             class="rival-trainer-img"
             :class="trainerAnimClass" />
        <img v-if="pokemonVisible"
             id="RivalImg"
             :src="rivalSprite"
             alt=""
             :class="pokemonAnimClass" />
      </div>

      <!-- Catch ball — separate div so #rival > img width doesn't apply -->
      <div id="catch-pos" v-show="catching">
        <img :key="shakeKey" :src="`/textures/Items/${throwingBall}.png`"
             :class="{ wiggle: wiggling, greyed: captureSuccess }" alt="" />
      </div>

      <!-- Player Pokémon -->
      <div id="pokemon">
        <img id="PokemonImg" :src="playerPokemonSprite" alt="" />
      </div>

      <!-- Battle log -->
      <div class="battle-log" ref="logEl">
        <p v-for="(msg, i) in battleLog" :key="i">{{ msg }}</p>
      </div>
    </div>

    <!-- Bag row — balls + consumables -->
    <div class="bag-row">
      <!-- Pokéball: always available, disabled in trainer battles -->
      <div class="bag-slot"
           :class="{ 'act-disabled': encounter.isTrainer || !canAct || battleOver || captureSuccess || forcedSwitch }"
           @click="onCatch(23)">
        <img src="/textures/Items/23.png" alt="Pokéball" />
        <span class="bag-item-count">∞</span>
      </div>
      <!-- Great Ball: ≥76 seen -->
      <div v-if="hasGreatBall" class="bag-slot"
           :class="{ 'act-disabled': encounter.isTrainer || !canAct || battleOver || captureSuccess || forcedSwitch }"
           @click="onCatch(24)">
        <img src="/textures/Items/24.png" alt="Great Ball" />
        <span class="bag-item-count">∞</span>
      </div>
      <!-- Ultra Ball: all 151 seen -->
      <div v-if="hasUltraBall" class="bag-slot"
           :class="{ 'act-disabled': encounter.isTrainer || !canAct || battleOver || captureSuccess || forcedSwitch }"
           @click="onCatch(25)">
        <img src="/textures/Items/25.png" alt="Ultra Ball" />
        <span class="bag-item-count">∞</span>
      </div>
      <!-- Master Ball: all 151 caught — also works on MissingNo -->
      <div v-if="hasMasterBall" class="bag-slot"
           :class="{ 'act-disabled': encounter.isTrainer || !canAct || battleOver || captureSuccess || forcedSwitch }"
           @click="onCatch(26)">
        <img src="/textures/Items/26.png" alt="Master Ball" />
        <span class="bag-item-count">∞</span>
      </div>
      <!-- Potion: 3 per battle -->
      <div class="bag-slot"
           :class="{ 'item-active': itemMode === 'potion', 'act-disabled': battleOver || potions <= 0 }"
           @click="toggleItemMode('potion')">
        <img src="/textures/Items/8.png" alt="Potion" />
        <span class="bag-item-count">{{ potions }}</span>
      </div>
      <!-- Revive: 1 per battle -->
      <div class="bag-slot"
           :class="{ 'item-active': itemMode === 'revive', 'act-disabled': battleOver || revives <= 0 }"
           @click="toggleItemMode('revive')">
        <img src="/textures/Items/14.png" alt="Revive" />
        <span class="bag-item-count">{{ revives }}</span>
      </div>
    </div>

    <!-- Move buttons -->
    <div class="tackle">
      <div class="Power1" @click="onMove(0)" @mouseover="showPP(0)" @mouseout="hidePP(0)"
           :class="{ 'act-disabled': !canAct || battleOver || forcedSwitch }">
        <h2>{{ moveDisplay[0] }}</h2>
      </div>
      <div class="Power2" @click="onMove(1)" @mouseover="showPP(1)" @mouseout="hidePP(1)"
           :class="{ 'act-disabled': !canAct || battleOver || forcedSwitch }">
        <h2>{{ moveDisplay[1] }}</h2>
      </div>
      <div class="Power3" @click="onMove(2)" @mouseover="showPP(2)" @mouseout="hidePP(2)"
           :class="{ 'act-disabled': !canAct || battleOver || forcedSwitch }">
        <h2>{{ moveDisplay[2] }}</h2>
      </div>
      <div class="Power4" @click="onMove(3)" @mouseover="showPP(3)" @mouseout="hidePP(3)"
           :class="{ 'act-disabled': !canAct || battleOver || forcedSwitch }">
        <h2>{{ moveDisplay[3] }}</h2>
      </div>
    </div>

    <!-- Team slots — click to switch or use item -->
    <div class="pokemons">
      <div v-for="(slot, i) in saveStore.team" :key="i" :id="`Slot${i+1}`"
           :class="{
             'act-disabled': !itemMode && (battleOver || i === 0 || !slot.id || slot.hp <= 0 || (!canAct && !forcedSwitch)),
             'slot-choose': !itemMode && forcedSwitch && i > 0 && slot.id > 0 && slot.hp > 0,
             'slot-dead': slot.id > 0 && slot.hp <= 0,
             'item-target': itemMode && isValidTarget(slot),
             'item-invalid': itemMode && slot.id > 0 && !isValidTarget(slot),
           }"
           @click="onSwitchPokemon(i)">
        <template v-if="slot.id">
          <div class="party-card">
            <img src="/textures/HUD/Pokeball.png" class="party-ball" alt="" />
            <div class="party-entry">
              <img :src="`/textures/Mini/Png/${padId(slot.id)}.png`" class="party-sprite" alt="" />
              <div class="party-info">
                <div class="party-name">{{ slot.nickname?.trim() || pokedex(slot.id) }}</div>
                <div class="party-lv">Lv.<b>{{ slot.lvl }}</b></div>
                <div class="party-hp-bar">
                  <div class="party-hp-fill" :style="{ width: slotHpPct(slot)*100+'%', backgroundColor: hpColor(slotHpPct(slot)) }"></div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </main>
  </div>

  <AppHeader
    :label="allFainted ? 'Health Center' : 'BACK TO MAP'"
    @nav-click="allFainted ? goToHealthCenter() : (forcedSwitch ? null : goBack())"
    :style="forcedSwitch && !allFainted ? { opacity: '0.4', pointerEvents: 'none' } : {}" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useSaveStore } from '../stores/save'
import { pokedex, padId } from '../data/pokemon'
import movesData from '../data/moves.json'
import statsData from '../data/pokemon-stats.json'
import learnsetsData from '../data/learnsets.json'

type MoveEntry  = { name: string; type: number; power: number; acc: number; pp: number }
type StatsEntry = { hp: number; atk: number; def: number; spa: number; spd: number; spe: number; type1: number; type2: number }
const MOVES    = movesData    as Record<string, MoveEntry>
const LEARNSETS = learnsetsData as Record<string, { id: number; level: number }[]>
const STATS    = statsData    as Record<string, StatsEntry>

// ── Gen 1 type chart ──────────────────────────────────────────────
// Only non-neutral matchups; everything else defaults to ×1
// Includes Gen 1-specific quirks:
//   Ghost→Psychic = 0 (should be ×2 but game had a 0 byte bug)
//   Bug→Psychic   = ×2 (was supposed to be removed but wasn't)
//   Poison→Bug    = ×2, Bug→Poison = ×2 (both changed in Gen 2)
const TYPE_CHART: Record<number, Record<number, number>> = {
  1:  { 9: 0.5,            13: 0 },                                             // Normal
  2:  { 15: 2, 10: 2, 11: 2,  2: 0.5, 4: 0.5,  9: 0.5, 12: 0.5 },            // Fire
  4:  { 2: 2,  9: 2,  7: 2,   4: 0.5, 15: 0.5, 12: 0.5 },                     // Water
  6:  { 4: 2, 16: 2,          6: 0.5, 15: 0.5, 12: 0.5, 7: 0 },               // Electric
  15: { 4: 2,  9: 2,  7: 2,   2: 0.5,  5: 0.5, 15: 0.5, 16: 0.5, 11: 0.5, 12: 0.5 }, // Grass
  10: { 15: 2, 7: 2, 16: 2,  12: 2,   4: 0.5 },                                // Ice (Ice vs Ice = ×1 in Gen 1)
  3:  { 1: 2, 10: 2,  9: 2,   5: 0.5, 11: 0.5,  8: 0.5, 16: 0.5, 13: 0 },    // Fighting
  5:  { 11: 2, 15: 2,         5: 0.5,  7: 0.5,  9: 0.5, 13: 0.5 },            // Poison  (SE vs Bug in Gen 1)
  7:  { 2: 2,  6: 2,  5: 2,   9: 2,   15: 0.5, 11: 0.5, 16: 0 },              // Ground
  16: { 3: 2, 11: 2, 15: 2,   6: 0.5,  9: 0.5 },                              // Flying
  8:  { 3: 2,  5: 2,          8: 0.5, 13: 0 },                                 // Psychic  (Ghost bug → 0)
  11: { 15: 2, 5: 2,  8: 2,   2: 0.5,  3: 0.5, 16: 0.5, 13: 0.5 },           // Bug      (SE vs Psychic/Poison in Gen 1)
  9:  { 2: 2, 10: 2, 16: 2,  11: 2,   3: 0.5,  7: 0.5 },                      // Rock
  13: { 1: 0,  8: 0 },                                                           // Ghost    (bugged: 0 vs Normal & Psychic)
  12: { 12: 2 },                                                                  // Dragon
}

// Gen 1 physical/special split is TYPE-based, not move-based
const PHYSICAL_TYPES = new Set([1, 3, 5, 7, 9, 11, 13, 16]) // Normal,Fight,Poison,Ground,Rock,Bug,Ghost,Flying

function typeEffectiveness(moveType: number, defType1: number, defType2: number): number {
  const row = TYPE_CHART[moveType] ?? {}
  const e1 = row[defType1] ?? 1
  const e2 = defType1 !== defType2 ? (row[defType2] ?? 1) : 1
  return e1 * e2
}

function effectivenessLabel(eff: number): string | null {
  if (eff === 0)        return "It has no effect..."
  if (eff >= 2)         return "It's super effective!"
  if (eff <= 0.5)       return "It's not very effective..."
  return null
}

const router = useRouter()
const saveStore = useSaveStore()
const logEl = ref<HTMLElement | null>(null)
const arenaNum = ref(Math.floor(Math.random() * 4) + 1)
const encounter = saveStore.encounter

// ── Active Pokémon (reactive — updates when team[0] changes on switch) ─
const active = computed(() => saveStore.team[0])

// ── Battle state ──────────────────────────────────────────────────
const battleOver = ref(false)
const allFainted = ref(false)
const canAct = ref(false)
const forcedSwitch = ref(false)
const battleLog = ref<string[]>([])
const catching = ref(false)
const wiggling = ref(false)
const captureSuccess = ref(false)
const shakeKey = ref(0)
const throwingBall = ref(23)
const trainerTeamIndex = ref(0)
const trainerVisible   = ref(false)
const trainerIsOutro   = ref(false)
const pokemonVisible   = ref(false)
const trainerAnimClass = ref('')
const pokemonAnimClass = ref('')

// ── Bag / items ────────────────────────────────────────────────────
const potions  = ref(3)
const revives  = ref(1)
const itemMode = ref<'potion' | 'revive' | null>(null)

// ── Ball unlock thresholds ─────────────────────────────────────────
const seenCount = computed(() =>
  Object.keys(saveStore.pokedex)
    .filter(k => { const n = Number(k); return n >= 1 && n <= 151 })
    .filter(k => saveStore.pokedex[k] === 'seen' || saveStore.pokedex[k] === 'caught')
    .length
)
const caughtCount = computed(() =>
  Object.keys(saveStore.pokedex)
    .filter(k => { const n = Number(k); return n >= 1 && n <= 151 })
    .filter(k => saveStore.pokedex[k] === 'caught')
    .length
)
const hasGreatBall  = computed(() => seenCount.value  >= 76)
const hasUltraBall  = computed(() => seenCount.value  >= 151)
const hasMasterBall = computed(() => caughtCount.value >= 151)
const trainerSrc = computed(() =>
  trainerIsOutro.value ? encounter.trainerStill : encounter.trainerPortrait
)

// ── MissingNo. Easter egg ─────────────────────────────────────────
// Triggered when navigating directly to /battle with no encounter set.
// 1 in 5 chance. Uses m1.gif / m1.png sprites, level 80-99.
const isMissingNo    = ref(false)
const missingNoLevel = ref(0)
const MISSINGNO_STATS = { hp: 33, atk: 136, def: 0, spa: 136, spd: 0, spe: 0, type1: 0, type2: 0 }

// ── Sprites ───────────────────────────────────────────────────────
const rivalNum = computed(() => encounter.number > 0 ? encounter.number : 0)
const isEnemyShiny = computed(() => encounter.shiny)
const isPlayerShiny = computed(() => active.value?.shiny ?? false)
const rivalSprite = computed(() => {
  if (isMissingNo.value) return '/textures/Battle/Normal/Front/Gif/m1.gif'
  const variant = isEnemyShiny.value ? 'Shiny' : 'Normal'
  return `/textures/Battle/${variant}/Front/Gif/${padId(rivalNum.value)}.gif`
})
const playerPokemonSprite = computed(() => {
  const variant = isPlayerShiny.value ? 'Shiny' : 'Normal'
  return `/textures/Battle/${variant}/Back/Gif/${padId(active.value?.id ?? 1)}.gif`
})

// ── Names / levels ────────────────────────────────────────────────
const enemyName = computed(() => isMissingNo.value ? 'MissingNo.' : pokedex(rivalNum.value))
const enemyLevel = computed(() => isMissingNo.value ? missingNoLevel.value : encounter.level)
const playerName = computed(() => active.value?.nickname?.trim() || pokedex(active.value?.id ?? 1))
const playerLvl  = computed(() => active.value?.lvl ?? 1)

// ── HP ────────────────────────────────────────────────────────────
function calcMaxHP(baseHP: number, level: number): number {
  return Math.floor(2 * baseHP * level / 100) + level + 10
}
const enemyMaxHP = computed(() => {
  if (isMissingNo.value) return calcMaxHP(MISSINGNO_STATS.hp, missingNoLevel.value)
  return calcMaxHP(STATS[String(rivalNum.value)]?.hp ?? 45, encounter.level)
})
const playerMaxHP = computed(() => calcMaxHP(STATS[String(active.value?.id ?? 1)]?.hp ?? 45, active.value?.lvl ?? 7))
const enemyHP = ref(0)
const playerHP = ref(0)

const enemyHpStyle = computed(() => {
  const pct = Math.max(0, enemyHP.value / enemyMaxHP.value)
  return { width: `${pct * 100}%`, backgroundColor: pct > 0.5 ? '#00cc00' : pct > 0.25 ? '#ffcc00' : '#cc0000' }
})
const playerHpStyle = computed(() => {
  const pct = Math.max(0, playerHP.value / playerMaxHP.value)
  return { width: `${pct * 100}%`, backgroundColor: pct > 0.5 ? '#00cc00' : pct > 0.25 ? '#ffcc00' : '#cc0000' }
})

// ── Player moves ──────────────────────────────────────────────────
const moveNames = ref(['---', '---', '---', '---'])
const movePP = ref([0, 0, 0, 0])
const moveMaxPP = ref([0, 0, 0, 0])
const showingPP = ref([false, false, false, false])
const playerMoveEntries = ref<Array<MoveEntry | null>>([null, null, null, null])

const moveDisplay = computed(() =>
  moveNames.value.map((name, i) =>
    showingPP.value[i] ? `PP: ${movePP.value[i]}/${moveMaxPP.value[i]}` : name
  )
)
function showPP(i: number) { showingPP.value[i] = true }
function hidePP(i: number) { showingPP.value[i] = false }

function loadPlayerMoves() {
  moveNames.value = ['---', '---', '---', '---']
  movePP.value = [0, 0, 0, 0]
  moveMaxPP.value = [0, 0, 0, 0]
  playerMoveEntries.value = [null, null, null, null]
  active.value?.moves?.forEach((m, i) => {
    const entry = MOVES[String(m.id)]
    moveNames.value[i] = entry?.name ?? '---'
    movePP.value[i] = m.pp
    moveMaxPP.value[i] = entry?.pp ?? 0
    playerMoveEntries.value[i] = entry ?? null
  })
}

// ── Enemy moves ───────────────────────────────────────────────────
type EnemyMove = { id: number; entry: MoveEntry; pp: number }
const enemyMoves = ref<EnemyMove[]>([])

function pickEnemyMoves() {
  const learnset = LEARNSETS[String(rivalNum.value)] ?? []
  // All moves learnable at or below the wild Pokémon's level
  const learnable = learnset
    .filter(e => e.level <= enemyLevel.value && MOVES[String(e.id)]?.power > 0)
    .sort((a, b) => b.level - a.level) // highest-level (strongest) first
  // Pick up to 4 best moves; fall back to random damaging moves if learnset is sparse
  const chosen = learnable.slice(0, 4)
  if (chosen.length < 4) {
    const fallback = Object.entries(MOVES)
      .filter(([id, m]) => m.power > 0 && m.power <= 80 && !chosen.find(c => c.id === Number(id)))
      .sort(() => Math.random() - 0.5)
      .slice(0, 4 - chosen.length)
    fallback.forEach(([id]) => chosen.push({ id: Number(id), level: 0 }))
  }
  enemyMoves.value = chosen.map(e => {
    const entry = MOVES[String(e.id)]!
    return { id: e.id, entry, pp: entry.pp }
  })
}

// ── Team slot display ─────────────────────────────────────────────
function slotHpPct(slot: { id: number; hp: number; lvl: number }): number {
  if (!slot.id) return 0
  const maxHP = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  return Math.max(0, Math.min(1, slot.hp / maxHP))
}
function hpColor(pct: number): string {
  if (pct > 0.5) return '#00cc44'
  if (pct > 0.25) return '#ffcc00'
  return '#cc2200'
}

// ── Item use ──────────────────────────────────────────────────────
function toggleItemMode(mode: 'potion' | 'revive') {
  itemMode.value = itemMode.value === mode ? null : mode
}
function isValidTarget(slot: { id: number; hp: number; lvl: number }): boolean {
  if (!slot.id) return false
  if (itemMode.value === 'potion') {
    const maxHP = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
    return slot.hp > 0 && slot.hp < maxHP
  }
  if (itemMode.value === 'revive') return slot.hp <= 0
  return false
}
function usePotion(i: number) {
  const slot = saveStore.team[i]
  if (!slot?.id) return
  const maxHP = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  if (slot.hp >= maxHP) { log(`${slot.nickname?.trim() || pokedex(slot.id)}'s HP is already full!`); itemMode.value = null; return }
  const heal = Math.min(20, maxHP - slot.hp)
  slot.hp += heal
  if (i === 0) playerHP.value = slot.hp
  potions.value--
  log(`Used Potion on ${slot.nickname?.trim() || pokedex(slot.id)}! +${heal} HP.`)
  itemMode.value = null
  canAct.value = false
  saveStore.save()
  setTimeout(() => enemyTurn(), 500)
}
function useRevive(i: number) {
  const slot = saveStore.team[i]
  if (!slot?.id || slot.hp > 0) { log('That Pokémon isn\'t fainted!'); itemMode.value = null; return }
  const maxHP = calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, slot.lvl)
  slot.hp = Math.floor(maxHP / 2)
  revives.value--
  log(`${slot.nickname?.trim() || pokedex(slot.id)} was revived!`)
  itemMode.value = null
  saveStore.save()
  if (forcedSwitch.value) {
    // Enemy already attacked this turn (caused the faint) — just keep forced switch active
    // so player can pick which Pokémon to send out next
    return
  }
  // Normal turn: consuming the player's action, enemy responds
  canAct.value = false
  setTimeout(() => enemyTurn(), 500)
}

// ── Utilities ─────────────────────────────────────────────────────
function delay(ms: number) { return new Promise(r => setTimeout(r, ms)) }
function log(msg: string) {
  battleLog.value.push(msg)
  nextTick(() => { if (logEl.value) logEl.value.scrollTop = logEl.value.scrollHeight })
}

// ── Damage formula (Gen 1) ────────────────────────────────────────
// Returns { dmg, effectiveness } so the caller can log type messages.
function calcDamage(
  atkStat: number, defStat: number, power: number, level: number,
  moveType: number,
  attackerType1: number, attackerType2: number,
  defenderType1: number, defenderType2: number,
): { dmg: number; effectiveness: number } {
  if (power === 0) return { dmg: 0, effectiveness: 1 }

  const eff  = typeEffectiveness(moveType, defenderType1, defenderType2)
  if (eff === 0) return { dmg: 0, effectiveness: 0 }

  const stab = (moveType === attackerType1 || moveType === attackerType2) ? 1.5 : 1
  const base = Math.floor(((2 * level / 5 + 2) * power * atkStat / defStat) / 50) + 2
  const rand = Math.random() * 0.15 + 0.85
  const dmg  = Math.max(1, Math.floor(Math.floor(base * stab) * eff * rand))
  return { dmg, effectiveness: eff }
}

// ── Enemy turn ────────────────────────────────────────────────────
function enemyTurn() {
  if (battleOver.value) return
  const available = enemyMoves.value.filter(m => m.pp > 0)
  let dmg = 0

  if (available.length === 0) {
    dmg = 5
    log(`Wild ${enemyName.value} used Struggle! Dealt ${dmg} damage.`)
  } else {
    const move = available[Math.floor(Math.random() * available.length)]
    move.pp--
    if (move.entry.power > 0) {
      if (Math.random() * 100 < move.entry.acc) {
        const eSt = isMissingNo.value ? MISSINGNO_STATS : STATS[String(rivalNum.value)]
        const pSt = STATS[String(active.value?.id ?? 1)]
        const isPhys = PHYSICAL_TYPES.has(move.entry.type)
        const atkStat = isPhys ? (eSt?.atk ?? 50) : (eSt?.spa ?? 50)
        const defStat = isPhys ? (pSt?.def ?? 50) : (pSt?.spd ?? 50)
        const result = calcDamage(
          atkStat, defStat, move.entry.power, enemyLevel.value,
          move.entry.type,
          eSt?.type1 ?? 1, eSt?.type2 ?? 1,
          pSt?.type1 ?? 1, pSt?.type2 ?? 1,
        )
        dmg = result.dmg
        log(`Wild ${enemyName.value} used ${move.entry.name}!${dmg > 0 ? ` Dealt ${dmg} damage.` : ''}`)
        const effMsg = effectivenessLabel(result.effectiveness)
        if (effMsg) log(effMsg)
      } else {
        log(`Wild ${enemyName.value} used ${move.entry.name}! It missed!`)
      }
    } else {
      log(`Wild ${enemyName.value} used ${move.entry.name}!`)
    }
  }

  playerHP.value = Math.max(0, playerHP.value - dmg)
  if (active.value) active.value.hp = playerHP.value
  saveStore.save()

  if (playerHP.value <= 0) {
    if (active.value) active.value.hp = 0
    log(`${playerName.value} fainted!`)
    const hasAlive = saveStore.team.some((s, i) => i > 0 && s.id > 0 && s.hp > 0)
    if (hasAlive) {
      forcedSwitch.value = true
      log('Choose your next Pokémon!')
    } else {
      log('You have no more Pokémon! You blacked out!')
      battleOver.value = true
      allFainted.value = true
    }
    return
  }
  setTimeout(() => { canAct.value = true }, 500)
}

// ── Level-based evolutions (Gen 1) ───────────────────────────────
const LEVEL_EVOLUTIONS: Record<number, { to: number; level: number }> = {
  1: { to: 2,   level: 16 }, // Bulbasaur → Ivysaur
  2: { to: 3,   level: 32 }, // Ivysaur → Venusaur
  4: { to: 5,   level: 16 }, // Charmander → Charmeleon
  5: { to: 6,   level: 36 }, // Charmeleon → Charizard
  7: { to: 8,   level: 16 }, // Squirtle → Wartortle
  8: { to: 9,   level: 36 }, // Wartortle → Blastoise
  10: { to: 11, level: 7  }, // Caterpie → Metapod
  11: { to: 12, level: 10 }, // Metapod → Butterfree
  13: { to: 14, level: 7  }, // Weedle → Kakuna
  14: { to: 15, level: 10 }, // Kakuna → Beedrill
  16: { to: 17, level: 18 }, // Pidgey → Pidgeotto
  17: { to: 18, level: 36 }, // Pidgeotto → Pidgeot
  19: { to: 20, level: 20 }, // Rattata → Raticate
  21: { to: 22, level: 20 }, // Spearow → Fearow
  23: { to: 24, level: 22 }, // Ekans → Arbok
  27: { to: 28, level: 22 }, // Sandshrew → Sandslash
  29: { to: 30, level: 16 }, // Nidoran♀ → Nidorina
  32: { to: 33, level: 16 }, // Nidoran♂ → Nidorino
  41: { to: 42, level: 22 }, // Zubat → Golbat
  43: { to: 44, level: 21 }, // Oddish → Gloom
  46: { to: 47, level: 24 }, // Paras → Parasect
  48: { to: 49, level: 31 }, // Venonat → Venomoth
  50: { to: 51, level: 26 }, // Diglett → Dugtrio
  52: { to: 53, level: 28 }, // Meowth → Persian
  54: { to: 55, level: 33 }, // Psyduck → Golduck
  56: { to: 57, level: 28 }, // Mankey → Primeape
  60: { to: 61, level: 25 }, // Poliwag → Poliwhirl
  63: { to: 64, level: 16 }, // Abra → Kadabra
  66: { to: 67, level: 28 }, // Machop → Machoke
  69: { to: 70, level: 21 }, // Bellsprout → Weepinbell
  72: { to: 73, level: 30 }, // Tentacool → Tentacruel
  74: { to: 75, level: 25 }, // Geodude → Graveler
  77: { to: 78, level: 40 }, // Ponyta → Rapidash
  79: { to: 80, level: 37 }, // Slowpoke → Slowbro
  81: { to: 82, level: 30 }, // Magnemite → Magneton
  84: { to: 85, level: 31 }, // Doduo → Dodrio
  86: { to: 87, level: 34 }, // Seel → Dewgong
  88: { to: 89, level: 38 }, // Grimer → Muk
  92: { to: 93, level: 25 }, // Gastly → Haunter
  96: { to: 97, level: 26 }, // Drowzee → Hypno
  98: { to: 99, level: 28 }, // Krabby → Kingler
  100: { to: 101, level: 30 }, // Voltorb → Electrode
  104: { to: 105, level: 28 }, // Cubone → Marowak
  109: { to: 110, level: 35 }, // Koffing → Weezing
  111: { to: 112, level: 42 }, // Rhyhorn → Rhydon
  116: { to: 117, level: 32 }, // Horsea → Seadra
  118: { to: 119, level: 33 }, // Goldeen → Seaking
  129: { to: 130, level: 20 }, // Magikarp → Gyarados
  138: { to: 139, level: 40 }, // Omanyte → Omastar
  140: { to: 141, level: 40 }, // Kabuto → Kabutops
  147: { to: 148, level: 30 }, // Dratini → Dragonair
  148: { to: 149, level: 55 }, // Dragonair → Dragonite
}

// Badge-based level caps — gym 0 = uncapped (100) for beta
const BADGE_CAPS = [100, 30, 40, 50, 60, 70, 80, 90, 100]

function xpToNextLevel(lvl: number): number {
  return (lvl + 1) ** 3 - lvl ** 3  // medium-fast growth delta
}

function checkLevelUp(slot: typeof saveStore.team[0]) {
  const cap = BADGE_CAPS[Math.min(saveStore.playerData.gym, 8)] ?? 100
  while (slot.lvl < cap && slot.xp >= xpToNextLevel(slot.lvl)) {
    slot.xp -= xpToNextLevel(slot.lvl)
    slot.lvl++
    const baseHP = STATS[String(slot.id)]?.hp ?? 45
    const newMaxHP = calcMaxHP(baseHP, slot.lvl)
    const hpGain = newMaxHP - calcMaxHP(baseHP, slot.lvl - 1)
    slot.hp = Math.min(slot.hp + hpGain, newMaxHP)
    log(`${pokedex(slot.id)} grew to Lv.${slot.lvl}!`)
    if (slot === saveStore.team[0]) playerHP.value = slot.hp

    // Queue evolution — player triggers it manually in the PC
    const evo = LEVEL_EVOLUTIONS[slot.id]
    if (evo && slot.lvl >= evo.level && !slot.pendingEvo) {
      slot.pendingEvo = evo.to
      log(`${pokedex(slot.id)} is ready to evolve!`)
    }
  }
  // Clamp leftover XP at cap
  if (slot.lvl >= cap) slot.xp = 0
}

// ── XP distribution ───────────────────────────────────────────────
function awardXp(base: number) {
  const total = Math.floor(base * saveStore.xpMultiplier)
  const alive = saveStore.team.filter(s => s.id > 0 && s.hp > 0)
  if (alive.length === 0) return
  if (saveStore.xpShare) {
    const share = Math.max(1, Math.floor(total / alive.length))
    alive.forEach(s => { s.xp += share; checkLevelUp(s) })
    log(`XP shared! Each Pokémon gained ${share} XP!`)
  } else {
    if (active.value) { active.value.xp += total; checkLevelUp(active.value) }
    log(`${playerName.value} gained ${total} XP!`)
  }
  saveStore.save()
}

// ── Player uses a move ────────────────────────────────────────────
async function onMove(i: number) {
  if (!canAct.value || battleOver.value) return
  const move = playerMoveEntries.value[i]
  if (!move) return
  if (movePP.value[i] <= 0) { log('No PP left for that move!'); return }

  canAct.value = false
  movePP.value[i]--
  if (active.value?.moves[i]) active.value.moves[i].pp = movePP.value[i]

  if (move.power > 0) {
    if (Math.random() * 100 < move.acc) {
      const pSt = STATS[String(active.value?.id ?? 1)]
      const eSt = isMissingNo.value ? MISSINGNO_STATS : STATS[String(rivalNum.value)]
      const isPhys = PHYSICAL_TYPES.has(move.type)
      const atkStat = isPhys ? (pSt?.atk ?? 50) : (pSt?.spa ?? 50)
      const defStat = isPhys ? (eSt?.def ?? 50) : (eSt?.spd ?? 50)
      const result = calcDamage(
        atkStat, defStat, move.power, playerLvl.value,
        move.type,
        pSt?.type1 ?? 1, pSt?.type2 ?? 1,
        eSt?.type1 ?? 1, eSt?.type2 ?? 1,
      )
      enemyHP.value = Math.max(0, enemyHP.value - result.dmg)
      log(`${playerName.value} used ${move.name}!${result.dmg > 0 ? ` Dealt ${result.dmg} damage.` : ''}`)
      const effMsg = effectivenessLabel(result.effectiveness)
      if (effMsg) log(effMsg)
    } else {
      log(`${playerName.value} used ${move.name}! It missed!`)
    }
  } else {
    log(`${playerName.value} used ${move.name}!`)
  }

  if (enemyHP.value <= 0) {
    if (active.value) active.value.hp = playerHP.value
    const xpBase = Math.floor(enemyLevel.value * 5 + Math.random() * 20)
    awardXp(xpBase)

    // HP bar drains (300ms transition), then faint animation
    await delay(350)
    pokemonAnimClass.value = 'anim-pokemon-faint'
    await delay(580)
    pokemonVisible.value = false
    pokemonAnimClass.value = ''

    if (encounter.isTrainer) {
      const prefix = encounter.trainerName
      log(`${prefix}'s ${enemyName.value} fainted!`)
      const nextIdx = trainerTeamIndex.value + 1
      if (nextIdx < encounter.trainerTeam.length) {
        trainerTeamIndex.value = nextIdx
        const next = encounter.trainerTeam[nextIdx]
        encounter.number = next.id
        encounter.level  = next.lvl
        enemyHP.value = enemyMaxHP.value
        pickEnemyMoves()
        log(`${prefix} sent out ${enemyName.value}!`)
        await delay(200)
        pokemonVisible.value = true
        pokemonAnimClass.value = 'anim-pokemon-enter'
        await delay(500)
        pokemonAnimClass.value = ''
        await delay(200)
        enemyTurn()
      } else {
        log(`${prefix} is out of Pokémon! You won!`)
        await delay(300)
        trainerIsOutro.value = true
        trainerAnimClass.value = 'anim-trainer-enter-right'
        trainerVisible.value = true
        saveStore.trainerWins[encounter.trainerId] = (saveStore.trainerWins[encounter.trainerId] ?? 0) + 1
        saveStore.save()
        battleOver.value = true
      }
    } else {
      log(`Wild ${enemyName.value} fainted!`)
      battleOver.value = true
    }
    return
  }

  await delay(800)
  enemyTurn()
}

// ── Switch Pokémon ────────────────────────────────────────────────
async function onSwitchPokemon(i: number) {
  // Item-use mode: apply item to clicked slot
  if (itemMode.value === 'potion') { usePotion(i); return }
  if (itemMode.value === 'revive') { useRevive(i); return }

  if (battleOver.value) return
  if (!canAct.value && !forcedSwitch.value) return
  if (i === 0 || !saveStore.team[i]?.id || saveStore.team[i].hp <= 0) return

  const wasForcedSwitch = forcedSwitch.value
  forcedSwitch.value = false
  canAct.value = false

  if (active.value) active.value.hp = playerHP.value

  const temp = { ...saveStore.team[0] }
  saveStore.team[0] = { ...saveStore.team[i] }
  saveStore.team[i] = temp

  playerHP.value = saveStore.team[0].hp
  loadPlayerMoves()
  log(`Go, ${playerName.value}!`)

  if (wasForcedSwitch) {
    setTimeout(() => { canAct.value = true }, 500)
  } else {
    await delay(700)
    enemyTurn()
  }
}

// ── Throw ball ────────────────────────────────────────────────────
const BALL_NAMES: Record<number, string> = { 23: 'Pokéball', 24: 'Great Ball', 25: 'Ultra Ball', 26: 'Master Ball' }

async function onCatch(ballNum = 23) {
  if (encounter.isTrainer) return
  if (!canAct.value || battleOver.value || captureSuccess.value || forcedSwitch.value) return
  // MissingNo only yields to Master Ball
  if (isMissingNo.value && ballNum !== 26) {
    log("It won't work... A stronger ball is needed!")
    return
  }

  canAct.value = false
  catching.value = true
  throwingBall.value = ballNum
  log(`You threw a ${BALL_NAMES[ballNum] ?? 'Pokéball'}!`)

  const hpRatio = enemyHP.value / enemyMaxHP.value
  const masterBall = ballNum === 26
  const escapePerShake = masterBall ? 0
    : ballNum === 25 ? 0.35 * hpRatio
    : ballNum === 24 ? 0.42 * hpRatio
    : 0.55 * hpRatio
  const shakes = masterBall ? 1 : 3

  let caught = true
  for (let s = 0; s < shakes; s++) {
    shakeKey.value++
    wiggling.value = true
    await delay(700)
    wiggling.value = false
    await delay(350)
    if (Math.random() < escapePerShake) { caught = false; break }
  }

  if (caught) {
    captureSuccess.value = true
    log(`Gotcha! ${enemyName.value} was caught!`)
    const dexId = isMissingNo.value ? '0' : String(rivalNum.value)
    if (encounter.shiny) {
      saveStore.shinydex[dexId] = 'caught'
      saveStore.pokedex[dexId] = 'caught'
    } else {
      saveStore.pokedex[dexId] = 'caught'
    }
    const catchXp = Math.floor(enemyLevel.value * 5 + Math.random() * 20)
    awardXp(catchXp)
    const caught = {
      id: rivalNum.value,
      lvl: encounter.level,
      hp: enemyMaxHP.value,
      xp: 0,
      moves: enemyMoves.value.map(m => ({ id: m.id, pp: m.entry.pp })),
      slot: 0,
      shiny: encounter.shiny,
    }
    const slot = saveStore.team.findIndex(s => s.id === 0)
    if (slot !== -1) {
      caught.slot = slot + 1
      saveStore.team[slot] = caught
    } else {
      caught.slot = saveStore.pc.length + 1
      saveStore.pc.push(caught)
      log(`Party full — ${enemyName.value} sent to PC!`)
    }
    saveStore.save()
    battleOver.value = true
  } else {
    log(`Oh no! ${enemyName.value} broke free!`)
    catching.value = false
    await delay(400)
    enemyTurn()
  }
}

// ── Navigation ────────────────────────────────────────────────────
function goBack() {
  // Reset trainer encounter flag so next wild battle is clean
  encounter.isTrainer = false
  encounter.trainerId = ''
  encounter.trainerTeam = []
  router.push('/game')
}

function goToHealthCenter() {
  saveStore.team.forEach(slot => {
    if (!slot.id) return
    const baseHP = STATS[String(slot.id)]?.hp ?? 45
    slot.hp = calcMaxHP(baseHP, slot.lvl)
    slot.moves.forEach(m => {
      const entry = MOVES[String(m.id)]
      if (entry) m.pp = entry.pp
    })
  })
  encounter.isTrainer = false
  encounter.trainerId = ''
  encounter.trainerTeam = []
  saveStore.save()
  router.push('/game')
}

onMounted(async () => {
  const hasAlive = saveStore.team.some(s => s.id > 0 && s.hp > 0)
  if (!hasAlive) {
    battleOver.value = true
    allFainted.value = true
    log('All your Pokémon have fainted! Visit the Health Center to recover.')
    return
  }

  // ── No valid encounter (e.g. direct page reload) ──────────────────
  if (encounter.number <= 0 && !encounter.isTrainer) {
    if (Math.random() < 1 / 5) {
      // MissingNo. appears!
      isMissingNo.value    = true
      missingNoLevel.value = Math.floor(Math.random() * 20) + 80
      enemyHP.value   = enemyMaxHP.value   // reactive — uses missingNoLevel
      playerHP.value  = active.value?.hp ?? playerMaxHP.value
      pickEnemyMoves()
      loadPlayerMoves()
      pokemonVisible.value = true
      log('A wild MissingNo. appeared!!')
      setTimeout(() => { canAct.value = true }, 600)
    } else {
      router.push('/game')
    }
    return
  }

  enemyHP.value = enemyMaxHP.value
  playerHP.value = active.value?.hp ?? playerMaxHP.value
  pickEnemyMoves()
  loadPlayerMoves()

  if (encounter.isTrainer) {
    // Show trainer sprite for 3 seconds
    trainerVisible.value = true
    log(`${encounter.trainerName} wants to battle!`)
    await delay(3000)
    // Trainer exits to the right
    trainerAnimClass.value = 'anim-trainer-exit-right'
    await delay(500)
    trainerVisible.value = false
    trainerAnimClass.value = ''
    // First Pokémon enters from the top
    log(`${encounter.trainerName} sent out ${enemyName.value}!`)
    pokemonVisible.value = true
    pokemonAnimClass.value = 'anim-pokemon-enter'
    await delay(500)
    pokemonAnimClass.value = ''
    canAct.value = true
  } else {
    pokemonVisible.value = true
    log(`A wild ${enemyName.value} appeared!`)
    const bid = String(rivalNum.value)
    if (saveStore.pokedex[bid] !== 'caught') saveStore.pokedex[bid] = 'seen'
    if (encounter.shiny && saveStore.shinydex[bid] !== 'caught') saveStore.shinydex[bid] = 'seen'
    saveStore.save()
    setTimeout(() => { canAct.value = true }, 600)
  }
})
</script>

<style scoped>
.battle-page {
  height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #060e16;
  background-image: radial-gradient(circle, #1a2a3a 1.5px, transparent 1.5px);
  background-size: 22px 22px;
}

main {
  height: 500px;
  width: 700px;
  flex-shrink: 0;
  box-shadow: 0 0 80px rgba(0,0,0,0.6);
  position: relative;
  background: #0e1a26;
}

/* ── Arena ── */
.monitor {
  display: inline-block;
  height: 349px;
  width: 500px;
  background-size: cover;
  position: relative;
  overflow: hidden;
}

/* ── HUDs ── */
.enemy-hud {
  position: absolute;
  top: 10px; left: 10px;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 5px 8px;
  border-radius: 6px;
  min-width: 150px;
  font-size: 12px;
  z-index: 5;
}
.player-hud {
  position: absolute;
  bottom: 70px; right: 10px;
  background: rgba(0,0,0,0.7);
  color: white;
  padding: 5px 8px;
  border-radius: 6px;
  min-width: 150px;
  font-size: 12px;
  z-index: 5;
  text-align: right;
}
.hud-name { font-weight: bold; margin-bottom: 3px; display: flex; align-items: center; gap: 4px; }
.hud-ball { width: 12px; height: 12px; vertical-align: middle; }
.hud-ball--shiny { filter: sepia(1) saturate(4) hue-rotate(10deg) brightness(1.3); }
.hud-lv { font-weight: normal; font-size: 11px; }
.shiny-badge { font-size: 13px; line-height: 1; }
.hud-hp { font-size: 11px; margin-top: 2px; }
.hp-bar-wrap { width: 100%; height: 6px; background: #555; border-radius: 3px; overflow: hidden; }
.hp-bar { height: 100%; border-radius: 3px; transition: width 0.3s, background-color 0.3s; }

/* ── Battle log ── */
.battle-log {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 62px;
  background: rgba(0,0,0,0.78);
  color: #fff;
  overflow-y: auto;
  padding: 4px 8px;
  font-size: 12px;
  line-height: 1.4;
  z-index: 5;
}
.battle-log p { margin: 0; padding: 1px 0; }

/* ── Sprites ── */
#rival { position: absolute; top: 40%; right: 20%; transform: translateY(-50%); }
#rival > img { width: 150%; }
#catch-pos { position: absolute; top: 40%; right: 20%; transform: translateY(-50%); }
#catch-pos > img { width: 36px; }
#pokemon { position: absolute; top: 190px; left: 20px; }
#pokemon > img { width: 200%; }

/* ── Catch ball animation ── */
.greyed { filter: grayscale(1) brightness(0.5); }
@keyframes wiggle {
  0%   { transform: rotate(0deg); }
  20%  { transform: rotate(-22deg); }
  50%  { transform: rotate(22deg); }
  80%  { transform: rotate(-22deg); }
  100% { transform: rotate(0deg); }
}
.wiggle { animation: wiggle 0.65s ease-in-out 1; }

/* ── Bag row (desktop: top-right panel) ── */
.bag-row {
  position: absolute;
  top: 0; left: 500px;
  width: 200px; height: 200px;
  background: #0b1520;
  border-left: 2px solid #060e16;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  padding: 14px;
  gap: 10px;
  flex-wrap: wrap;
}
.bag-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  padding: 10px 10px 8px;
  border: 2px solid #2a4268;
  background: #1c3050;
  clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);
  width: 68px;
  transition: background 0.1s;
}
.bag-slot:hover:not(.act-disabled):not(.trainer-slot) { background: #243a60; }
.bag-slot > img { width: 36px; height: 36px; object-fit: contain; filter: drop-shadow(0 0 4px rgba(0,0,0,0.8)); }
.bag-item-count {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px;
  color: #c8dff8;
  letter-spacing: 0.5px;
}

/* ── Move buttons ── */
.tackle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  padding: 10px;
  height: 151px; width: 500px;
  position: absolute;
  bottom: 0; left: 0;
  background: #060e16;
  box-sizing: border-box;
}
.Power1, .Power2, .Power3, .Power4 {
  width: 100%; height: 100%;
  border: 2px solid #2a4268;
  border-radius: 0;
  background: #1c3050;
  color: #c8dff8;
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
  cursor: pointer;
  user-select: none;
  box-sizing: border-box;
}
/* Cut inner corners toward center */
.Power1 { clip-path: polygon(0 0, 100% 0, 100% calc(100% - 28px), calc(100% - 28px) 100%, 0 100%); }
.Power2 { clip-path: polygon(0 0, 100% 0, 100% 100%, 28px 100%, 0 calc(100% - 28px)); }
.Power3 { clip-path: polygon(0 0, calc(100% - 28px) 0, 100% 28px, 100% 100%, 0 100%); }
.Power4 { clip-path: polygon(28px 0, 100% 0, 100% 100%, 0 100%, 0 28px); }
.Power1:hover:not(.act-disabled), .Power2:hover:not(.act-disabled),
.Power3:hover:not(.act-disabled), .Power4:hover:not(.act-disabled) { background: #243a60; }
.Power1 > h2, .Power2 > h2, .Power3 > h2, .Power4 > h2 {
  font-family: 'Press Start 2P', monospace;
  font-size: 8px;
  font-weight: normal;
  margin: 0; padding: 0;
  text-align: center;
  line-height: 1.5;
  white-space: normal;
  word-break: break-word;
}

/* ── Team slots ── */
.pokemons {
  display: inline-block;
  height: 300px; width: 200px;
  position: absolute;
  bottom: 0; right: 0;
  vertical-align: bottom;
  background: #0e120e;
}
.pokemons > div {
  box-sizing: border-box;
  height: 50px; width: 200px;
  background: transparent;
  border-top: 1px solid rgba(0,0,0,0.4);
  cursor: pointer;
  display: flex;
  align-items: stretch;
  padding: 3px 3px 3px 3px;
}
.pokemons > div:hover:not(.act-disabled) .party-card { filter: drop-shadow(0 2px 0 rgba(0,0,0,0.5)) brightness(1.15); }

/* ── Map-style party card ── */
.party-card {
  position: relative;
  width: 100%;
  filter: drop-shadow(0 2px 0 rgba(0,0,0,0.5));
  transition: filter 0.12s;
}
.party-ball {
  position: absolute;
  top: 3px; left: 3px;
  width: 14px; height: 14px;
  image-rendering: pixelated;
  opacity: 0.9;
  z-index: 2;
}
.party-entry {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 6px 4px 4px;
  height: 100%;
  box-sizing: border-box;
  background:
    linear-gradient(135deg,
      transparent        18px,
      #3a6030            18px,
      #3a6030            20px,
      transparent        20px
    ),
    linear-gradient(180deg, #2a4a22 0%, #162e10 100%);
  clip-path: polygon(20px 0, 100% 0, 100% 100%, 0 100%, 0 20px);
}
.slot-dead .party-entry {
  background:
    linear-gradient(135deg,
      transparent 18px, #602020 18px, #602020 20px, transparent 20px
    ),
    linear-gradient(180deg, #3a1010 0%, #1e0808 100%);
}
.party-sprite {
  width: 34px;
  height: 34px;
  image-rendering: pixelated;
  flex-shrink: 0;
  margin-left: 12px;
}
.party-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.party-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  color: #fff;
  text-shadow: 1px 1px 0 rgba(0,0,0,0.55);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.party-lv {
  font-family: 'Press Start 2P', monospace;
  font-size: 4px;
  color: rgba(255,255,255,0.75);
}
.party-lv b { font-size: 5px; }
.party-hp-bar {
  height: 4px;
  background: rgba(0,0,0,0.35);
  border-radius: 1px;
  overflow: hidden;
  margin-top: 2px;
}
.party-hp-fill {
  height: 100%;
  border-radius: 1px;
  transition: width 0.3s, background-color 0.3s;
}

/* Active slot (first) — slightly brighter tint */
.pokemons > div:first-child .party-entry {
  background:
    linear-gradient(135deg,
      transparent 18px, #4a7a38 18px, #4a7a38 20px, transparent 20px
    ),
    linear-gradient(180deg, #3a5e2a 0%, #1e3e12 100%);
}

/* ── Disabled / item states ── */
.act-disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
.trainer-slot { cursor: default; pointer-events: none; }
.bag-slot.item-active { border-color: #44cc88 !important; background: #183828 !important; }
.bag-slot.item-active .bag-item-count { color: #88ffbb; }
.item-target .party-entry { animation: pulse-item 0.75s ease-in-out infinite alternate; cursor: pointer; }
.item-invalid { opacity: 0.35; pointer-events: none; }
@keyframes pulse-item {
  from { filter: brightness(1); }
  to   { filter: brightness(1.6) saturate(1.4); }
}

/* ════════════════════════════════════════════════════════════
   MOBILE — full-screen column layout, GBA-inspired palette
   ════════════════════════════════════════════════════════════ */
@media (max-width: 600px) {
  .battle-page {
    min-height: 100dvh;
    padding-top: 70px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
    background: #0e1a26;
  }
  main {
    width: 100%;
    height: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: none;
    background: #0e1a26;
    position: relative;
  }

  /* ── Arena ── */
  .monitor {
    width: 100%;
    height: 48vw;
    min-height: 210px;
    display: block;
    flex-shrink: 0;
    border-radius: 0;
    border-bottom: 3px solid #060e16;
  }

  /* Sprite positions inside the shrunken arena */
  #rival        { top: 24%; right: 10%; }
  #rival > img  { width: 100%; }
  #catch-pos    { top: 24%; right: 10%; }
  #pokemon      { top: auto; bottom: 62px; left: 5%; }
  #pokemon > img { width: 130%; }

  /* ── HUDs ── */
  .enemy-hud {
    top: 8px; left: 8px; right: auto;
    font-size: 10px; padding: 6px 10px;
    min-width: 115px;
    border-radius: 4px;
    background: rgba(0,0,0,0.75);
  }
  .player-hud {
    bottom: 66px; right: 8px; left: auto;
    font-size: 10px; padding: 6px 10px;
    min-width: 115px;
    border-radius: 4px;
    background: rgba(0,0,0,0.75);
    text-align: right;
  }
  .battle-log { font-size: 10px; height: 58px; padding: 6px 10px; line-height: 1.5; }

  /* ── Bag row (mobile: horizontal item strip) ── */
  .bag-row {
    position: relative;
    top: auto; left: auto;
    width: 100%; height: auto;
    background: #0b1520;
    border-top: 2px solid #060e16;
    border-bottom: 2px solid #060e16;
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 8px 12px;
    gap: 10px;
    flex-shrink: 0;
    flex-wrap: nowrap;
  }
  .bag-slot {
    width: 56px; height: 56px;
    padding: 8px 8px 6px;
    gap: 4px;
  }
  .bag-slot > img { width: 28px; height: 28px; }
  .bag-item-count { font-size: 6px; }

  /* ── Move buttons — 2×2 grid with cut inner corners ── */
  .tackle {
    position: relative;
    bottom: auto; left: auto;
    width: 100%;
    height: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 16px;
    border: none;
    background: #060e16;
  }
  .Power1, .Power2, .Power3, .Power4 {
    position: relative;
    top: auto; bottom: auto; left: auto; right: auto;
    width: 100%; height: 68px;
    border: 2px solid #2a4268;
    border-radius: 0;
    background: #1c3050;
    color: #c8dff8;
    font-family: 'Press Start 2P', monospace;
    font-size: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 10px;
    cursor: pointer;
    user-select: none;
  }
  /* Cut inner corners toward center — creates diamond space for pokeball */
  .Power1 { clip-path: polygon(0 0, 100% 0, 100% calc(100% - 32px), calc(100% - 32px) 100%, 0 100%); }
  .Power2 { clip-path: polygon(0 0, 100% 0, 100% 100%, 32px 100%, 0 calc(100% - 32px)); }
  .Power3 { clip-path: polygon(0 0, calc(100% - 32px) 0, 100% 32px, 100% 100%, 0 100%); }
  .Power4 { clip-path: polygon(32px 0, 100% 0, 100% 100%, 0 100%, 0 32px); }

  .Power1:hover:not(.act-disabled),
  .Power2:hover:not(.act-disabled),
  .Power3:hover:not(.act-disabled),
  .Power4:hover:not(.act-disabled) { background: #243a60; box-shadow: none; }
  .Power1 > h2, .Power2 > h2, .Power3 > h2, .Power4 > h2 {
    font-family: 'Press Start 2P', monospace;
    font-size: 7px;
    font-weight: normal;
    margin: 0; padding: 0;
    text-align: center;
    line-height: 1.5;
    white-space: normal;
    word-break: break-word;
  }

  /* ── Party grid — 3 per row ── */
  .pokemons {
    position: relative;
    bottom: auto; right: auto;
    width: 100%;
    height: auto;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: 56px 56px;
    background: #0e120e;
    border-top: 2px solid #060e16;
    flex-shrink: 0;
    gap: 2px;
    padding: 3px;
    box-sizing: border-box;
  }
  .pokemons > div {
    width: auto; height: 100%;
    background: transparent;
    border: none;
    padding: 0;
    overflow: hidden;
    display: flex;
    align-items: stretch;
    cursor: pointer;
  }
  .pokemons > div .party-card { width: 100%; }
  .pokemons > div .party-entry {
    padding: 3px 4px 3px 3px;
    gap: 2px;
  }
  .pokemons > div .party-sprite {
    width: 30px; height: 30px;
    margin-left: 10px;
  }
  .pokemons > div .party-name { font-size: 4px; }
  .pokemons > div .party-lv { font-size: 3px; }
  .pokemons > div .party-lv b { font-size: 4px; }
  .pokemons > div .party-ball { width: 11px; height: 11px; }
  @keyframes pulse-slot {
    from { opacity: 1; }
    to   { opacity: 1; }
  }
}

/* ── Trainer / Pokémon entrance & exit animations ── */
.rival-trainer-img { width: 150%; display: block; }

@keyframes kf-trainer-exit-right {
  from { transform: translateX(0);     opacity: 1; }
  to   { transform: translateX(180px); opacity: 0; }
}
@keyframes kf-trainer-enter-left {
  from { transform: translateX(-180px); opacity: 0; }
  to   { transform: translateX(0);      opacity: 1; }
}
@keyframes kf-trainer-enter-right {
  from { transform: translateX(180px); opacity: 0; }
  to   { transform: translateX(0);     opacity: 1; }
}
@keyframes kf-pokemon-enter {
  from { transform: translateY(-80px); opacity: 0; }
  to   { transform: translateY(0);     opacity: 1; }
}
@keyframes kf-pokemon-faint {
  from { transform: translateY(0);    opacity: 1; }
  to   { transform: translateY(60px); opacity: 0; }
}
.anim-trainer-exit-right { animation: kf-trainer-exit-right 0.5s  ease-in  forwards; }
.anim-trainer-enter-left  { animation: kf-trainer-enter-left  0.5s ease-out forwards; }
.anim-trainer-enter-right { animation: kf-trainer-enter-right 0.5s ease-out forwards; }
.anim-pokemon-enter      { animation: kf-pokemon-enter       0.45s ease-out forwards; }
.anim-pokemon-faint      { animation: kf-pokemon-faint       0.55s ease-in  forwards; }
.slot-choose .party-entry { animation: pulse-card 0.8s ease-in-out infinite alternate; cursor: pointer; }
@keyframes pulse-card {
  from { filter: brightness(1); }
  to   { filter: brightness(1.5); }
}
.slot-dead .party-entry {
  background:
    linear-gradient(135deg,
      transparent 18px, #602020 18px, #602020 20px, transparent 20px
    ),
    linear-gradient(180deg, #3a1010 0%, #1e0808 100%);
}
</style>
