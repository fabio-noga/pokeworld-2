<template>
  <Teleport to="body">
    <div class="optin-backdrop">
      <div class="optin-dex">

        <!-- Pokédex header bar -->
        <div class="optin-bar">
          <img src="/textures/Nav/Pokedex/0.png" class="optin-bar-icon" alt="" />
          <span class="optin-bar-title">POKÉDEX</span>
          <div class="optin-led"></div>
        </div>

        <!-- Screen -->
        <div class="optin-screen">
          <div class="optin-screen-inner">
            <div class="optin-screen-label">NEW FEATURE DETECTED</div>
            <div class="optin-screen-title">PLAY ONLINE?</div>
            <div class="optin-screen-desc">
              See other Trainers<br>moving in real time.<br>
              <span class="optin-screen-sub">Can be changed in Settings.</span>
            </div>
          </div>
        </div>

        <!-- Buttons styled as Pokédex control panel -->
        <div class="optin-controls">
          <button class="optin-ctrl yes" @click="$emit('choose', true)">
            <span class="ctrl-dot"></span>YES
          </button>
          <button class="optin-ctrl no" @click="$emit('choose', false)">
            <span class="ctrl-dot ctrl-dot--gray"></span>NO
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
defineEmits<{ (e: 'choose', value: boolean): void }>()
</script>

<style scoped>
.optin-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.75);
  display: flex; align-items: center; justify-content: center;
}

/* ── Pokédex shell ── */
.optin-dex {
  width: 260px;
  background-color: #d0d0d0;
  background-image: radial-gradient(circle, #aaaaaa 1.5px, transparent 1.5px);
  background-size: 14px 14px;
  border-radius: 8px 8px 6px 6px;
  box-shadow: 0 16px 60px rgba(0,0,0,0.7), 0 0 0 2px rgba(255,255,255,0.08);
  overflow: hidden;
}

/* ── Header bar ── */
.optin-bar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px;
  background: #c82020;
  border-bottom: 3px solid #881010;
  box-shadow: 0 3px 0 #660808;
}
.optin-bar-icon {
  width: 20px; height: 20px;
  image-rendering: pixelated;
  filter: invert(1); opacity: 0.9;
}
.optin-bar-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 10px; color: #fff;
  letter-spacing: 2px;
  text-shadow: 1px 1px 0 #660808;
  flex: 1;
}
.optin-led {
  width: 8px; height: 8px; border-radius: 50%;
  background: #44ff88; box-shadow: 0 0 6px #44ff88;
  animation: led-pulse 1.4s ease-in-out infinite;
}
@keyframes led-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}

/* ── Screen ── */
.optin-screen {
  margin: 12px 14px 8px;
  background: #1a2a1a;
  border: 3px solid #484848;
  border-radius: 3px;
  box-shadow: inset 0 2px 8px rgba(0,0,0,0.6);
  overflow: hidden;
}
.optin-screen-inner {
  padding: 14px 12px;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 3px,
    rgba(0,0,0,0.08) 3px,
    rgba(0,0,0,0.08) 4px
  );
  display: flex; flex-direction: column; gap: 8px;
}
.optin-screen-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 6px; color: #44cc66; letter-spacing: 1px;
  opacity: 0.7;
}
.optin-screen-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 11px; color: #88ff88;
  text-shadow: 0 0 8px rgba(136,255,136,0.5);
  letter-spacing: 1px;
}
.optin-screen-desc {
  font-family: 'Press Start 2P', monospace;
  font-size: 7px; color: #66cc88; line-height: 1.8;
}
.optin-screen-sub {
  font-size: 6px; color: #448866; opacity: 0.8;
}

/* ── Control buttons ── */
.optin-controls {
  display: flex; gap: 8px;
  padding: 10px 14px 14px;
}
.optin-ctrl {
  flex: 1;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 9px 0;
  font-family: 'Press Start 2P', monospace;
  font-size: 8px; font-weight: bold;
  border-radius: 3px; cursor: pointer;
  border: none;
  transition: filter 0.12s;
}
.optin-ctrl:hover { filter: brightness(1.15); }
.optin-ctrl:active { filter: brightness(0.9); transform: translateY(1px); }

.optin-ctrl.yes {
  background: #c82020; color: #fff;
  box-shadow: 0 3px 0 #881010;
}
.optin-ctrl.no {
  background: #484848; color: #ccc;
  box-shadow: 0 3px 0 #2a2a2a;
}

.ctrl-dot {
  width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
  background: #ff8888; box-shadow: 0 0 4px rgba(255,136,136,0.6);
}
.ctrl-dot--gray {
  background: #888; box-shadow: none;
}
</style>
