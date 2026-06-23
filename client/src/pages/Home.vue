<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { FORMULA_VERSION_ROUTES } from '@/routes/paths/formulaVersionRoutes'
import { CONTRACT_ROUTES } from '@/routes/paths/contractRoutes'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'

document.title = 'EnergyLogix — Commission Engine'
</script>

<template>
  <AppLayout>
    <div class="relative overflow-hidden py-24 sm:py-32">
      <!-- Decorative background blur behind hero -->
      <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-indigo-200 to-blue-400 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
      </div>
      
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center">
          <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-500 ring-1 ring-white/20">
            <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h1 class="mt-8 text-5xl font-extrabold tracking-tight text-slate-900 sm:text-6xl">
            Energy<span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-blue-500">Logix</span>
          </h1>
          <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
            A highly dynamic, extremely fast commission engine. Create and manage versioned formulas, run robust calculations, and analyze impact across all contracts instantly.
          </p>
          <div class="mt-10 flex flex-wrap justify-center gap-4">
            <RouterLink
              :to="FORMULA_VERSION_ROUTES.INDEX"
              class="premium-button"
            >
              Formula Versions
            </RouterLink>
            <RouterLink
              :to="CONTRACT_ROUTES.INDEX"
              class="premium-button-secondary"
            >
              Contracts
            </RouterLink>
            <RouterLink
              :to="CALCULATION_ROUTES.INDEX"
              class="premium-button-secondary"
            >
              Audit Trail
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
      <div class="grid gap-8 sm:grid-cols-3">
        <div class="premium-card p-8">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-500/20">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <h2 class="mt-5 text-lg font-bold tracking-tight text-slate-900">Formula Builder</h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">Create and version commission formulas with intermediate variables and full syntax validation powered by Symfony ExpressionLanguage.</p>
        </div>
        <div class="premium-card p-8">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 ring-1 ring-emerald-500/20">
            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h2 class="mt-5 text-lg font-bold tracking-tight text-slate-900">Impact Simulation</h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">Simulate exactly how activating a new formula affects total commission across thousands of contracts instantly without persisting data.</p>
        </div>
        <div class="premium-card p-8">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 ring-1 ring-violet-500/20">
            <svg class="h-6 w-6 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h2 class="mt-5 text-lg font-bold tracking-tight text-slate-900">Audit Trail</h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">Every calculation is cryptographically stored with its full input values and step-by-step resolution breakdown for bulletproof traceability.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
