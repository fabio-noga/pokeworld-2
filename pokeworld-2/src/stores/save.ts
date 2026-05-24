import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { encodeSave, decodeSave } from '../utils/saveCodec'

const SAVE_KEY = 'pkw_save'

export interface TeamSlot {
  id: number
  lvl: number
  hp: number
  xp: number
  moves: { id: number; pp: number }[]
  slot: number
  pendingEvo?: number
  nickname?: string
}

export interface SaveData {
  version: number
  player: {
    nome: string
    sprite: string
    dinheiro: number
    gym: number
    lvlMax: number
  }
  team: TeamSlot[]
  pc: TeamSlot[]
  pokedex: Record<string, 'seen' | 'caught'>
}

export const useSaveStore = defineStore('save', () => {
  const playerData = reactive({
    nome: '',
    sprite: 'FireRed',
    dinheiro: 200,
    gym: 0,
    lvlMax: 5,
  })

  const team = ref<TeamSlot[]>([])
  const pc = ref<TeamSlot[]>([])
  const pokedex = ref<Record<string, 'seen' | 'caught'>>({})
  const xpShare = ref(false)
  const xpMultiplier = ref(3)

  // Passed from game → battle via Pinia (replaces the classic POST form)
  const encounter = reactive({ number: 0, level: 0 })

  function initDefaultSave() {
    playerData.nome = 'Player'
    playerData.sprite = 'FireRed'
    playerData.dinheiro = 200
    playerData.gym = 0
    playerData.lvlMax = 100
    team.value = [
      { id: 1, lvl: 7, hp: calcMaxHP(45, 7), xp: 0, moves: [{ id: 81, pp: 35 }, { id: 96, pp: 25 }, { id: 91, pp: 25 }, { id: 61, pp: 25 }], slot: 1 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 2 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 3 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 4 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 5 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 6 },
    ]
    pokedex.value = { 1: 'caught' }
  }

  function load(): boolean {
    const raw = localStorage.getItem(SAVE_KEY)
    if (!raw) {
      initDefaultSave()
      return false
    }
    try {
      const data = decodeSave(raw) as SaveData
      Object.assign(playerData, data.player)
      team.value = data.team ?? []
      pc.value = data.pc ?? []
      pokedex.value = data.pokedex ?? {}
      xpShare.value = (data as any).xpShare ?? false
      xpMultiplier.value = (data as any).xpMultiplier ?? 3
      return true
    } catch {
      initDefaultSave()
      return false
    }
  }

  function save() {
    const data: any = {
      version: 1,
      player: { ...playerData },
      team: team.value,
      pc: pc.value,
      pokedex: { ...pokedex.value },
      xpShare: xpShare.value,
      xpMultiplier: xpMultiplier.value,
    }
    localStorage.setItem(SAVE_KEY, encodeSave(data))
  }

  // Gen 1 HP formula: floor(2 * baseHP * level / 100) + level + 10
  function calcMaxHP(baseHP: number, level: number): number {
    return Math.floor(2 * baseHP * level / 100) + level + 10
  }

  // Base HP stats for starters; level 7 computed HP: Bulbasaur=23, Charmander=22, Squirtle=23
  const STARTER_DATA: Record<number, { baseHP: number; moves: { id: number; pp: number }[] }> = {
    1: { baseHP: 45, moves: [{ id: 81, pp: 35 }, { id: 96, pp: 25 }, { id: 91, pp: 25 }, { id: 61, pp: 25 }] },
    4: { baseHP: 39, moves: [{ id: 66, pp: 35 }, { id: 96, pp: 25 }, { id: 26, pp: 25 }, { id: 30, pp: 15 }] },
    7: { baseHP: 44, moves: [{ id: 81, pp: 35 }, { id: 96, pp: 25 }, { id:  9, pp: 30 }, { id: 92, pp: 25 }] },
  }

  function initNewGame(nome: string, sprite: string, starterPoke: number) {
    const starter = STARTER_DATA[starterPoke] ?? STARTER_DATA[1]
    const starterHP = calcMaxHP(starter.baseHP, 7)
    playerData.nome = nome
    playerData.sprite = sprite
    playerData.dinheiro = 200
    playerData.gym = 0
    playerData.lvlMax = 100
    team.value = [
      { id: starterPoke, lvl: 7, hp: starterHP, xp: 0, moves: starter.moves, slot: 1 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 2 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 3 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 4 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 5 },
      { id: 0, lvl: 0, hp: 0, xp: 0, moves: [], slot: 6 },
    ]
    pokedex.value = { [starterPoke]: 'caught' }
    save()
  }

  function exportSave(): void {
    const raw = localStorage.getItem(SAVE_KEY) ?? ''
    const blob = new Blob([raw], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'pokeworld.pkwsave'
    a.click()
    URL.revokeObjectURL(url)
  }

  function importSave(raw: string): boolean {
    try {
      decodeSave(raw)
      localStorage.setItem(SAVE_KEY, raw)
      return load()
    } catch {
      return false
    }
  }

  function hasSave(): boolean {
    return !!localStorage.getItem(SAVE_KEY)
  }

  return {
    playerData,
    team,
    pc,
    pokedex,
    xpShare,
    xpMultiplier,
    encounter,
    load,
    save,
    initNewGame,
    exportSave,
    importSave,
    hasSave,
  }
})
