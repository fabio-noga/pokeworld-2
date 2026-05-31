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

// Persisted across reconnects so host-migration can send updated position
let _myInfo: PlayerInfo | null = null
// Set when in a private room — used for self-healing reconnect without server
let _privateKey: string | null = null

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

// ── Server API helpers ────────────────────────────────────────────────────────

type Assignment = { action: 'join' | 'host'; room?: number } | null

function brokerBase(): string | null {
  const host = import.meta.env.VITE_PEER_HOST as string | undefined
  return host ? `https://${host}` : null
}

async function fetchRoomAssignment(): Promise<Assignment> {
  const base = brokerBase()
  if (!base) return null
  try {
    const res = await fetch(`${base}/room/assign`, { signal: AbortSignal.timeout(3000) })
    if (!res.ok) return null
    return await res.json() as Assignment
  } catch {
    return null
  }
}

async function reportHostGone(room: number): Promise<Assignment> {
  const base = brokerBase()
  if (!base) return null
  try {
    const res = await fetch(`${base}/room/host-gone`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ room }),
      signal: AbortSignal.timeout(3000),
    })
    if (!res.ok) return null
    return await res.json() as Assignment
  } catch {
    return null
  }
}

// ── Become host: probe broker for first free room number ──────────────────────

async function becomeHost(
  Peer: typeof import('peerjs').Peer,
  myInfo: PlayerInfo,
): Promise<void> {
  for (let n = 1; n <= MAX_ROOM; n++) {
    const result = await attemptRoom(Peer, n, myInfo, 3000)
    if (result === 'hosted') return
    if (result === 'joined') return  // room came alive during probe
  }
  statusMsg.value = 'Could not connect'
}

// ── Auto-connect: try known room first, server only on failure ────────────────

export async function autoConnect(myInfo: PlayerInfo): Promise<void> {
  if (isOnline.value) return
  _myInfo = myInfo
  _privateKey = null
  statusMsg.value = 'Connecting…'

  const { Peer } = await import('peerjs')

  // 1. Returning player: try last known room directly (no server ping)
  if (roomNum.value > 0) {
    const result = await joinAsGuest(Peer, roomNum.value, myInfo)
    if (result === 'joined') return
  }

  // 2. Direct attempt failed — ask server once what to do
  const assignment = await fetchRoomAssignment()

  if (assignment?.action === 'join' && assignment.room) {
    const result = await joinAsGuest(Peer, assignment.room, myInfo)
    if (result === 'joined') return
    // Race condition: server said join but room wasn't ready — fall through to host
  }

  // 3. No rooms or server down — become host
  await becomeHost(Peer, myInfo)
}

// ── Re-connect after host leaves (migration) ──────────────────────────────────

async function autoConnectFromRoom(prevRoom: number, myInfo: PlayerInfo): Promise<void> {
  if (isOnline.value) return
  _myInfo = myInfo
  statusMsg.value = 'Reconnecting…'

  const { Peer } = await import('peerjs')

  // 1. Try rejoining the same room directly — someone may have already re-hosted it
  const directResult = await joinAsGuest(Peer, prevRoom, myInfo)
  if (directResult === 'joined') return

  // 2. Direct rejoin failed — report to server, get authoritative assignment
  const assignment = await reportHostGone(prevRoom)

  if (assignment?.action === 'host' && assignment.room) {
    const result = await attemptRoom(Peer, assignment.room, myInfo, 5000)
    if (result === 'hosted' || result === 'joined') return
  } else if (assignment?.action === 'join' && assignment.room) {
    const result = await joinAsGuest(Peer, assignment.room, myInfo)
    if (result === 'joined') return
  }

  // 3. Fallback — become host of any free room
  await becomeHost(Peer, myInfo)
}

// ── Private room ──────────────────────────────────────────────────────────────

async function fetchPrivateRoomAssignment(key: string): Promise<{ action: string; peerId: string } | null> {
  const base = brokerBase()
  if (!base) return null
  try {
    const res = await fetch(`${base}/room/join-private`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ key: key.toUpperCase() }),
      signal: AbortSignal.timeout(3000),
    })
    if (!res.ok) return null
    return await res.json()
  } catch {
    return null
  }
}

// Host a private room using the key as the peer ID
function setupHostPrivate(peer: import('peerjs').Peer, myInfo: PlayerInfo, key: string) {
  const store = useMultiplayerStore()
  _peer = peer
  _privateKey = key
  isHost.value   = true
  isOnline.value = true
  roomNum.value  = 0
  roomCount.value = 1
  statusMsg.value = `Private ·${key}`

  peer.on('connection', (conn: DataConnection) => {
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
        id: uuid, name: helloMsg.name, sprite: helloMsg.sprite,
        tile: helloMsg.tile, dir: helloMsg.dir,
        followerId: helloMsg.followerId, shiny: helloMsg.shiny,
      }
      store.upsert(newPlayer)
      roomCount.value = _connections.size + 1
      const hostSelf: Omit<OtherPlayer, 'walking' | 'prevTile'> = {
        id: myInfo.uuid, name: myInfo.name, sprite: myInfo.sprite,
        tile: myInfo.tile, dir: myInfo.dir,
        followerId: myInfo.followerId, shiny: myInfo.shiny,
      }
      const existing = store.playersList
        .filter((p: OtherPlayer) => p.id !== uuid)
        .map(({ walking: _w, prevTile: _pt, ...rest }: OtherPlayer) => rest)
      send(conn, { type: 'welcome', players: [hostSelf, ...existing] } as WelcomeMsg)
      broadcastExcept(conn.peer, { type: 'joined', ...newPlayer } as JoinedMsg)
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
      if (uuid) { _uuidToPeer.delete(uuid); store.remove(uuid); broadcastAll({ type: 'left', id: uuid } as LeftMsg) }
      roomCount.value = _connections.size + 1
    })
    conn.on('error', () => {
      const uuid = _peerToUuid.get(conn.peer)
      _connections.delete(conn.peer)
      _peerToUuid.delete(conn.peer)
      if (uuid) { _uuidToPeer.delete(uuid) }
      roomCount.value = _connections.size + 1
    })
  })
  peer.on('error', () => { statusMsg.value = 'Room error' })
}

// Join an existing private room as guest
function joinPrivateAsGuest(
  Peer: typeof import('peerjs').Peer,
  key: string,
  myInfo: PlayerInfo,
): Promise<AttemptResult> {
  const store = useMultiplayerStore()
  const peerId = `pkw-k${key}`

  return new Promise((resolve) => {
    const peer = new Peer(peerOptions() as any)
    let settled = false
    function settle(r: AttemptResult) { if (settled) return; settled = true; resolve(r) }

    peer.on('open', () => {
      const conn = peer.connect(peerId, { reliable: true })
      _hostConn = conn
      conn.on('open', () => { send(conn, { type: 'hello', ...myInfo } as HelloMsg) })
      conn.on('data', (raw) => {
        const msg = JSON.parse(raw as string) as IncomingMsg
        if (msg.type === 'full') { peer.destroy(); _hostConn = null; settle('full'); return }
        if (msg.type === 'welcome') {
          _peer = peer
          _privateKey = key
          isHost.value   = false
          isOnline.value = true
          roomNum.value  = 0
          statusMsg.value = `Private ·${key}`
          store.clear()
          msg.players.forEach((p: Omit<OtherPlayer, 'walking' | 'prevTile'>) => store.upsert(p))
          roomCount.value = store.playersList.length + 1
          conn.removeAllListeners('data')
          conn.on('data', handleGuestData)
          settle('joined')
        }
      })
      conn.on('close', () => {
        if (!settled) { settle('error'); peer.destroy(); return }
        if (isOnline.value) {
          const savedInfo = _myInfo
          const savedKey  = _privateKey
          isOnline.value = false; isHost.value = false
          _peer = null; _hostConn = null; roomCount.value = 0
          statusMsg.value = 'Reconnecting…'
          showToast('Host left — reconnecting…')
          const jitter = Math.floor(Math.random() * 400) + 100
          setTimeout(async () => {
            store.clear()
            if (!savedInfo || !savedKey) return
            // Private rooms: reconnect directly to same key — no server ping needed
            const { Peer: P } = await import('peerjs')
            const r = await joinPrivateAsGuest(P, savedKey, savedInfo)
            if (r !== 'joined') {
              // Become new host of same private key
              const p = new P(`pkw-k${savedKey}`, peerOptions())
              p.on('open', () => setupHostPrivate(p, savedInfo, savedKey))
              p.on('error', () => { statusMsg.value = 'Could not reconnect' })
            }
          }, jitter)
        }
      })
      conn.on('error', () => { if (!settled) settle('error') })
    })
    peer.on('error', () => settle('error'))
  })
}

// ── Public: join a private room by 5-char alphanumeric key ───────────────────

export async function joinPrivateRoom(key: string, myInfo: PlayerInfo): Promise<void> {
  key = key.toUpperCase()
  if (isOnline.value) disconnect()
  _myInfo = myInfo
  statusMsg.value = 'Connecting…'

  const { Peer } = await import('peerjs')

  // Ask server: is this key hosted already?
  const assignment = await fetchPrivateRoomAssignment(key)

  if (!assignment || assignment.action === 'host') {
    // Become host of pkw-k{key}
    const peer = new Peer(`pkw-k${key}`, peerOptions())
    peer.on('open', () => setupHostPrivate(peer, myInfo, key))
    peer.on('error', (err: { type: string }) => {
      if (err.type === 'unavailable-id') {
        // Race: someone else just became host — join instead
        peer.destroy()
        joinPrivateAsGuest(Peer, key, myInfo)
      } else {
        statusMsg.value = 'Could not connect'
      }
    })
    return
  }

  if (assignment.action === 'full') {
    statusMsg.value = 'Private room is full'
    return
  }

  // Join as guest
  const result = await joinPrivateAsGuest(Peer, key, myInfo)
  if (result !== 'joined') {
    // Room vanished between server query and join — try to become host
    const peer = new Peer(`pkw-k${key}`, peerOptions())
    peer.on('open', () => setupHostPrivate(peer, myInfo, key))
    peer.on('error', () => { statusMsg.value = 'Could not connect' })
  }
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
  _privateKey     = null
}

// ── Composable (thin wrapper) ─────────────────────────────────────────────────

export function useMultiplayer() {
  return { isOnline, isHost, roomNum, roomCount, toastMsg, statusMsg, autoConnect, broadcastMove, broadcastFollower, disconnect }
}
