<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { useHealthCheck } from '@/composables/queries/useHealthCheck'

const route = useRoute()

const { isError, isLoading } = useHealthCheck()

import { FORMULA_VERSION_ROUTES } from '@/routes/paths/formulaVersionRoutes'
import { CONTRACT_ROUTES } from '@/routes/paths/contractRoutes'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'

const navLinks = [
  { label: 'Formula Versions', to: FORMULA_VERSION_ROUTES.INDEX },
  { label: 'Contracts', to: CONTRACT_ROUTES.INDEX },
  { label: 'Calculations', to: CALCULATION_ROUTES.INDEX },
]

function isActive(path: string): boolean {
  return route.path.startsWith(path)
}
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <header class="sticky top-0 z-40 border-b border-white/20 bg-white/70 backdrop-blur-xl">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <RouterLink to="/" class="group flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-blue-500 transition-transform duration-300 group-hover:scale-105 group-hover:-rotate-3">
              <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <p class="text-[15px] font-bold tracking-tight text-slate-900">Energy<span class="text-indigo-600">Logix</span></p>
              <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Commission Engine</p>
            </div>
          </RouterLink>

          <nav class="hidden items-center gap-2 md:flex">
            <RouterLink
              v-for="link in navLinks"
              :key="link.to"
              :to="link.to"
              :class="[
                'rounded-lg px-4 py-2 text-[13px] font-semibold transition-all duration-200',
                isActive(link.to)
                  ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/10'
                  : 'text-slate-500 hover:bg-slate-100/80 hover:text-slate-900',
              ]"
            >
              {{ link.label }}
            </RouterLink>
          </nav>

          <div class="flex items-center gap-2">
            <span
              v-if="isLoading"
              class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/50"
            >
              <span class="relative flex h-2 w-2">
                <span class="relative inline-flex h-2 w-2 rounded-full bg-slate-300"></span>
              </span>
              Checking...
            </span>
            <span
              v-else-if="isError"
              class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-600/20"
            >
              <span class="relative flex h-2 w-2">
                <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
              </span>
              System Offline
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20"
            >
              <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
              </span>
              System Online
            </span>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="mt-auto border-t border-slate-200/60 bg-white/50 backdrop-blur-md py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
          <p class="text-[13px] font-medium text-slate-500">
            © {{ new Date().getFullYear() }} EnergyLogix
          </p>
          <p class="text-xs tracking-wide text-slate-400">
            Dynamic Commission Engine
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>
