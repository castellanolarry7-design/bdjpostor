<!-- src/components/pos/BarcodeScanner.vue -->
<template>
  <div class="relative overflow-hidden rounded-2xl" style="background:#000">

    <!-- Video feed -->
    <video
      ref="videoEl"
      class="w-full object-cover"
      style="max-height: 240px; display: block"
      muted
      playsinline
    />

    <!-- Overlay con marco de escaneo -->
    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
      <!-- Marco esquinado -->
      <div class="relative w-52 h-32">
        <span class="corner tl" /><span class="corner tr" />
        <span class="corner bl" /><span class="corner br" />
        <!-- Línea de escaneo animada -->
        <div class="scan-line" />
      </div>
      <p class="text-xs mt-3 font-medium" style="color: rgba(255,255,255,0.7)">
        {{ status }}
      </p>
    </div>

    <!-- Botón flash -->
    <button
      v-if="hasFlash"
      @click="toggleFlash"
      class="absolute top-3 right-3 w-9 h-9 rounded-full flex items-center justify-center transition-colors"
      :style="flashOn ? 'background: rgba(255,220,0,0.25); color: #fde047' : 'background: rgba(0,0,0,0.4); color: white'"
    >
      <BoltIcon class="w-5 h-5" />
    </button>

    <!-- Error / sin cámara -->
    <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center"
         style="background: rgba(0,0,0,0.85)">
      <ExclamationTriangleIcon class="w-10 h-10 text-amber-400" />
      <p class="text-sm text-white">{{ error }}</p>
      <button @click="start" class="btn-primary text-xs !py-1.5 !px-4">Reintentar</button>
    </div>

    <!-- Entrada manual como fallback -->
    <div class="absolute bottom-3 left-3 right-3">
      <div v-if="showManual" class="flex gap-2">
        <input
          v-model="manualCode"
          class="input text-xs py-1.5 flex-1"
          placeholder="Ingresar código manualmente..."
          @keyup.enter="submitManual"
          autofocus
        />
        <button @click="submitManual" class="btn-primary text-xs !py-1.5 !px-3">OK</button>
      </div>
      <button
        v-else
        @click="showManual = true"
        class="text-xs underline"
        style="color: rgba(255,255,255,0.5)"
      >
        Ingresar código manualmente
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { BoltIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['scanned', 'error'])

const videoEl    = ref(null)
const error      = ref('')
const hasFlash   = ref(false)
const flashOn    = ref(false)
const showManual = ref(false)
const manualCode = ref('')
const status     = ref('Apuntando al código de barras o QR...')

let controls  = null
let lastCode  = ''
let lastTime  = 0
const COOLDOWN = 2000 // ms entre escaneos del mismo código

async function start() {
  error.value = ''
  try {
    const { BrowserMultiFormatReader } = await import('@zxing/browser')
    const reader = new BrowserMultiFormatReader()

    controls = await reader.decodeFromConstraints(
      { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } },
      videoEl.value,
      (result, err) => {
        if (result) {
          const code = result.getText()
          const now  = Date.now()
          if (code === lastCode && now - lastTime < COOLDOWN) return
          lastCode = code
          lastTime = now
          beep()
          emit('scanned', code)
        }
      }
    )

    // Verificar soporte de flash (torch)
    const stream = videoEl.value?.srcObject
    if (stream) {
      const track = stream.getVideoTracks()[0]
      const caps  = track?.getCapabilities?.()
      hasFlash.value = !!caps?.torch
    }

  } catch (e) {
    const msg = e?.message || ''
    if (msg.includes('Permission') || msg.includes('NotAllowed')) {
      error.value = 'Permiso de cámara denegado. Habilita el acceso en la configuración del navegador.'
    } else if (msg.includes('NotFound') || msg.includes('Devices')) {
      error.value = 'No se encontró cámara. Usa la entrada manual.'
      showManual.value = true
    } else {
      error.value = 'No se pudo iniciar la cámara.'
      showManual.value = true
    }
    emit('error', e)
  }
}

async function toggleFlash() {
  const stream = videoEl.value?.srcObject
  if (!stream) return
  const track = stream.getVideoTracks()[0]
  flashOn.value = !flashOn.value
  await track.applyConstraints({ advanced: [{ torch: flashOn.value }] }).catch(() => {})
}

function submitManual() {
  const code = manualCode.value.trim()
  if (!code) return
  emit('scanned', code)
  manualCode.value = ''
  showManual.value = false
}

// Beep corto al escanear
function beep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.connect(gain); gain.connect(ctx.destination)
    osc.frequency.value = 1200
    gain.gain.setValueAtTime(0.1, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1)
    osc.start(ctx.currentTime)
    osc.stop(ctx.currentTime + 0.1)
  } catch (_) {}
}

onMounted(start)
onUnmounted(() => { controls?.stop?.() })
</script>

<style scoped>
.corner {
  position: absolute;
  width: 18px;
  height: 18px;
  border-color: #3b82f6;
  border-style: solid;
}
.tl { top: 0; left: 0;  border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
.tr { top: 0; right: 0; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
.bl { bottom: 0; left: 0;  border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }
.br { bottom: 0; right: 0; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }

.scan-line {
  position: absolute;
  left: 2px; right: 2px;
  height: 2px;
  background: linear-gradient(90deg, transparent, #3b82f6, transparent);
  animation: scan 1.8s ease-in-out infinite;
  box-shadow: 0 0 8px #3b82f6;
}
@keyframes scan {
  0%   { top: 4px; opacity: 1 }
  50%  { top: calc(100% - 4px); opacity: 1 }
  100% { top: 4px; opacity: 1 }
}
</style>
