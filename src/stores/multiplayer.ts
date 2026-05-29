import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type Direction = 'up' | 'down' | 'left' | 'right'

export interface OtherPlayer {
  id: string
  name: string
  sprite: string
  tile: number
  prevTile: number | null   // tile before the last move (for direction derivation)
  dir: Direction
  walking: boolean
  followerId: number
  shiny: boolean
}

const MAP_WIDTH = 24

/** Derive facing direction from the tile position delta. */
export function dirFromDelta(prevTile: number, newTile: number): Direction | null {
  const d = newTile - prevTile
  if (d === -MAP_WIDTH || d === -(MAP_WIDTH * 2)) return 'up'
  if (d ===  MAP_WIDTH || d ===   MAP_WIDTH * 2)  return 'down'
  if (d === -1) return 'left'
  if (d ===  1) return 'right'
  return null
}

export const useMultiplayerStore = defineStore('multiplayer', () => {
  const players = ref<Map<string, OtherPlayer>>(new Map())
  const playersList = computed<OtherPlayer[]>(() => Array.from(players.value.values()))

  function upsert(player: Omit<OtherPlayer, 'walking' | 'prevTile'>) {
    players.value.set(player.id, { ...player, walking: false, prevTile: null })
  }

  function updateMove(id: string, newTile: number, hintDir: Direction) {
    const p = players.value.get(id)
    if (!p) return
    // Derive direction from geometry; fall back to hint if tile didn't change
    const derived = p.tile !== newTile ? dirFromDelta(p.tile, newTile) : null
    const dir = derived ?? hintDir
    players.value.set(id, { ...p, prevTile: p.tile, tile: newTile, dir, walking: true })
  }

  function setWalking(id: string, val: boolean) {
    const p = players.value.get(id)
    if (p) players.value.set(id, { ...p, walking: val })
  }

  function updateFollower(id: string, followerId: number, shiny: boolean) {
    const p = players.value.get(id)
    if (p) players.value.set(id, { ...p, followerId, shiny })
  }

  function remove(id: string) {
    players.value.delete(id)
  }

  function clear() {
    players.value.clear()
  }

  return { players, playersList, upsert, updateMove, setWalking, updateFollower, remove, clear }
})
