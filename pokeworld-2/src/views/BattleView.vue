<template>
  <main>
    <!-- Arena -->
    <div class="monitor" :style="{ backgroundImage: `url(/textures/Battle/Arena/background${arenaNum}.png)` }">

      <!-- Enemy HUD -->
      <div class="enemy-hud">
        <div class="hud-name">
          {{ enemyName }}
          <img v-if="saveStore.pokedex[String(rivalNum)] === 'caught'" src="/textures/HUD/Pokeball.png" class="hud-ball" />
          <span class="hud-lv">Lv.{{ encounter.level }}</span>
        </div>
        <div class="hp-bar-wrap"><div class="hp-bar" :style="enemyHpStyle"></div></div>
        <div class="hud-hp">{{ enemyHP }}/{{ enemyMaxHP }}</div>
      </div>

      <!-- Player HUD -->
      <div class="player-hud">
        <div class="hud-name">{{ playerName }} <span class="hud-lv">Lv.{{ playerLvl }}</span></div>
        <div class="hp-bar-wrap"><div class="hp-bar" :style="playerHpStyle"></div></div>
        <div class="hud-hp">{{ playerHP }}/{{ playerMaxHP }}</div>
      </div>

      <!-- Rival sprite -->
      <div id="rival" v-show="!catching">
        <img id="RivalImg" :src="rivalSprite" alt="" />
      </div>

      <!-- Catch ball — separate div so #rival > img width doesn't apply -->
      <div id="catch-pos" v-show="catching">
        <img :key="shakeKey" src="/textures/HUD/Pokeball.png"
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

    <!-- Pokéball (catch button) -->
    <div class="pokeball" @click="onCatch" :class="{ 'act-disabled': !canAct || battleOver || captureSuccess || forcedSwitch }">
      <img src="/textures/HUD/Pokeball.png" alt="" />
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

    <!-- Team slots — click to switch -->
    <div class="pokemons">
      <div v-for="(slot, i) in saveStore.team" :key="i" :id="`Slot${i+1}`"
           :class="{
             'act-disabled': battleOver || i === 0 || !slot.id || slot.hp <= 0
               || (!canAct && !forcedSwitch),
             'slot-choose': forcedSwitch && i > 0 && slot.id > 0 && slot.hp > 0,
             'slot-dead': slot.id > 0 && slot.hp <= 0,
           }"
           @click="onSwitchPokemon(i)">
        <p>{{ slotName(slot) }}</p>
        <p>{{ slotHP(slot) }}</p>
      </div>
    </div>
  </main>

  <AppHeader
    :label="allFainted ? 'Health Center' : 'BACK TO MAP'"
    @nav-click="allFainted ? goToHealthCenter() : (forcedSwitch ? null : router.push('/game'))"
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

// ── Sprites ───────────────────────────────────────────────────────
const rivalNum = computed(() => encounter.number > 0 ? encounter.number : 25)
const rivalSprite = computed(() => `/textures/Battle/Normal/Front/Gif/${padId(rivalNum.value)}.gif`)
const playerPokemonSprite = computed(() => `/textures/Battle/Normal/Back/Gif/${padId(active.value?.id ?? 1)}.gif`)

// ── Names / levels ────────────────────────────────────────────────
const enemyName = computed(() => pokedex(rivalNum.value))
const playerName = computed(() => pokedex(active.value?.id ?? 1))
const playerLvl = computed(() => active.value?.lvl ?? 1)

// ── HP ────────────────────────────────────────────────────────────
function calcMaxHP(baseHP: number, level: number): number {
  return Math.floor(2 * baseHP * level / 100) + level + 10
}
const enemyMaxHP = computed(() => calcMaxHP(STATS[String(rivalNum.value)]?.hp ?? 45, encounter.level))
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
    .filter(e => e.level <= encounter.level && MOVES[String(e.id)]?.power > 0)
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
function slotName(slot: { id: number; lvl: number }) {
  return slot.id ? `${pokedex(slot.id)} | LVL ${slot.lvl}` : ''
}
function slotHP(slot: { id: number; hp: number }) {
  if (!slot.id) return ''
  return `${slot.hp}/${calcMaxHP(STATS[String(slot.id)]?.hp ?? 45, saveStore.team.find(s => s.id === slot.id)?.lvl ?? 1)}`
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
        const eSt = STATS[String(rivalNum.value)]
        const pSt = STATS[String(active.value?.id ?? 1)]
        const isPhys = PHYSICAL_TYPES.has(move.entry.type)
        const atkStat = isPhys ? (eSt?.atk ?? 50) : (eSt?.spa ?? 50)
        const defStat = isPhys ? (pSt?.def ?? 50) : (pSt?.spd ?? 50)
        const result = calcDamage(
          atkStat, defStat, move.entry.power, encounter.level,
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
      const eSt = STATS[String(rivalNum.value)]
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
    log(`Wild ${enemyName.value} fainted!`)
    const totalXp = Math.floor(encounter.level * 5 + Math.random() * 20)
    awardXp(totalXp)
    battleOver.value = true
    return
  }

  await delay(800)
  enemyTurn()
}

// ── Switch Pokémon ────────────────────────────────────────────────
async function onSwitchPokemon(i: number) {
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

// ── Throw Pokéball ────────────────────────────────────────────────
async function onCatch() {
  if (!canAct.value || battleOver.value || captureSuccess.value) return
  canAct.value = false
  catching.value = true
  log('You threw a Pokéball!')

  const hpRatio = enemyHP.value / enemyMaxHP.value
  const escapePerShake = 0.55 * hpRatio

  let caught = true
  for (let s = 0; s < 3; s++) {
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
    saveStore.pokedex[String(rivalNum.value)] = 'caught'
    const catchXp = Math.floor(encounter.level * 5 + Math.random() * 20)
    awardXp(catchXp)
    const caught = {
      id: rivalNum.value,
      lvl: encounter.level,
      hp: enemyMaxHP.value,
      xp: 0,
      moves: enemyMoves.value.map(m => ({ id: m.id, pp: m.entry.pp })),
      slot: 0,
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

// ── Init ──────────────────────────────────────────────────────────
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
  saveStore.save()
  router.push('/game')
}

onMounted(() => {
  const hasAlive = saveStore.team.some(s => s.id > 0 && s.hp > 0)
  if (!hasAlive) {
    battleOver.value = true
    allFainted.value = true
    log('All your Pokémon have fainted! Visit the Health Center to recover.')
    return
  }
  enemyHP.value = enemyMaxHP.value
  playerHP.value = active.value?.hp ?? playerMaxHP.value
  pickEnemyMoves()
  loadPlayerMoves()
  log(`A wild ${enemyName.value} appeared!`)
  setTimeout(() => { canAct.value = true }, 600)
})
</script>

<style scoped>
main {
  height: 500px;
  width: 700px;
  margin: auto;
  margin-top: 120px;
  box-shadow: 0 0 100px #888;
  position: relative;
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
.hud-lv { font-weight: normal; font-size: 11px; }
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
#catch-pos > img { width: 80px; }
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

/* ── Pokéball (catch button) ── */
.pokeball {
  display: inline-block;
  height: 200px; width: 200px;
  top: 0;
  position: absolute;
  background-color: #CEC;
  border: 1px solid black;
  cursor: pointer;
  vertical-align: top;
}
.pokeball > img { width: 150px; margin: 25px; }
.pokeball:hover { box-shadow: inset 0 0 50px #888; }

/* ── Move buttons ── */
.tackle {
  display: inline-block;
  height: 151px; width: 500px;
  position: absolute;
  bottom: 0; left: 0;
  border-bottom: 1px solid black;
  border-left: 1px solid black;
  border-top: 1px solid black;
}
.Power1, .Power2, .Power3, .Power4 {
  position: absolute;
  width: 200px; font-size: 20px; height: 50px;
  border: 3px solid black;
  border-radius: 30px;
  text-align: center;
  display: inline-block;
  cursor: pointer;
  user-select: none;
}
.Power1:hover, .Power2:hover, .Power3:hover, .Power4:hover { box-shadow: inset 0 0 20px #888; }
.Power1, .Power2 { bottom: 80px; }
.Power1, .Power3 { left: 40px; }
.Power2, .Power4 { left: 265px; }
.Power3, .Power4 { bottom: 10px; }
.Power1 > h2, .Power2 > h2, .Power3 > h2, .Power4 > h2 { margin: 4px; }

/* ── Team slots ── */
.pokemons {
  display: inline-block;
  height: 300px; width: 200px;
  position: absolute;
  bottom: 0; right: 0;
  text-align: right;
  font-weight: bold;
  vertical-align: bottom;
}
.pokemons > div {
  box-sizing: border-box;
  height: 50px; width: 200px;
  background-color: #a6a6a6;
  border-bottom: 1px solid black;
  border-left: 1px solid black;
  cursor: pointer;
}
.pokemons > div:first-child { color: white; background-color: grey; }
.pokemons > div:hover:not(.act-disabled) { background-color: #737373; color: white; }

/* ── Disabled ── */
.act-disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
.slot-choose { animation: pulse-slot 0.8s ease-in-out infinite alternate; cursor: pointer; }
@keyframes pulse-slot { from { background-color: #a6a6a6; } to { background-color: #4a8a4a; } }
.slot-dead { background-color: #5a2020 !important; color: #884444 !important; }
</style>
