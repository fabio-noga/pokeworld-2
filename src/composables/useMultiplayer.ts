import { ref } from 'vue'
import type { DataConnection } from 'peerjs'
import { useMultiplayerStore, type Direction, type OtherPlayer } from '../stores/multiplayer'

// ── Types ────────────────────────────────────────────────────────────────────

export interface PlayerInfo {
  uuid: string
  name: string
  sprite: string
  tile: number
  dir: Direction
  followerId: number
  shiny: boolean
}

interface HelloMsg          { type: 'hello';    uuid: string; name: string; sprite: string; tile: number; dir: Direction; followerId: number; shiny: boolean }
interface WelcomeMsg        { type: 'welcome';  players: Omit<OtherPlayer, 'walking' | 'prevTile'>[] }
interface JoinedMsg         { type: 'joined';   id: string; name: string; sprite: string; tile: number; dir: Direction; followerId: number; shiny: boolean }
interface MoveOutMsg        { type: 'move';     tile: number; dir: Direction }
interface MoveRelMsg        { type: 'move';     id: string; tile: number; dir: Direction }
interface FollowerOutMsg    { type: 'follower'; followerId: number; shiny: boolean }
interface FollowerRelMsg    { type: 'follower'; id: string; followerId: number; shiny: boolean }
interface LeftMsg           { type: 'left';     id: string }
interface FullMsg           { type: 'full' }

type IncomingMsg = WelcomeMsg | JoinedMsg | MoveRelMsg | FollowerRelMsg | LeftMsg | FullMsg

// ── Singleton peer state (persists across component re-mounts) ────────────────

let _peer: import('peerjs').Peer | null = null
let _hostConn: DataConnection | null = null
const _connections = new Map<string, DataConnection>()  // peerID → conn  (host side)
const _peerToUuid  = new Map<string, string>()          // peerID → uuid   (host side)
const _uuidToPeer  = new Map<string, string>()          // uuid  → peerID  (host side)

const ROOM_PREFIX      = 'pkw-r'
const MAX_ROOM         = 20
const MAX_PLAYERS      = 15
const WALK_MS          = 280
const CONNECT_TIMEOUTS = [1000, 3000, 5000, 10000]

// Persisted across reconnects so host-migration can send updated position
let _myInfo: PlayerInfo | null = null

// ── Shared reactive state ────────────────────────────────────────────────────

export const isOnline  = ref(false)
export const isHost    = ref(false)
export const roomNum   = ref(0)
export const roomCount = ref(0)
export const toastMsg  = ref('')
export const statusMsg = ref('Offline')

function showToast(msg: string, ms = 3500) {
  toastMsg.value = msg
  setTimeout(() => { toastMsg.value = '' }, ms)
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function send(conn: DataConnection, data: object) {
  if (conn.open) conn.send(JSON.stringify(data))
}

function broadcastAll(data: object) {
  _connections.forEach(c => send(c, data))
}

function broadcastExcept(exceptPeerId: string, data: object) {
  _connections.forEach((c, id) => { if (id !== exceptPeerId) send(c, data) })
}

function roomId(n: number) { return `${ROOM_PREFIX}${n}` }

// ── Walking animation ─────────────────────────────────────────────────────────

const _walkTimers = new Map<string, ReturnType<typeof setTimeout>>()

function triggerWalkWithPos(id: string, tile: number, dir: Direction) {
  const store = useMultiplayerStore()
  clearTimeout(_walkTimers.get(id))
  store.updateMove(id, tile, dir)
  const t = setTimeout(() => { store.setWalking(id, false) }, WALK_MS)
  _walkTimers.set(id, t)
}

// ── Host setup ────────────────────────────────────────────────────────────────

function setupHost(peer: import('peerjs').Peer, myInfo: PlayerInfo, n: number) {
  const store = useMultiplayerStore()
  _peer = peer
  isHost.value   = true
  isOnline.value = true
  roomNum.value  = n
  roomCount.value = 1
  statusMsg.value = `Room ${n}`

  peer.on('connection', (conn: DataConnection) => {
    // Reject if room full
    if (_connections.size >= MAX_PLAYERS - 1) {
      conn.on('open', () => { send(conn, { type: 'full' }); setTimeout(() => conn.close(), 200) })
      return
    }

    conn.on('open', () => { _connections.set(conn.peer, conn) })

    conn.on('data', (raw) => {
      const msg = JSON.parse(raw as string)
      if (msg.type !== 'hello') return

      const helloMsg = msg as HelloMsg
      const uuid = helloMsg.uuid

      // Deduplicate: if this UUID was already connected under a different peer ID, drop old entry
      const oldPeerId = _uuidToPeer.get(uuid)
      if (oldPeerId && oldPeerId !== conn.peer) {
        _peerToUuid.delete(oldPeerId)
        const oldConn = _connections.get(oldPeerId)
        if (oldConn) { try { oldConn.close() } catch { /* ignore */ } }
        _connections.delete(oldPeerId)
      }
      _peerToUuid.set(conn.peer, uuid)
      _uuidToPeer.set(uuid, conn.peer)

      const newPlayer: Omit<OtherPlayer, 'walking' | 'prevTile'> = {
        id:         uuid,   // ← store key is UUID
        name:       helloMsg.name,
        sprite:     helloMsg.sprite,
        tile:       helloMsg.tile,
        dir:        helloMsg.dir,
        followerId: helloMsg.followerId,
        shiny:      helloMsg.shiny,
      }
      store.upsert(newPlayer)
      roomCount.value = _connections.size + 1

      // Build welcome list: host self (using latest _myInfo) + all existing guests except the joiner
      const hostSelf: Omit<OtherPlayer, 'walking' | 'prevTile'> = {
        id:         myInfo.uuid,
        name:       myInfo.name,
        sprite:     myInfo.sprite,
        tile:       myInfo.tile,
        dir:        myInfo.dir,
        followerId: myInfo.followerId,
        shiny:      myInfo.shiny,
      }
      const existing = store.playersList
        .filter((p: OtherPlayer) => p.id !== uuid)
        .map(({ walking: _w, prevTile: _pt, ...rest }: OtherPlayer) => rest)

      send(conn, { type: 'welcome', players: [hostSelf, ...existing] } as WelcomeMsg)
      broadcastExcept(conn.peer, { type: 'joined', ...newPlayer } as JoinedMsg)

      // Switch to ongoing data handler (move + follower changes)
      conn.removeAllListeners('data')
      conn.on('data', (raw2) => {
        const m = JSON.parse(raw2 as string) as MoveOutMsg | FollowerOutMsg
        const senderUuid = _peerToUuid.get(conn.peer)
        if (!senderUuid) return

        if (m.type === 'move') {
          triggerWalkWithPos(senderUuid, m.tile, m.dir)
          broadcastExcept(conn.peer, { type: 'move', id: senderUuid, tile: m.tile, dir: m.dir } as MoveRelMsg)
        } else if (m.type === 'follower') {
          store.updateFollower(senderUuid, m.followerId, m.shiny)
          broadcastExcept(conn.peer, { type: 'follower', id: senderUuid, followerId: m.followerId, shiny: m.shiny } as FollowerRelMsg)
        }
      })
    })

    conn.on('close', () => {
      const uuid = _peerToUuid.get(conn.peer)
      _connections.delete(conn.peer)
      _peerToUuid.delete(conn.peer)
      if (uuid) {
        _uuidToPeer.delete(uuid)
        store.remove(uuid)
        broadcastAll({ type: 'left', id: uuid } as LeftMsg)
      }
      roomCount.value = _connections.size + 1
    })

    conn.on('error', () => {
      const uuid = _peerToUuid.get(conn.peer)
      _connections.delete(conn.peer)
      _peerToUuid.delete(conn.peer)
      if (uuid) { _uuidToPeer.delete(uuid); store.remove(uuid) }
      roomCount.value = _connections.size + 1
    })
  })

  peer.on('error', () => {
    statusMsg.value = 'Room error'
  })
}

// ── Guest data handler ────────────────────────────────────────────────────────

function handleGuestData(raw: unknown) {
  const store = useMultiplayerStore()
  const msg = JSON.parse(raw as string) as IncomingMsg

  if (msg.type === 'joined') {
    const { type: _, ...player } = msg
    store.upsert(player)          // player.id = UUID
    roomCount.value++
  }

  if (msg.type === 'move') {
    triggerWalkWithPos(msg.id, msg.tile, msg.dir)
    roomCount.value = store.playersList.length + 1
  }

  if (msg.type === 'follower') {
    store.updateFollower(msg.id, msg.followerId, msg.shiny)
  }

  if (msg.type === 'left') {
    store.remove(msg.id)          // msg.id = UUID
    roomCount.value = Math.max(1, roomCount.value - 1)
  }
}

// ── Auto-connect: scan rooms 1 → MAX_ROOM ────────────────────────────────────

export async function autoConnect(myInfo: PlayerInfo): Promise<void> {
  if (isOnline.value) return
  _myInfo = myInfo

  const { Peer } = await import('peerjs')
  for (let i = 0; i < CONNECT_TIMEOUTS.length; i++) {
    const n = (i % MAX_ROOM) + 1
    statusMsg.value = i === 0 ? 'Connecting…' : `Connecting… (${i + 1}/${CONNECT_TIMEOUTS.length})`
    const result = await attemptRoom(Peer, n, myInfo, CONNECT_TIMEOUTS[i])
    if (result === 'hosted' || result === 'joined') return
  }
  statusMsg.value = 'Could not connect'
}

// ── Re-connect starting from a preferred room (for host migration) ────────────

async function autoConnectFromRoom(preferredRoom: number, myInfo: PlayerInfo): Promise<void> {
  if (isOnline.value) return
  _myInfo = myInfo

  const { Peer } = await import('peerjs')
  for (let i = 0; i < CONNECT_TIMEOUTS.length; i++) {
    const n = ((preferredRoom - 1 + i) % MAX_ROOM) + 1
    const result = await attemptRoom(Peer, n, myInfo, CONNECT_TIMEOUTS[i])
    if (result === 'hosted' || result === 'joined') return
  }
  statusMsg.value = 'Could not connect'
}

// ── Room attempt ──────────────────────────────────────────────────────────────

type AttemptResult = 'hosted' | 'joined' | 'full' | 'error'

// Build PeerJS constructor options — uses VITE_PEER_HOST in production, default broker in dev
function peerOptions(): object {
  const host = import.meta.env.VITE_PEER_HOST as string | undefined
  if (!host) return {}
  return { host, port: 443, secure: true, path: '/' }
}

function attemptRoom(
  Peer: typeof import('peerjs').Peer,
  n: number,
  myInfo: PlayerInfo,
  timeoutMs?: number,
): Promise<AttemptResult> {
  return new Promise((resolve) => {
    let settled = false
    function settle(r: AttemptResult) {
      if (settled) return
      settled = true
      resolve(r)
    }

    const peer = new Peer(roomId(n), peerOptions())

    const timer = timeoutMs
      ? setTimeout(() => { peer.destroy(); settle('error') }, timeoutMs)
      : null

    peer.on('open', () => {
      if (timer) clearTimeout(timer)
      setupHost(peer, myInfo, n)
      settle('hosted')
    })

    peer.on('error', (err: { type: string }) => {
      if (timer) clearTimeout(timer)
      peer.destroy()
      if (err.type === 'unavailable-id') {
        // Room already exists — join as guest
        joinAsGuest(Peer, n, myInfo).then(r => settle(r))
      } else {
        settle('error')
      }
    })
  })
}

// ── Join as guest ─────────────────────────────────────────────────────────────

function joinAsGuest(
  Peer: typeof import('peerjs').Peer,
  n: number,
  myInfo: PlayerInfo,
): Promise<AttemptResult> {
  const store = useMultiplayerStore()

  return new Promise((resolve) => {
    const peer = new Peer(peerOptions() as any)   // random peer ID for ourselves
    let settled = false

    function settle(result: AttemptResult) {
      if (settled) return
      settled = true
      resolve(result)
    }

    peer.on('open', () => {
      const conn = peer.connect(roomId(n), { reliable: true })
      _hostConn = conn

      conn.on('open', () => {
        send(conn, { type: 'hello', ...myInfo } as HelloMsg)
      })

      // Initial handler: waiting for welcome or full
      conn.on('data', (raw) => {
        const msg = JSON.parse(raw as string) as IncomingMsg

        if (msg.type === 'full') {
          peer.destroy()
          _hostConn = null
          settle('full')
          return
        }

        if (msg.type === 'welcome') {
          _peer = peer
          isHost.value   = false
          isOnline.value = true
          roomNum.value  = n
          statusMsg.value = `Room ${n}`

          store.clear()
          msg.players.forEach((p: Omit<OtherPlayer, 'walking' | 'prevTile'>) => store.upsert(p))
          roomCount.value = store.playersList.length + 1  // +1 for self

          conn.removeAllListeners('data')
          conn.on('data', handleGuestData)

          settle('joined')
        }
      })

      conn.on('close', () => {
        if (!settled) { settle('error'); peer.destroy(); return }

        // Host disconnected after we were in the room → attempt host migration
        if (isOnline.value) {
          const prevRoom = roomNum.value
          const savedInfo = _myInfo

          isOnline.value  = false
          isHost.value    = false
          _peer           = null
          _hostConn       = null
          roomCount.value = 0
          statusMsg.value = 'Reconnecting…'
          showToast('Host left — reconnecting…')

          // Random jitter 100–500 ms so all survivors don't race to host simultaneously
          const jitter = Math.floor(Math.random() * 400) + 100
          setTimeout(() => {
            store.clear()
            if (savedInfo) autoConnectFromRoom(prevRoom, savedInfo)
          }, jitter)
        }
      })

      conn.on('error', () => {
        if (!settled) settle('error')
      })
    })

    peer.on('error', () => settle('error'))
  })
}

// ── Broadcast follower change ─────────────────────────────────────────────────

export function broadcastFollower(followerId: number, shiny: boolean) {
  if (_myInfo) { _myInfo.followerId = followerId; _myInfo.shiny = shiny }
  if (!isOnline.value) return

  if (isHost.value && _myInfo) {
    broadcastAll({ type: 'follower', id: _myInfo.uuid, followerId, shiny } as FollowerRelMsg)
  } else if (_hostConn?.open) {
    send(_hostConn, { type: 'follower', followerId, shiny } as FollowerOutMsg)
  }
}

// ── Broadcast own movement ────────────────────────────────────────────────────

export function broadcastMove(tile: number, dir: Direction) {
  // Keep _myInfo position up to date for welcome messages sent to late joiners
  if (_myInfo) { _myInfo.tile = tile; _myInfo.dir = dir }

  if (!isOnline.value) return

  if (isHost.value) {
    // Relay host's own move to all guests
    if (_myInfo) {
      broadcastAll({ type: 'move', id: _myInfo.uuid, tile, dir } as MoveRelMsg)
    }
  } else if (_hostConn?.open) {
    send(_hostConn, { type: 'move', tile, dir } as MoveOutMsg)
  }
}

// ── Disconnect ────────────────────────────────────────────────────────────────

export function disconnect() {
  const store = useMultiplayerStore()
  _walkTimers.forEach(t => clearTimeout(t))
  _walkTimers.clear()
  if (_peer) { _peer.destroy(); _peer = null }
  _hostConn = null
  _connections.clear()
  _peerToUuid.clear()
  _uuidToPeer.clear()
  store.clear()
  isOnline.value  = false
  isHost.value    = false
  roomNum.value   = 0
  roomCount.value = 0
  statusMsg.value = 'Offline'
}

// ── Composable (thin wrapper) ─────────────────────────────────────────────────

export function useMultiplayer() {
  return { isOnline, isHost, roomNum, roomCount, toastMsg, statusMsg, autoConnect, broadcastMove, broadcastFollower, disconnect }
}
