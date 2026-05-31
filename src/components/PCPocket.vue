<template>
  <div class="pc-pocket" ref="panelRef" :style="dragStyle">

    <!-- Header — drag handle -->
    <div class="pc-header" @pointerdown="onHandlePointerDown" style="cursor:grab;touch-action:none">
      <span class="pc-title">BILL'S PC</span>
      <span class="pc-sub">BOX 1</span>
      <span class="blink">▮</span>
      <div class="pc-header-actions" @pointerdown.stop>
        <button class="pc-close-btn" @click="openFullscreen" title="Fullscreen">⛶</button>
        <button class="pc-close-btn" @click="$emit('close')" title="Close">✕</button>
      </div>
    </div>

    <div class="pc-layout">

      <!-- PC Box -->
      <div class="box-panel">

        <!-- Filter bar -->
        <div class="filter-bar">
          <input
            v-model="searchQuery"
            class="pc-search"
            type="text"
            placeholder="Search name, #, nickname…"
            @pointerdown.stop
          />
          <div class="filter-types">
            <button
              v-for="(name, tid) in TYPE_NAMES" :key="tid"
              class="ftype-chip"
              :class="{ active: filterTypes.includes(Number(tid)) }"
              :style="filterTypes.includes(Number(tid)) ? { background: TYPE_COLORS[Number(tid)], borderColor: TYPE_COLORS[Number(tid)] } : { borderColor: TYPE_COLORS[Number(tid)] }"
              @click="toggleTypeFilter(Number(tid))"
            >{{ name }}</button>
          </div>
          <div class="filter-shiny">
            <button class="fshiny-btn" :class="{ active: filterShiny === null }"  @click="filterShiny = null">ALL</button>
            <button class="fshiny-btn" :class="{ active: filterShiny === true  }" @click="filterShiny = true">✨</button>
            <button class="fshiny-btn" :class="{ active: filterShiny === false }" @click="filterShiny = false">NO ✨</button>
          </div>
        </div>

        <div class="box-grid">
          <div
            v-for="cell in pcCells"
            :key="`pc-${cell}`"
            v-show="cardVisible(saveStore.pc[cell - 1])"
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
                @click="openSlotInfo(saveStore.pc[cell - 1], -(cell))"
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
                  <div class="card-hp-bar">
                    <div class="card-hp-fill" :style="{ width: hpPct(saveStore.pc[cell - 1]) * 100 + '%', backgroundColor: hpColor(hpPct(saveStore.pc[cell - 1])) }"></div>
                  </div>
                </div>
                <button v-if="saveStore.pc[cell - 1].pendingEvo"
                  class="evolve-btn evolve-btn-card"
                  @click.stop="evolveSlot(saveStore.pc[cell - 1])">
                  ★ EVO
                </button>
              </div>
            </template>
            <template v-else>
              <div class="cell-empty">·</div>
            </template>
          </div>
        </div>
        <div class="box-footer">
          <span>{{ saveStore.pc.filter(p => p?.id).length }} in storage</span>
        </div>
      </div>

      <!-- Team Panel -->
      <div class="team-panel">
        <div class="team-header">
          <span>PARTY</span>
          <button class="heal-all-btn" @click="healAll" title="Heal All">♥</button>
        </div>
        <div class="xpshare-row">
          <span class="xpshare-label">XP SHR</span>
          <button class="xpshare-toggle" :class="{ active: saveStore.xpShare }"
            @click="saveStore.xpShare = !saveStore.xpShare; saveStore.save()">
            {{ saveStore.xpShare ? 'ON' : 'OFF' }}
          </button>
        </div>
        <div class="team-list">
          <div
            v-for="(slot, i) in saveStore.team" :key="`team-${i}`"
            class="team-slot"
            :class="{ 'slot-empty': !slot.id, 'slot-drop': dragOver === `team-${i}`, 'slot-dragging': dragSrc === `team-${i}` }"
            :style="slot.id ? slotBorder(slot.id) : {}"
            :draggable="!!slot.id"
            @click="slot.id ? openSlotInfo(slot, i) : undefined"
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
                  <div class="slot-hp-bar"><div class="slot-hp-fill" :style="{ width: hpPct(slot) * 100 + '%', backgroundColor: hpColor(hpPct(slot)) }"></div></div>
                </div>
                <div class="bar-row">
                  <span class="bar-label">Lv{{ slot.lvl }}</span>
                  <div class="slot-xp-bar"><div class="slot-xp-fill" :style="{ width: xpPct(slot) * 100 + '%' }"></div></div>
                </div>
              </div>
              <!-- Reorder arrows + gear -->
              <div class="slot-actions" @click.stop>
                <button class="slot-arrow" :disabled="i === 0 || !saveStore.team[i-1].id" @click.stop="moveTeamSlot(i, 'up')" title="Move up">▲</button>
                <button class="slot-arrow" :disabled="i >= saveStore.team.length - 1 || !saveStore.team[i+1].id" @click.stop="moveTeamSlot(i, 'down')" title="Move down">▼</button>
                <button class="gear-btn" @click.stop="openMoveEditor(slot, 'team', i)" title="Manage"><i class="fa-solid fa-gear"></i></button>
              </div>
            </template>
            <template v-else>
              <div class="slot-empty-label">— EMPTY —</div>
            </template>
          </div>
        </div>
        <div class="party-count">{{ activeTeam.length }}/6</div>
      </div>
    </div>

    <!-- Party slot info modal -->
    <PokemonInfoModal
      v-if="infoSlot"
      :slot="infoSlot"
      @close="infoSlot = null"
      @evolve="evolveInfoSlot"
      @evolve-special="evolveInfoSlotTo"
    />

    <!-- Tip -->
    <div class="pc-tip">
      <span class="blink-slow">▶</span> DRAG to move Pokémon
    </div>

    <!-- Move editor modal -->
    <div v-if="moveEditor.show" class="modal-overlay" @click.self="moveEditor.show = false">
      <div class="modal-box move-modal">
        <div class="move-modal-header">
          <div class="modal-title">
            {{ pokedex(moveEditor.slot?.id ?? 0) }} — MOVES
            <span class="move-modal-sub">{{ moveEditor.slot?.lvl ? `LV.${moveEditor.slot.lvl}` : '' }}</span>
          </div>
          <div class="view-toggle">
            <button class="view-btn" :class="{ active: moveEditor.view === 'list' }" @click="moveEditor.view = 'list'">☰</button>
            <button class="view-btn" :class="{ active: moveEditor.view === 'grid' }" @click="moveEditor.view = 'grid'">⊞</button>
          </div>
        </div>
        <div class="nickname-row">
          <span class="nickname-label">NICK</span>
          <input v-model="moveEditor.nickname" class="nickname-input" :placeholder="pokedex(moveEditor.slot?.id ?? 0)" maxlength="12" spellcheck="false" autocomplete="off" />
        </div>
        <div class="active-moves-row">
          <div v-for="n in 4" :key="n" class="active-move-slot" :class="{ filled: moveEditor.active[n-1] }">
            <template v-if="moveEditor.active[n-1]">
              <span class="am-name">{{ MOVES[String(moveEditor.active[n-1])]?.name }}</span>
              <span class="am-type-badge" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(moveEditor.active[n-1])]?.type ?? 1] ?? '#888' }">{{ TYPE_NAMES[MOVES[String(moveEditor.active[n-1])]?.type ?? 1] ?? '???' }}</span>
            </template>
            <template v-else><span class="am-empty">—</span></template>
          </div>
        </div>
        <div class="move-list" :class="{ 'move-list-grid': moveEditor.view === 'grid' }">
          <template v-for="entry in moveEditor.learnset" :key="entry.id">
            <div v-if="moveEditor.view === 'list'" class="move-row"
                 :class="{ 'move-active': moveEditor.active.includes(entry.id), 'move-locked': entry.level > (moveEditor.slot?.lvl ?? 0), 'move-disabled': !moveEditor.active.includes(entry.id) && moveEditor.active.filter(Boolean).length >= 4 && entry.level <= (moveEditor.slot?.lvl ?? 0) }"
                 @click="toggleMove(entry.id, entry.level)">
              <div class="move-row-left">
                <span class="move-check">{{ moveEditor.active.includes(entry.id) ? '✓' : '○' }}</span>
                <span class="move-name">{{ MOVES[String(entry.id)]?.name ?? '???' }}</span>
                <span class="move-type-badge" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(entry.id)]?.type ?? 1] ?? '#888' }">{{ TYPE_NAMES[MOVES[String(entry.id)]?.type ?? 1] ?? '???' }}</span>
              </div>
              <div class="move-row-right">
                <span v-if="entry.tag === 'type'" class="move-src-tag">TYPE</span>
                <span v-else-if="entry.level > 0" class="move-lv-tag">Lv.{{ entry.level }}</span>
                <span class="move-pp">PP {{ MOVES[String(entry.id)]?.pp }}</span>
              </div>
              <div v-if="entry.level > (moveEditor.slot?.lvl ?? 0)" class="move-locked-overlay">Lv.{{ entry.level }}</div>
            </div>
            <div v-else class="move-box"
                 :class="{ 'move-active': moveEditor.active.includes(entry.id), 'move-locked': entry.level > (moveEditor.slot?.lvl ?? 0), 'move-disabled': !moveEditor.active.includes(entry.id) && moveEditor.active.filter(Boolean).length >= 4 && entry.level <= (moveEditor.slot?.lvl ?? 0) }"
                 @click="toggleMove(entry.id, entry.level)">
              <span class="move-box-check">{{ moveEditor.active.includes(entry.id) ? '✓' : '' }}</span>
              <span class="move-box-name">{{ MOVES[String(entry.id)]?.name ?? '???' }}</span>
              <span class="move-box-type" :style="{ backgroundColor: TYPE_COLORS[MOVES[String(entry.id)]?.type ?? 1] ?? '#888' }">{{ TYPE_NAMES[MOVES[String(entry.id)]?.type ?? 1] ?? '???' }}</span>
              <div v-if="entry.level > (moveEditor.slot?.lvl ?? 0)" class="move-locked-overlay">🔒 Lv.{{ entry.level }}</div>
            </div>
          </template>
        </div>
        <div class="modal-actions modal-actions-split">
          <button class="modal-btn modal-release" @click="releaseFromModal">✕ RELEASE</button>
          <div class="modal-actions-right">
            <button class="modal-btn modal-cancel" @click="moveEditor.show = false">CANCEL</button>
            <button class="modal-btn modal-confirm" @click="saveMoveEditor">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Release confirm -->
    <div v-if="releaseTarget.show" class="modal-overlay" @click.self="releaseTarget.show = false">
      <div class="modal-box">
        <div class="modal-title">RELEASE?</div>
        <div class="modal-body">Release <span class="modal-poke">{{ releaseTarget.name }}</span>?</div>
        <div class="modal-actions">
          <button class="modal-btn modal-cancel" @click="releaseTarget.show = false">NO</button>
          <button class="modal-btn modal-confirm" @click="confirmRelease">YES</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'
import { useSaveStore } from '../stores/save'
import { useModalStore } from '../stores/modals'
import { useDraggable } from '../composables/useDraggable'
import { pokedex, padId } from '../data/pokemon'
import statsData from '../data/pokemon-stats.json'
import movesData from '../data/moves.json'
import learnsetsData from '../data/learnsets.json'
import PokemonInfoModal from './PokemonInfoModal.vue'
import type { TeamSlot } from '../stores/save'

const emit = defineEmits<{ close: [] }>()
const modalStore = useModalStore()

function openFullscreen() {
  emit('close')
  modalStore.openPC()
}

const panelRef = ref<HTMLElement | null>(null)
const { dragStyle, onHandlePointerDown } = useDraggable('pkw_pc_pos', panelRef)

type StatsEntry = { hp: number; type1: number; type2: number }
const STATS = statsData as Record<string, StatsEntry>
type MoveEntry = { name: string; type: number; power: number; acc: number; pp: number }
const MOVES = movesData as Record<string, MoveEntry>
const LEARNSETS_DATA = learnsetsData as Record<string, { id: number; level: number }[]>

const saveStore = useSaveStore()

onMounted(() => { window.addEventListener('keydown', onKey) })
onUnmounted(() => { window.removeEventListener('keydown', onKey) })
function onKey(e: KeyboardEvent) { if (e.key === 'Escape') emit('close') }

const pcCells = computed(() => Math.max(5, saveStore.pc.filter(p => p?.id).length + 4))
const activeTeam = computed(() => saveStore.team.filter(s => s.id > 0))

// ── Party slot info + reorder ─────────────────────────────────────
const infoSlot      = ref<TeamSlot | null>(null)
const infoSlotIndex = ref(-1)

function openSlotInfo(slot: TeamSlot, index: number) {
  infoSlot.value      = { ...slot }
  infoSlotIndex.value = index  // negative = PC slot (no evolve from here)
}

function applyTeamEvolve(idx: number, toId: number) {
  let isShiny = false
  if (idx >= 0) {
    const old = saveStore.team[idx]
    if (!old) return
    isShiny = !!old.shiny
    saveStore.team.splice(idx, 1, { ...old, id: toId, pendingEvo: undefined })
  } else {
    const pcIdx = -idx - 1
    const old = saveStore.pc[pcIdx]
    if (!old) return
    isShiny = !!old.shiny
    saveStore.pc.splice(pcIdx, 1, { ...old, id: toId, pendingEvo: undefined })
  }
  if (isShiny) saveStore.shinydex[String(toId)] = 'caught'
  else         saveStore.pokedex[String(toId)]  = 'caught'
  saveStore.save()
  infoSlot.value = null
}

function evolveInfoSlot() {
  const idx = infoSlotIndex.value
  const slot = idx >= 0 ? saveStore.team[idx] : saveStore.pc[-idx - 1]
  if (!slot?.pendingEvo) return
  applyTeamEvolve(idx, slot.pendingEvo)
}

function evolveInfoSlotTo(toId: number) {
  applyTeamEvolve(infoSlotIndex.value, toId)
}

function moveTeamSlot(fromIdx: number, direction: 'up' | 'down') {
  const toIdx = direction === 'up' ? fromIdx - 1 : fromIdx + 1
  if (toIdx < 0 || toIdx >= saveStore.team.length) return
  const team = saveStore.team
  const a = { ...team[fromIdx] }
  const b = { ...team[toIdx] }
  team[fromIdx] = { ...b, slot: fromIdx + 1 }
  team[toIdx]   = { ...a, slot: toIdx + 1 }
  saveStore.save()
}

// ── Filters ───────────────────────────────────────────────────────
const filterTypes  = ref<number[]>([])
const filterShiny  = ref<null | true | false>(null)   // null=all, true=shiny, false=normal
const searchQuery  = ref('')

function toggleTypeFilter(t: number) {
  const i = filterTypes.value.indexOf(t)
  if (i === -1) filterTypes.value.push(t)
  else filterTypes.value.splice(i, 1)
}

function cardVisible(slot: any): boolean {
  if (!slot?.id) return true   // keep empty cells always visible
  if (searchQuery.value.trim()) {
    const q    = searchQuery.value.trim().toLowerCase()
    const name = pokedex(slot.id).toLowerCase()
    const num  = String(slot.id)
    const nick = (slot.nickname ?? '').toLowerCase()
    if (!name.includes(q) && !num.includes(q) && !nick.includes(q)) return false
  }
  if (filterTypes.value.length) {
    const types = getTypes(slot.id)
    if (!filterTypes.value.some(t => types.includes(t))) return false
  }
  if (filterShiny.value !== null) {
    if (filterShiny.value === true  && !slot.shiny) return false
    if (filterShiny.value === false &&  slot.shiny) return false
  }
  return true
}

const releaseTarget = reactive({ show: false, source: '' as 'pc' | 'team', index: -1, name: '' })
function askRelease(source: 'pc' | 'team', index: number, name: string) { releaseTarget.source = source; releaseTarget.index = index; releaseTarget.name = name; releaseTarget.show = true }
function confirmRelease() {
  if (releaseTarget.source === 'pc') saveStore.pc.splice(releaseTarget.index, 1)
  else saveStore.team[releaseTarget.index] = { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: releaseTarget.index + 1 }
  saveStore.save(); releaseTarget.show = false
}

const dragSrc = ref(''); const dragOver = ref('')

const TYPE_COLORS: Record<number, string> = { 1:'#9B9B6F',2:'#E8622C',3:'#B82820',4:'#4A6EDC',5:'#8830A0',6:'#D4B000',7:'#C8A840',8:'#DC3060',9:'#A89020',10:'#5AC0D0',11:'#7A9010',12:'#5010E0',13:'#5040A0',15:'#50A830',16:'#8070D8' }
const TYPE_NAMES: Record<number, string> = { 1:'NRM',2:'FIRE',3:'FGT',4:'WTR',5:'PSN',6:'ELC',7:'GND',8:'PSY',9:'RCK',10:'ICE',11:'BUG',12:'DRG',13:'GHO',15:'GRS',16:'FLY' }

function getTypes(id: number): number[] { const s=STATS[String(id)]; if(!s) return [1]; return s.type1!==s.type2?[s.type1,s.type2]:[s.type2] }
function typeBgH(id: number): string { const s=STATS[String(id)]; if(!s) return TYPE_COLORS[1]??'#888'; const c1=TYPE_COLORS[s.type1]??'#888',c2=TYPE_COLORS[s.type2]??'#888'; return s.type1===s.type2?c1:`linear-gradient(to right,${c1},${c2})` }
function slotBorder(id: number) { const s=STATS[String(id)]; if(!s) return {borderLeftColor:TYPE_COLORS[1]??'#888'}; const c1=TYPE_COLORS[s.type1]??'#888',c2=TYPE_COLORS[s.type2]??'#888'; if(s.type1===s.type2) return {borderLeftColor:c1}; return {background:`linear-gradient(#fff,#fff) padding-box,linear-gradient(to bottom,${c1},${c2}) border-box`,borderLeftColor:'transparent'} }
function xpToNext(lvl: number): number { return (lvl+1)**3-lvl**3 }
function xpPct(slot: {lvl:number;xp:number}): number { return Math.min(1,slot.xp/xpToNext(slot.lvl)) }
function calcMaxHP(pokeid: number, lvl: number): number { return Math.floor(2*(STATS[String(pokeid)]?.hp??45)*lvl/100)+lvl+10 }
function hpPct(slot: {id:number;lvl:number;hp:number}): number { return Math.max(0,Math.min(1,slot.hp/calcMaxHP(slot.id,slot.lvl))) }
function hpColor(pct: number): string { return pct>0.5?'#2ecc40':pct>0.25?'#ffdc00':'#ff4136' }
function evolveSlot(slot: any) { if(!slot.pendingEvo) return; const old=calcMaxHP(slot.id,slot.lvl); slot.id=slot.pendingEvo; slot.hp=Math.min(slot.hp+Math.max(0,calcMaxHP(slot.id,slot.lvl)-old),calcMaxHP(slot.id,slot.lvl)); slot.pendingEvo=undefined; saveStore.pokedex[String(slot.id)]='caught'; saveStore.save() }

function healAll() {
  const heal=(s:any)=>{ if(!s.id) return; s.hp=calcMaxHP(s.id,s.lvl); s.moves.forEach((m:any)=>{ const e=MOVES[String(m.id)]; if(e) m.pp=e.pp }) }
  saveStore.team.forEach(heal); saveStore.pc.forEach(heal); saveStore.save()
}

interface LearnEntry { id:number; level:number; tag?:'type'|'legacy' }
const moveEditor = reactive<{show:boolean;slot:any;source:'pc'|'team';sourceIndex:number;learnset:LearnEntry[];active:number[];view:'list'|'grid';nickname:string}>({show:false,slot:null,source:'team',sourceIndex:-1,learnset:[],active:[0,0,0,0],view:'grid',nickname:''})

function openMoveEditor(slot:any,source:'pc'|'team'='team',sourceIndex=-1) {
  moveEditor.slot=slot; moveEditor.source=source; moveEditor.sourceIndex=sourceIndex; moveEditor.nickname=slot.nickname??''
  const raw=LEARNSETS_DATA[String(slot.id)]??[]; const st=STATS[String(slot.id)]; const types=st?(st.type1!==st.type2?[st.type1,st.type2]:[st.type2]):[]
  const typeMoves:LearnEntry[]=Object.entries(MOVES).filter(([id,m])=>types.includes(m.type)&&!raw.find(e=>e.id===Number(id))).map(([id])=>({id:Number(id),level:0,tag:'type'as const}))
  const knownIds=new Set([...raw.map(e=>e.id),...typeMoves.map(e=>e.id)])
  const legacy:LearnEntry[]=slot.moves.filter((m:any)=>m.id>0&&MOVES[String(m.id)]&&!knownIds.has(m.id)).map((m:any)=>({id:m.id,level:0,tag:'legacy'as const}))
  const all:LearnEntry[]=[...raw,...typeMoves,...legacy]; const to=(t?:string)=>t==='type'?1:t==='legacy'?2:0
  moveEditor.learnset=[...all.filter(e=>e.level<=slot.lvl&&MOVES[String(e.id)]).sort((a,b)=>b.level-a.level||to(a.tag)-to(b.tag)),...all.filter(e=>e.level>slot.lvl&&MOVES[String(e.id)]).sort((a,b)=>a.level-b.level)]
  const init=[0,0,0,0]; slot.moves.slice(0,4).forEach((m:any,i:number)=>{init[i]=m.id}); moveEditor.active=init; moveEditor.show=true
}
function toggleMove(id:number,level:number) { if(!moveEditor.slot||level>moveEditor.slot.lvl) return; const i=moveEditor.active.indexOf(id); if(i!==-1) moveEditor.active.splice(i,1,0); else {const e=moveEditor.active.indexOf(0);if(e!==-1) moveEditor.active.splice(e,1,id)} }
function saveMoveEditor() { const s=moveEditor.slot; if(!s) return; s.moves=moveEditor.active.filter(id=>id>0).map(id=>({id,pp:s.moves.find((m:any)=>m.id===id)?.pp??MOVES[String(id)]?.pp??1})); const t=moveEditor.nickname.trim(); s.nickname=t||undefined; saveStore.save(); moveEditor.show=false }
function releaseFromModal() { const s=moveEditor.slot; if(!s) return; const n=s.nickname?.trim()||pokedex(s.id); moveEditor.show=false; setTimeout(()=>askRelease(moveEditor.source,moveEditor.sourceIndex,n),80) }

function onDragStart(area:'pc'|'team',idx:number) { dragSrc.value=`${area}-${idx}` }
function onDropToPC(ti:number) {
  dragOver.value=''; const src=dragSrc.value; if(!src) return; const[sa,si]=src.split('-'); const srcIdx=Number(si)
  if(sa==='team') { if(activeTeam.value.length<=1) return; const p={...saveStore.team[srcIdx]}; while(saveStore.pc.length<=ti) saveStore.pc.push({id:0,lvl:0,hp:0,xp:0,moves:[],slot:0}); const ex=saveStore.pc[ti]; if(ex?.id){saveStore.team[srcIdx]={...ex,slot:srcIdx+1};saveStore.pc[ti]={...p,slot:ti+1}}else{saveStore.pc[ti]={...p,slot:ti+1};saveStore.team[srcIdx]={id:0,lvl:0,hp:0,xp:0,moves:[],slot:srcIdx+1}} }
  else if(sa==='pc') { if(srcIdx===ti) return; while(saveStore.pc.length<=Math.max(srcIdx,ti)) saveStore.pc.push({id:0,lvl:0,hp:0,xp:0,moves:[],slot:0}); const a=saveStore.pc[srcIdx]?{...saveStore.pc[srcIdx]}:null,b=saveStore.pc[ti]?{...saveStore.pc[ti]}:null; saveStore.pc[ti]=a?{...a,slot:ti+1}:{id:0,lvl:0,hp:0,xp:0,moves:[],slot:ti+1}; saveStore.pc[srcIdx]=b?{...b,slot:srcIdx+1}:{id:0,lvl:0,hp:0,xp:0,moves:[],slot:srcIdx+1} }
  while(saveStore.pc.length&&!saveStore.pc[saveStore.pc.length-1]?.id) saveStore.pc.pop(); saveStore.save()
}
function onDropToTeam(ti:number) {
  dragOver.value=''; const src=dragSrc.value; if(!src) return; const[sa,si]=src.split('-'); const srcIdx=Number(si)
  if(sa==='team') { if(srcIdx===ti) return; const t={...saveStore.team[srcIdx]}; saveStore.team[srcIdx]={...saveStore.team[ti],slot:srcIdx+1}; saveStore.team[ti]={...t,slot:ti+1} }
  else if(sa==='pc') { const fp={...saveStore.pc[srcIdx]},ft={...saveStore.team[ti]}; if(activeTeam.value.length>=6&&ft.id){saveStore.team[ti]={...fp,slot:ti+1};saveStore.pc[srcIdx]={...ft,slot:srcIdx+1}}else{saveStore.team[ti]={...fp,slot:ti+1};saveStore.pc.splice(srcIdx,1);saveStore.pc.forEach((p,i)=>{p.slot=i+1})} }
  while(saveStore.pc.length&&!saveStore.pc[saveStore.pc.length-1]?.id) saveStore.pc.pop(); saveStore.save()
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323:wght@400&display=swap');
* { box-sizing: border-box; }

/* ── Pocket panel ── */
.pc-pocket {
  position: fixed;
  bottom: 90px;
  right: 16px;
  z-index: 480;
  width: min(720px, calc(100vw - 40px));
  max-height: 80vh;
  overflow-y: auto;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 14px 14px;
  border-radius: 6px;
  box-shadow: 0 16px 60px rgba(0,0,0,0.6), 0 0 0 2px rgba(255,255,255,0.08);
  animation: pop-in 0.2s cubic-bezier(.34,1.4,.64,1);
}
@keyframes pop-in { from { opacity:0; transform: scale(0.92) translateY(16px); } to { opacity:1; transform: scale(1) translateY(0); } }

.pc-header { font-family:'Press Start 2P',monospace; display:flex; align-items:center; gap:10px; margin-bottom:12px; padding:10px 14px; background:#c82020; border:3px solid #881010; border-radius:4px; box-shadow:0 4px 0 #660808; position:sticky; top:0; z-index:10; }
.pc-title  { font-size:13px; color:#fff; text-shadow:1px 1px 0 #660808; letter-spacing:2px; }
.pc-sub    { font-size:7px; color:#ffc8c8; letter-spacing:1px; }
.blink     { font-size:14px; color:#fff; animation:blink-cur 1s step-end infinite; }
@keyframes blink-cur { 0%,100%{opacity:1}50%{opacity:0} }
.pc-header-actions { margin-left:auto; display:flex; gap:6px; flex-shrink:0; }
.pc-close-btn { font-family:'Press Start 2P',monospace; font-size:10px; background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.35); color:#fff; width:28px; height:28px; border-radius:2px; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background 0.15s; }
.pc-close-btn:hover { background:rgba(255,255,255,0.3); }

.pc-layout { display:flex; gap:10px; align-items:flex-start; padding: 12px 12px 18px; }

.box-panel { flex:1; background:#f0f8ff; border:3px solid #2848a8; border-radius:4px; overflow:hidden; box-shadow:0 4px 0 #1a3080; }

/* ── Filter bar ── */
.filter-bar { padding: 6px 8px; background: #e0eef8; border-bottom: 2px solid #b0cce0; display: flex; flex-direction: column; gap: 5px; }
.pc-search { width: 100%; box-sizing: border-box; padding: 4px 7px; font-family: 'Press Start 2P', monospace; font-size: 7px; background: #fff; border: 2px solid #b0cce0; color: #1a2a3a; outline: none; }
.pc-search::placeholder { color: #8aabcc; }
.pc-search:focus { border-color: #c82020; }
.filter-types { display: flex; flex-wrap: wrap; gap: 3px; }
.ftype-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  padding: 2px 5px;
  border: 1.5px solid;
  border-radius: 3px;
  background: transparent;
  color: #1a1a3a;
  cursor: pointer;
  transition: background 0.1s, color 0.1s;
  white-space: nowrap;
}
.ftype-chip.active { color: #fff; }
.filter-shiny { display: flex; gap: 4px; }
.fshiny-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 5px;
  padding: 3px 6px;
  border: 1.5px solid #2848a8;
  border-radius: 3px;
  background: transparent;
  color: #2848a8;
  cursor: pointer;
  transition: background 0.1s, color 0.1s;
}
.fshiny-btn.active { background: #2848a8; color: #fff; }
.fshiny-btn:not(.active):hover { background: #dce8fc; }

.box-grid  { display:grid; grid-template-columns:repeat(5,1fr); gap:5px; padding:8px; background:#dceef8; }
.box-cell  { aspect-ratio:1; background:#fff; border:2px solid #b0cce0; border-radius:3px; display:flex; align-items:center; justify-content:center; position:relative; transition:border-color 0.15s; min-height:56px; }
.box-cell.drop-glow { border-color:#c82020; background:#fff4f4; }
.cell-empty { color:#b8d4e8; font-size:18px; font-family:monospace; }

.poke-card { width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:grab; border:2px solid transparent; border-radius:3px; padding:2px; position:relative; background:#fff; transition:transform 0.12s; user-select:none; overflow:hidden; }
.card-type-bar { position:absolute; top:0; left:0; right:0; height:3px; }
.poke-card:hover { transform:scale(1.1); z-index:2; }
.poke-card.card-dragging { opacity:0.3; transform:scale(0.88); }
.poke-card>img { width:34px; height:34px; image-rendering:pixelated; }
.type-badge { font-family:'Press Start 2P',monospace; font-size:4px; color:#fff; padding:1px 3px; border-radius:2px; }
.shiny-star { font-size:9px; }
.card-name { font-family:'VT323',monospace; font-size:11px; color:#181830; text-align:center; width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1; }
.card-lvl  { font-family:'VT323',monospace; font-size:9px; color:#4060a0; line-height:1; }
.card-hp-bar { width:80%; height:3px; background:#d0e4f0; border:1px solid #b0c8e0; border-radius:2px; margin-top:2px; overflow:hidden; }
.card-hp-fill { height:100%; border-radius:1px; }
.type-badges { display:flex; gap:1px; flex-wrap:wrap; }
.box-footer { font-family:'VT323',monospace; font-size:14px; color:#4060a0; padding:5px 10px; border-top:2px solid #b0cce0; text-align:right; background:#f0f8ff; }

.team-panel { width:190px; flex-shrink:0; background:#f0f8ff; border:3px solid #2848a8; border-radius:4px; overflow:hidden; box-shadow:0 4px 0 #1a3080; position:sticky; top:58px; align-self:flex-start; }
.team-header { font-family:'Press Start 2P',monospace; font-size:8px; color:#fff; padding:7px 8px; background:#2a9030; border-bottom:3px solid #1a6020; display:flex; align-items:center; justify-content:space-between; }
.heal-all-btn { font-family:'Press Start 2P',monospace; font-size:9px; background:#f0fff0; color:#1a6020; border:2px solid #1a6020; border-radius:2px; padding:4px 8px; cursor:pointer; box-shadow:0 2px 0 #0e3a10; }
.heal-all-btn:hover { background:#d8f0d8; }
.xpshare-row { display:flex; align-items:center; justify-content:space-between; padding:5px 8px; background:#e8f4fc; border-bottom:1px solid #b8d4e8; }
.xpshare-label { font-family:'Press Start 2P',monospace; font-size:6px; color:#2848a8; }
.xpshare-toggle { font-family:'Press Start 2P',monospace; font-size:6px; padding:4px 8px; border-radius:2px; border:2px solid #a0c0d8; background:#dceef8; color:#4060a0; cursor:pointer; }
.xpshare-toggle.active { background:#fff4d8; color:#8a4800; border-color:#c08020; }
.team-list { padding:6px; display:flex; flex-direction:column; gap:4px; }
.team-slot { background:#fff; border:2px solid #b8d4e8; border-left-width:4px; border-radius:3px; height:52px; display:flex; align-items:center; gap:6px; padding:4px 6px; cursor:grab; position:relative; overflow:hidden; transition:border-color 0.15s, background 0.1s; }
.team-slot:not(.slot-empty):hover { background:#f0f7ff; }
.team-slot.slot-dragging { opacity:0.3; }
.team-slot.slot-drop { border-color:#c82020 !important; }
.team-slot.slot-empty { cursor:default; opacity:0.4; border-left-color:#b8d4e8 !important; justify-content:center; }
.team-slot>img { width:38px; height:38px; image-rendering:pixelated; flex-shrink:0; }
.slot-info { flex:1; min-width:0; }
.slot-name { font-family:'VT323',monospace; font-size:14px; color:#181830; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1; }
.slot-hp-bar { flex:1; height:3px; background:#d0e4f0; border:1px solid #b0c8e0; border-radius:2px; overflow:hidden; }
.slot-hp-fill { height:100%; border-radius:1px; }
.slot-xp-bar { flex:1; height:3px; background:#d0e4f0; border:1px solid #b0c8e0; border-radius:2px; overflow:hidden; }
.slot-xp-fill { height:100%; border-radius:1px; background:#2848a8; }
.bar-row { display:flex; align-items:center; gap:4px; margin-top:2px; }
.bar-label { font-family:'VT323',monospace; font-size:9px; color:#4060a0; white-space:nowrap; flex-shrink:0; }
.slot-empty-label { font-family:'Press Start 2P',monospace; font-size:5px; color:#a0c0d8; }
.party-count { font-family:'VT323',monospace; font-size:14px; color:#4060a0; padding:4px 10px; border-top:2px solid #b8d4e8; text-align:center; background:#f0f8ff; }

.slot-actions { display:flex; flex-direction:column; align-items:center; gap:2px; flex-shrink:0; margin-left:auto; }
.slot-arrow { display:none; background:none; border:1px solid #b0b8c8; border-radius:2px; width:18px; height:16px; font-size:8px; cursor:pointer; align-items:center; justify-content:center; color:#4a6080; transition:background 0.1s; padding:0; }
.slot-arrow:hover:not(:disabled) { background:#dce8f0; color:#1a3050; }
.slot-arrow:disabled { opacity:0.25; cursor:default; }
@media (max-width: 600px) { .slot-arrow { display:flex; } }
.gear-btn { font-size:16px; background:none; border:none; padding:0; width:20px; height:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:transform 0.15s; }
.gear-btn i { background:linear-gradient(160deg,#7090b0,#2848a8); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.gear-btn:hover { transform:rotate(30deg); }
.gear-btn-card { position:absolute !important; top:1px; right:1px; width:14px !important; height:14px !important; font-size:10px !important; z-index:5; opacity:0.5; }
.poke-card:hover .gear-btn-card { opacity:1; }
.evolve-btn { font-family:'Press Start 2P',monospace; font-size:5px; background:#7020c0; color:#fff; border:2px solid #4a1090; border-radius:2px; padding:3px 4px; cursor:pointer; width:100%; letter-spacing:0.5px; }
.evolve-btn-card { width:auto; margin-top:2px; }

.pc-tip { font-family:'VT323',monospace; font-size:13px; color:#6080a8; text-align:center; margin-top:10px; display:flex; align-items:center; justify-content:center; gap:6px; }
.blink-slow { color:#2a9030; animation:blink-cur 2s step-end infinite; }

/* ── Nested modals ── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.55); display:flex; align-items:center; justify-content:center; z-index:1000; }
.modal-box { background:#f0f8ff; border:3px solid #2848a8; border-radius:4px; padding:20px 24px; max-width:340px; width:90%; box-shadow:0 6px 0 #1a3080; text-align:center; }
.modal-title { font-family:'Press Start 2P',monospace; font-size:9px; color:#c82020; margin-bottom:12px; }
.modal-body  { font-family:'VT323',monospace; font-size:17px; color:#181830; margin-bottom:16px; line-height:1.4; }
.modal-poke  { color:#c82020; font-weight:bold; }
.modal-actions { display:flex; gap:10px; justify-content:center; }
.modal-btn { font-family:'Press Start 2P',monospace; font-size:7px; padding:7px 12px; border-radius:2px; cursor:pointer; border:2px solid; transition:background 0.15s; }
.modal-cancel  { background:#dceef8; border-color:#a0c0d8; color:#4060a0; box-shadow:0 3px 0 #80a0c0; }
.modal-confirm { background:#fff0f0; border-color:#c82020; color:#c82020; box-shadow:0 3px 0 #881010; }
.modal-cancel:hover  { background:#c8e4f4; }
.modal-confirm:hover { background:#ffe0e0; }
.move-modal { max-width:440px; width:95%; text-align:left; padding:16px 16px 12px; max-height:85vh; display:flex; flex-direction:column; }
.move-modal .modal-title { color:#181830; text-shadow:none; font-size:9px; display:flex; align-items:baseline; gap:8px; margin-bottom:4px; }
.move-modal-sub { font-family:'VT323',monospace; font-size:14px; color:#6080a0; font-weight:normal; }
.move-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:4px; }
.move-modal-header .modal-title { flex:1; margin-bottom:0; }
.view-toggle { display:flex; gap:3px; flex-shrink:0; }
.view-btn { font-size:12px; width:22px; height:22px; background:#dceef8; border:2px solid #a0c0d8; border-radius:2px; color:#6080a0; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; }
.view-btn.active { color:#fff; border-color:#2848a8; background:#2848a8; }
.active-moves-row { display:grid; grid-template-columns:repeat(4,1fr); gap:5px; margin-bottom:10px; }
.active-move-slot { background:#fff; border:2px solid #b8d4e8; border-radius:2px; padding:5px 4px; min-height:36px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; text-align:center; }
.active-move-slot.filled { border-color:#2848a8; background:#eaf2ff; }
.am-name { font-family:'VT323',monospace; font-size:12px; color:#181830; line-height:1; }
.am-type-badge { font-family:'Press Start 2P',monospace; font-size:4px; color:#fff; padding:2px 3px; border-radius:2px; }
.am-empty { font-family:'VT323',monospace; font-size:18px; color:#b8d4e8; }
.move-list { flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:3px; margin-bottom:10px; padding-right:3px; scrollbar-width:thin; scrollbar-color:#a0c0d8 #e8f4fc; }
.move-row { display:flex; align-items:center; justify-content:space-between; padding:6px 8px; background:#fff; border:2px solid #c8dcea; border-radius:2px; cursor:pointer; position:relative; overflow:hidden; gap:6px; }
.move-row:hover:not(.move-locked):not(.move-disabled) { background:#e8f4fc; }
.move-row.move-active { background:#eaf2ff; border-color:#2848a8; box-shadow:inset 3px 0 0 #2848a8; }
.move-row.move-disabled,.move-row.move-locked { opacity:0.45; cursor:not-allowed; }
.move-row-left { display:flex; align-items:center; gap:6px; min-width:0; }
.move-row-right { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.move-check { font-family:'VT323',monospace; font-size:14px; color:#a0c0d8; width:12px; text-align:center; flex-shrink:0; }
.move-row.move-active .move-check { color:#2848a8; }
.move-name { font-family:'VT323',monospace; font-size:14px; color:#181830; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:120px; }
.move-type-badge { font-family:'Press Start 2P',monospace; font-size:4px; color:#fff; padding:2px 3px; border-radius:2px; flex-shrink:0; }
.move-lv-tag  { font-family:'Press Start 2P',monospace; font-size:4px; color:#4060a0; background:#e0eeff; border:1px solid #a0c0d8; border-radius:2px; padding:2px 3px; flex-shrink:0; }
.move-src-tag { font-family:'Press Start 2P',monospace; font-size:4px; color:#1a7030; background:#e0f8e8; border:1px solid #80c090; border-radius:2px; padding:2px 3px; flex-shrink:0; }
.move-pp { font-family:'VT323',monospace; font-size:12px; color:#4060a0; }
.move-locked-overlay { position:absolute; inset:0; background:rgba(240,248,255,0.85); display:flex; align-items:center; justify-content:center; font-family:'Press Start 2P',monospace; font-size:5px; color:#8090b0; pointer-events:none; }
.move-list-grid { display:grid !important; grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:4px; }
.move-box { position:relative; background:#fff; border:2px solid #c8dcea; border-radius:2px; padding:5px 4px; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:2px; text-align:center; overflow:hidden; min-height:58px; }
.move-box:hover:not(.move-locked):not(.move-disabled) { background:#e8f4fc; }
.move-box.move-active { background:#eaf2ff; border-color:#2848a8; }
.move-box.move-disabled,.move-box.move-locked { opacity:0.4; cursor:not-allowed; }
.move-box-check { position:absolute; top:2px; left:4px; font-family:'VT323',monospace; font-size:12px; color:#2848a8; }
.move-box-name { font-family:'VT323',monospace; font-size:12px; color:#181830; line-height:1.1; word-break:break-word; }
.move-box-type { font-family:'Press Start 2P',monospace; font-size:4px; color:#fff; padding:2px 4px; border-radius:2px; }
.nickname-row { display:flex; align-items:center; gap:8px; margin-bottom:10px; padding:6px 8px; background:#e8f4fc; border:2px solid #a0c0d8; border-radius:2px; }
.nickname-label { font-family:'Press Start 2P',monospace; font-size:6px; color:#2848a8; flex-shrink:0; }
.nickname-input { flex:1; background:#fff; border:2px solid #a0c0d8; border-radius:2px; color:#181830; font-family:'VT323',monospace; font-size:16px; padding:3px 7px; outline:none; min-width:0; }
.nickname-input:focus { border-color:#2848a8; }
.modal-actions-split { display:flex !important; justify-content:space-between !important; gap:6px; }
.modal-actions-right { display:flex; gap:8px; }
.modal-release { background:#fff0f0; border-color:#c82020; color:#c82020; font-family:'Press Start 2P',monospace; font-size:6px; padding:7px 10px; border-radius:2px; cursor:pointer; border-width:2px; border-style:solid; box-shadow:0 3px 0 #881010; }
.modal-release:hover { background:#ffe0e0; }
</style>
