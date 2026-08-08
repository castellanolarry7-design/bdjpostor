<!-- src/views/auth/LoginView.vue -->
<template>
  <div class="login-root flex flex-col lg:flex-row">

    <!-- IZQUIERDA: logo / marca — 60% -->
    <div class="hidden lg:flex relative overflow-hidden flex-col items-center justify-between py-14
                lg:w-[60%] lg:min-w-[60%] lg:shrink-0"
         style="background:#000;">

      <div class="grid-bg absolute inset-0" />
      <div class="scan-line absolute inset-x-0 pointer-events-none" />
      <div class="absolute -top-32 -right-32 w-[560px] h-[560px] rounded-full pointer-events-none"
           style="background:radial-gradient(circle,rgba(59,130,246,0.14) 0%,transparent 60%);" />
      <div class="absolute -bottom-32 -left-32 w-[460px] h-[460px] rounded-full pointer-events-none"
           style="background:radial-gradient(circle,rgba(59,130,246,0.09) 0%,transparent 60%);" />
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div v-for="i in 8" :key="i" class="particle absolute rounded-full" :class="`particle-${i}`"
             style="background:rgba(59,130,246,0.55)" />
      </div>

      <div class="relative z-10 flex items-center gap-3 self-start px-14 w-full">
        <div class="w-px h-5" style="background:rgba(59,130,246,0.7)" />
        <span class="text-[11px] font-bold uppercase tracking-[0.22em]" style="color:rgba(59,130,246,0.75)">Sistema de gestión</span>
      </div>

      <div class="relative z-10 flex flex-col items-center text-center px-14 gap-7 flex-1 justify-center">
        <div class="logo-wrap relative">
          <!-- Marco geométrico cuadrado en lugar de círculos -->
          <div class="logo-frame">
            <span class="lf tl" /><span class="lf tr" /><span class="lf bl" /><span class="lf br" />
            <div class="lf-scan" />
          </div>
          <img src="/logo-brand.png" alt="28 STORE" class="brand-logo select-none relative z-10"
               style="width:300px;height:300px;object-fit:contain;display:block;" />
          <div class="logo-halo" />
        </div>

        <div class="flex items-center gap-4 w-72">
          <div class="flex-1 h-px" style="background:linear-gradient(to right,transparent,rgba(59,130,246,0.5))" />
          <div class="flex gap-1">
            <span class="w-1 h-1" style="background:#3b82f6;clip-path:polygon(50% 0%,100% 100%,0% 100%)" />
            <span class="w-1 h-1" style="background:#60a5fa;clip-path:polygon(50% 0%,100% 100%,0% 100%)" />
            <span class="w-1 h-1" style="background:#93c5fd;clip-path:polygon(50% 0%,100% 100%,0% 100%)" />
          </div>
          <div class="flex-1 h-px" style="background:linear-gradient(to left,transparent,rgba(59,130,246,0.5))" />
        </div>

        <div class="space-y-3">
          <h2 class="text-3xl font-bold text-white leading-tight tracking-tight">
            Gestiona tu negocio<br>
            <span style="background:linear-gradient(90deg,#3b82f6,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
              con total control.
            </span>
          </h2>
          <p class="text-sm" style="color:rgba(255,255,255,0.32);max-width:380px;margin:0 auto">
            Inventario, ventas y reportes en un solo lugar.
          </p>
        </div>

        <div class="flex items-center gap-8 pt-1">
          <div class="text-center">
            <p class="text-2xl font-bold text-white">100%</p>
            <p class="text-[10px] uppercase tracking-[0.15em] mt-1" style="color:rgba(255,255,255,0.27)">En línea</p>
          </div>
          <div class="w-px h-10" style="background:rgba(255,255,255,0.1)" />
          <div class="text-center">
            <p class="text-2xl font-bold text-white">&#8734;</p>
            <p class="text-[10px] uppercase tracking-[0.15em] mt-1" style="color:rgba(255,255,255,0.27)">Productos</p>
          </div>
          <div class="w-px h-10" style="background:rgba(255,255,255,0.1)" />
          <div class="text-center">
            <p class="text-2xl font-bold text-white">24/7</p>
            <p class="text-[10px] uppercase tracking-[0.15em] mt-1" style="color:rgba(255,255,255,0.27)">Disponible</p>
          </div>
        </div>

        <div class="flex flex-wrap justify-center gap-2" style="margin-top:1.5rem">
          <span v-for="f in features" :key="f"
                class="flex items-center gap-1.5 text-[11px] font-medium px-3 py-1.5"
                style="background:#0a1628;color:#a8bbd4;border:1px solid rgba(59,130,246,0.35);border-radius:3px;box-shadow:0 0 8px rgba(59,130,246,0.08)">
            <span style="color:#3b82f6;font-size:7px">&#9650;</span>{{ f }}
          </span>
        </div>
      </div>

      <p class="relative z-10 text-[10px] tracking-wider uppercase" style="color:rgba(255,255,255,0.13)">
        &copy; {{ new Date().getFullYear() }} 28 Store &nbsp;&middot;&nbsp; Powered by JPStore
      </p>

      <div class="absolute right-0 inset-y-0 w-px"
           style="background:linear-gradient(to bottom,transparent 5%,rgba(59,130,246,0.55) 35%,rgba(59,130,246,0.55) 65%,transparent 95%)" />
    </div>

    <!-- DERECHA: login — 40% -->
    <div class="login-pane relative flex flex-col items-center justify-center overflow-hidden
                w-full lg:w-[40%] lg:min-w-[40%] lg:shrink-0 px-5 py-14 lg:px-6 lg:py-0"
         style="background:#050a14;">

      <!-- Grid oscuro animado -->
      <div class="right-grid absolute inset-0 pointer-events-none" />

      <!-- Orbs azules flotantes -->
      <div class="orb orb-1 absolute rounded-full pointer-events-none" />
      <div class="orb orb-2 absolute rounded-full pointer-events-none" />
      <div class="orb orb-3 absolute rounded-full pointer-events-none" />

      <!-- Partículas derechas -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div v-for="i in 5" :key="i" class="rp absolute rounded-full" :class="`rp-${i}`"
             style="background:rgba(59,130,246,0.5);width:2px;height:2px" />
      </div>

      <!-- Línea divisoria izquierda luminosa (ya la pone el panel izquierdo, esta refuerza) -->

      <!-- Theme toggle -->
      <div class="absolute top-5 right-5 z-20">
        <AppThemeToggle />
      </div>

      <!-- Logo móvil (en flujo, para que no se solape con la tarjeta) -->
      <img src="/logo-brand.png" alt="28 STORE"
           class="lg:hidden relative z-10 w-24 h-24 object-contain mb-7 shrink-0" />

      <!-- Card compacta -->
      <div class="form-card relative z-10 animate-form-in w-full max-w-[340px]">

        <div class="top-beam" />

        <!-- Esquinas L azules -->
        <span class="deco tl-h" /><span class="deco tl-v" />
        <span class="deco tr-h" /><span class="deco tr-v" />
        <span class="deco bl-h" /><span class="deco bl-v" />
        <span class="deco br-h" /><span class="deco br-v" />

        <div class="relative z-10">
          <div class="mb-6">
            <div class="flex items-center gap-2 mb-3">
              <div class="accent-line" />
              <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color:#3b82f6">Acceso al sistema</span>
            </div>
            <h1 class="text-xl font-bold leading-tight mb-1" style="color:#e8edf8">
              Bienvenido de vuelta
            </h1>
            <p class="text-xs" style="color:#3d5478">Ingresa tus credenciales para continuar</p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-4">

            <div class="field-group">
              <label class="field-label">Correo electrónico</label>
              <div class="field-wrap">
                <EnvelopeIcon class="field-icon" />
                <input v-model="form.email" type="email" class="field-input"
                       placeholder="tu@empresa.com" autocomplete="email" :disabled="loading" />
                <div class="field-focus-bar" />
              </div>
              <p v-if="errors.email" class="field-error">
                <ExclamationCircleIcon class="w-3 h-3" />{{ errors.email[0] }}
              </p>
            </div>

            <div class="field-group">
              <label class="field-label">Contraseña</label>
              <div class="field-wrap">
                <LockClosedIcon class="field-icon" />
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
                       class="field-input pr-10" placeholder="••••••••"
                       autocomplete="current-password" :disabled="loading" />
                <button type="button" class="field-eye" @click="showPassword = !showPassword" tabindex="-1">
                  <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                  <EyeIcon v-else class="w-4 h-4" />
                </button>
                <div class="field-focus-bar" />
              </div>
              <p v-if="errors.password" class="field-error">
                <ExclamationCircleIcon class="w-3 h-3" />{{ errors.password[0] }}
              </p>
            </div>

            <Transition name="err-slide">
              <div v-if="errorMessage" class="error-box">
                <ExclamationCircleIcon class="w-4 h-4 shrink-0" />
                <span>{{ errorMessage }}</span>
              </div>
            </Transition>

            <button type="submit" class="submit-btn" :disabled="loading">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                          stroke-dasharray="30" stroke-dashoffset="10" stroke-linecap="round"/>
                </svg>
                Verificando...
              </span>
              <span v-else class="flex items-center justify-center gap-2">
                Iniciar sesión
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
              </span>
              <div class="btn-shimmer" />
            </button>

          </form>

          <p class="text-center text-[10px] mt-5" style="color:#2a3d5a">
            Acceso restringido &nbsp;&middot;&nbsp; Usuarios autorizados
          </p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { EyeIcon, EyeSlashIcon, EnvelopeIcon, LockClosedIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppThemeToggle from '@/components/layout/AppThemeToggle.vue'

const auth         = useAuthStore()
const form         = reactive({ email: '', password: '' })
const errors       = ref({})
const errorMessage = ref('')
const loading      = ref(false)
const showPassword = ref(false)
const features     = ['Control de stock', 'Ventas en tiempo real', 'Multi-empresa', 'Reportes avanzados']

async function handleLogin() {
  errors.value = {}; errorMessage.value = ''; loading.value = true
  try {
    await auth.login(form.email, form.password)
  } catch (err) {
    const s = err.response?.status
    if (s === 422)      errors.value = err.response.data.errors || {}
    else if (s === 401) errorMessage.value = 'Correo o contraseña incorrectos.'
    else if (s === 403) errorMessage.value = err.response.data.message || 'Tu cuenta está desactivada.'
    else                errorMessage.value = 'Ocurrió un error. Intenta de nuevo.'
  } finally { loading.value = false }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Share+Tech+Mono&display=swap');
.login-root {
  font-family: inherit;
  /* Alto real del viewport: evita el salto por la barra de Safari en iOS */
  min-height: 100vh;
  min-height: 100svh;
  min-height: 100dvh;
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

/* El panel del formulario ocupa toda la pantalla en móvil */
.login-pane {
  min-height: 100vh;
  min-height: 100svh;
  min-height: 100dvh;
  /* Respeta el notch y la barra inferior del iPhone */
  padding-top: max(3.5rem, env(safe-area-inset-top));
  padding-bottom: max(3.5rem, env(safe-area-inset-bottom));
}
@media (min-width: 1024px) {
  .login-pane { min-height: auto; padding-top: 0; padding-bottom: 0; }
}

/* IZQUIERDA */
.grid-bg {
  background-image:
    linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);
  background-size:44px 44px;
  animation:grid-drift 22s linear infinite;
}
@keyframes grid-drift { from{background-position:0 0} to{background-position:44px 44px} }

.scan-line {
  height:1px;
  background:linear-gradient(90deg,transparent 10%,rgba(59,130,246,0.7) 50%,transparent 90%);
  animation:scan 6s ease-in-out infinite; top:0;
}
@keyframes scan {
  0%{top:-2px;opacity:0} 8%{opacity:1} 92%{opacity:1} 100%{top:100%;opacity:0}
}

.particle { width:3px;height:3px }
.particle-1{left:12%;top:18%;animation:fl1 9s  ease-in-out infinite}
.particle-2{left:78%;top:12%;animation:fl2 12s ease-in-out infinite 1s}
.particle-3{left:25%;top:72%;animation:fl1 10s ease-in-out infinite 2s}
.particle-4{left:82%;top:68%;animation:fl2 8s  ease-in-out infinite .5s}
.particle-5{left:55%;top:38%;animation:fl1 13s ease-in-out infinite 3s}
.particle-6{left: 8%;top:88%;animation:fl2 11s ease-in-out infinite 1.5s}
.particle-7{left:65%;top:82%;animation:fl1 7s  ease-in-out infinite 2.5s}
.particle-8{left:42%;top: 8%;animation:fl2 9s  ease-in-out infinite .8s}
@keyframes fl1{0%,100%{transform:translate(0,0);opacity:.35}50%{transform:translate(16px,-22px);opacity:1}}
@keyframes fl2{0%,100%{transform:translate(0,0);opacity:.3}50%{transform:translate(-18px,20px);opacity:.85}}

/* Marco geométrico alrededor del logo */
.logo-frame{
  position:absolute;inset:-24px;pointer-events:none;z-index:0;overflow:hidden;
}
/* Esquinas L del marco */
.lf{position:absolute;background:#3b82f6;opacity:0.6}
.lf.tl,.lf.tr,.lf.bl,.lf.br{animation:lf-glow 3s ease-in-out infinite}
.lf.tl{top:0;left:0;width:32px;height:2px;box-shadow:0 0 8px #3b82f6}
.lf.tl::after{content:'';position:absolute;top:0;left:0;width:2px;height:32px;background:#3b82f6;box-shadow:0 0 8px #3b82f6}
.lf.tr{top:0;right:0;width:32px;height:2px;box-shadow:0 0 8px #3b82f6}
.lf.tr::after{content:'';position:absolute;top:0;right:0;width:2px;height:32px;background:#3b82f6;box-shadow:0 0 8px #3b82f6}
.lf.bl{bottom:0;left:0;width:32px;height:2px;box-shadow:0 0 8px #3b82f6}
.lf.bl::after{content:'';position:absolute;bottom:0;left:0;width:2px;height:32px;background:#3b82f6;box-shadow:0 0 8px #3b82f6}
.lf.br{bottom:0;right:0;width:32px;height:2px;box-shadow:0 0 8px #3b82f6}
.lf.br::after{content:'';position:absolute;bottom:0;right:0;width:2px;height:32px;background:#3b82f6;box-shadow:0 0 8px #3b82f6}
@keyframes lf-glow{0%,100%{opacity:.4}50%{opacity:.9}}
/* Línea de scan que recorre el marco */
.lf-scan{
  position:absolute;left:0;right:0;height:1px;top:0;
  background:linear-gradient(90deg,transparent 0%,rgba(59,130,246,0.9) 50%,transparent 100%);
  animation:lf-scan-run 2.8s ease-in-out infinite;
}
@keyframes lf-scan-run{0%{top:0;opacity:0}5%{opacity:1}95%{opacity:1}100%{top:100%;opacity:0}}

.logo-wrap{position:relative;display:inline-block}
.brand-logo{animation:logo-in 1s cubic-bezier(0.22,1,0.36,1) both;transition:transform .5s,filter .5s}
.brand-logo:hover{transform:scale(1.03);filter:drop-shadow(0 0 36px rgba(59,130,246,.5))}
@keyframes logo-in{from{opacity:0;transform:scale(.82)translateY(24px)}to{opacity:1;transform:scale(1)translateY(0)}}
.logo-halo{position:absolute;inset:-40px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,.2)0%,transparent 65%);animation:halo 4.5s ease-in-out infinite;pointer-events:none;z-index:0}
@keyframes halo{0%,100%{opacity:.35;transform:scale(.92)}50%{opacity:1;transform:scale(1.06)}}

/* DERECHA */
.right-grid {
  background-image:
    linear-gradient(rgba(59,130,246,0.06) 1px, transparent 1px),
    linear-gradient(90deg,rgba(59,130,246,0.06) 1px,transparent 1px);
  background-size:36px 36px;
  animation:grid-drift 26s linear infinite reverse;
}

.orb{filter:blur(60px);border-radius:50%;animation:orb-drift ease-in-out infinite}
.orb-1{width:260px;height:260px;top:-50px;right:-30px;background:rgba(59,130,246,0.14);animation-duration:14s}
.orb-2{width:200px;height:200px;bottom:-40px;left:-20px;background:rgba(59,130,246,0.10);animation-duration:19s;animation-delay:-7s}
.orb-3{width:160px;height:160px;top:50%;right:5%;background:rgba(96,165,250,0.07);animation-duration:23s;animation-delay:-12s}
@keyframes orb-drift{0%,100%{transform:translate(0,0)}33%{transform:translate(-14px,22px)}66%{transform:translate(12px,-14px)}}

.rp{width:2px;height:2px}
.rp-1{left:18%;top:20%;animation:fl2 11s ease-in-out infinite}
.rp-2{left:72%;top:28%;animation:fl1 14s ease-in-out infinite 2s}
.rp-3{left:38%;top:68%;animation:fl2 9s  ease-in-out infinite 1s}
.rp-4{left:82%;top:72%;animation:fl1 12s ease-in-out infinite 3s}
.rp-5{left:55%;top:45%;animation:fl2 16s ease-in-out infinite .5s}

/* FORM CARD */
.form-card {
  position:relative;
  background:#0a1628;
  border:1px solid rgba(59,130,246,0.2);
  border-radius:6px;
  padding:2rem 1.75rem;
  overflow:hidden;
  animation:card-glow 5s ease-in-out infinite;
}
@keyframes card-glow {
  0%,100%{box-shadow:0 0 0 0 rgba(59,130,246,0),0 20px 50px rgba(0,0,0,0.5)}
  50%    {box-shadow:0 0 0 1px rgba(59,130,246,0.25),0 20px 50px rgba(0,0,0,0.6),0 0 50px rgba(59,130,246,0.1)}
}
/* Stream lateral derecho — líneas de datos cayendo */
.form-card::after {
  content:'';
  position:absolute;top:0;right:0;bottom:0;width:2px;
  background:linear-gradient(
    to bottom,
    transparent 0%,
    rgba(59,130,246,0) 20%,
    rgba(59,130,246,0.7) 50%,
    rgba(59,130,246,0) 80%,
    transparent 100%
  );
  background-size:100% 60%;
  animation:stream-fall 2.2s ease-in-out infinite;
  border-radius:0 4px 4px 0;
}
@keyframes stream-fall {
  0%  {background-position:0 -100%;opacity:0}
  10% {opacity:1}
  90% {opacity:1}
  100%{background-position:0 200%;opacity:0}
}

/* Beam superior */
.top-beam {
  position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent 0%,#3b82f6 40%,#60a5fa 60%,transparent 100%);
  background-size:200% 100%;
  animation:beam-run 3.5s ease-in-out infinite;
}
@keyframes beam-run {
  0%  {background-position:-100% 0;opacity:.5}
  50% {background-position:100%  0;opacity:1}
  100%{background-position:300%  0;opacity:.5}
}

/* Esquinas L decorativas */
.deco{position:absolute;background:#3b82f6;opacity:0;animation:deco-appear 1s ease forwards 0.4s}
@keyframes deco-appear{to{opacity:0.6}}
.tl-h{top:6px;left:6px;width:14px;height:1.5px}
.tl-v{top:6px;left:6px;width:1.5px;height:14px}
.tr-h{top:6px;right:6px;width:14px;height:1.5px}
.tr-v{top:6px;right:6px;width:1.5px;height:14px}
.bl-h{bottom:6px;left:6px;width:14px;height:1.5px}
.bl-v{bottom:6px;left:6px;width:1.5px;height:14px}
.br-h{bottom:6px;right:6px;width:14px;height:1.5px}
.br-v{bottom:6px;right:6px;width:1.5px;height:14px}

/* Accent line */
.accent-line{height:2px;background:linear-gradient(90deg,#3b82f6,#60a5fa);border-radius:1px;animation:accent-grow 3s ease-in-out infinite}
@keyframes accent-grow{0%,100%{width:14px;opacity:.7}50%{width:26px;opacity:1}}

@keyframes form-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.animate-form-in{animation:form-in .65s cubic-bezier(0.22,1,0.36,1) both .1s}

/* FIELDS */
.field-group{display:flex;flex-direction:column;gap:5px}
.field-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#3d5478}
.field-wrap{position:relative}
.field-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#3d5478;pointer-events:none;transition:color .2s}
.field-input{
  width:100%;padding:10px 12px 10px 38px;
  background:#060e1e !important;
  -webkit-appearance:none;
  border:1px solid #162844;
  border-radius:4px;
  color:#e8edf8 !important;
  /* Fuente futurista para lo que se escribe */
  font-family:'Space Grotesk', 'Share Tech Mono', monospace;
  font-size:13px;
  letter-spacing:0.04em;
  outline:none;
  transition:border-color .25s, box-shadow .25s, letter-spacing .2s, color .2s;
}
.field-input:-webkit-autofill,
.field-input:-webkit-autofill:hover,
.field-input:-webkit-autofill:focus {
  -webkit-box-shadow:0 0 0 1000px #060e1e inset !important;
  -webkit-text-fill-color:#e8edf8 !important;
  caret-color:#60a5fa;
}
.field-input::placeholder{color:#2a3d5a;font-family:inherit;letter-spacing:0.03em}
.field-input:not(:placeholder-shown){
  color:#7dd3fc !important;
  letter-spacing:0.06em;
  text-shadow:0 0 12px rgba(96,165,250,0.4);
}
.field-input:focus{
  border-color:rgba(59,130,246,0.65);
  box-shadow:0 0 0 3px rgba(59,130,246,0.12), 0 0 20px rgba(59,130,246,0.08);
  color:#bae6fd !important;
  letter-spacing:0.07em;
}
.field-wrap:focus-within .field-icon{color:#3b82f6}
.field-focus-bar{
  position:absolute;bottom:0;left:10%;right:10%;height:1px;
  background:linear-gradient(90deg,transparent,#3b82f6,transparent);
  transform:scaleX(0);transition:transform .3s ease;border-radius:1px;
}
.field-wrap:focus-within .field-focus-bar{transform:scaleX(1)}
.field-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#3d5478;padding:4px;border-radius:3px;transition:color .2s}
.field-eye:hover{color:#7a92b8}
.field-error{display:flex;align-items:center;gap:4px;font-size:11px;color:#f87171;margin-top:2px}

@media (max-width: 640px) {
  .field-input {
    font-size: 16px;
    padding: 12px 12px 12px 38px;
  }
  .form-card { padding: 1.5rem 1.25rem; }
  .submit-btn { padding: 13px; min-height: 46px; }
  .field-eye  { padding: 8px; }
}

/* ERROR */
.error-box{
  display:flex;align-items:center;gap:10px;padding:10px 12px;
  border-radius:4px;font-size:12px;
  background:rgba(244,63,94,0.08);border:1px solid rgba(244,63,94,0.25);
  color:#f87171;border-left:3px solid #f87171;
}
.err-slide-enter-active{animation:errSlide .25s ease}
.err-slide-leave-active{animation:errSlide .2s ease reverse}
@keyframes errSlide{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}

/* SUBMIT */
.submit-btn{
  position:relative;width:100%;padding:11px;border-radius:4px;
  font-size:14px;font-weight:700;letter-spacing:.03em;color:#fff;
  background:linear-gradient(135deg,#1d4ed8 0%,#3b82f6 50%,#2563eb 100%);
  background-size:200% 200%;overflow:hidden;
  transition:background-position .4s,transform .15s,box-shadow .3s;
  cursor:pointer;margin-top:2px;
}
.submit-btn:hover:not(:disabled){
  background-position:right center;
  box-shadow:0 4px 24px rgba(59,130,246,.45);
  transform:translateY(-1px);
}
.submit-btn:active:not(:disabled){transform:translateY(0)}
.submit-btn:disabled{opacity:.5;cursor:not-allowed}
.btn-shimmer{
  position:absolute;inset:0;
  background:linear-gradient(105deg,transparent 40%,rgba(255,255,255,.18) 50%,transparent 60%);
  transform:translateX(-100%);
}
.submit-btn:hover:not(:disabled) .btn-shimmer{animation:shimmer .6s ease forwards}
@keyframes shimmer{from{transform:translateX(-100%)}to{transform:translateX(100%)}}
</style>
