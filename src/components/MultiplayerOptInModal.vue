<template>
  <Teleport to="body">
    <div class="optin-backdrop">
      <div class="optin-box">
        <div class="optin-icon">🌐</div>
        <div class="optin-title">Play Online?</div>
        <div class="optin-desc">See other players moving around the world in real time.</div>
        <div class="optin-btns">
          <button class="optin-btn yes" @click="$emit('choose', true)">Yes!</button>
          <button class="optin-btn no"  @click="$emit('choose', false)">No thanks</button>
        </div>
        <div class="optin-note">You can change this anytime in Settings.</div>
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
  background: rgba(0,0,0,0.7);
  display: flex; align-items: center; justify-content: center;
}
.optin-box {
  background: #0e1a26;
  border: 2px solid #2a4268;
  border-radius: 4px;
  padding: 28px 24px 20px;
  width: 260px;
  text-align: center;
  font-family: 'Pokemon GB', monospace, sans-serif;
  color: #c8e0f0;
  display: flex; flex-direction: column; gap: 12px;
}
.optin-icon { font-size: 32px; }
.optin-title { font-size: 14px; font-weight: bold; }
.optin-desc { font-size: 9px; color: #7a9ab0; line-height: 1.6; }
.optin-btns { display: flex; gap: 10px; justify-content: center; margin-top: 4px; }
.optin-btn {
  flex: 1; padding: 10px 0; border: none; border-radius: 2px;
  font-family: inherit; font-size: 10px; font-weight: bold; cursor: pointer;
}
.optin-btn.yes { background: #1c5030; color: #44cc88; border: 1px solid #44cc88; }
.optin-btn.yes:hover { background: #245a38; }
.optin-btn.no  { background: #1a1a2a; color: #7a9ab0; border: 1px solid #2a4268; }
.optin-btn.no:hover { background: #222233; }
.optin-note { font-size: 8px; color: #3a5a70; }
</style>
