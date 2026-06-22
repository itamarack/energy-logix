<script setup lang="ts">
import { RouterLink } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

document.title = 'EnergyLogix — Commission Engine'
</script>

<template>
  <AppLayout>
    <div class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center text-center">
          <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-600 shadow-lg">
            <svg class="h-9 w-9 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <h1 class="mt-6 text-4xl font-semibold text-slate-900">EnergyLogix</h1>
          <p class="mt-3 max-w-xl text-lg text-slate-500">
            Dynamic Commission Engine — create and manage formula versions, run calculations, and
            analyse formula impact across all contracts.
          </p>
          <div class="mt-8 flex flex-wrap justify-center gap-4">
            <RouterLink
              to="/formula-versions"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500"
            >
              Formula Versions
            </RouterLink>
            <RouterLink
              to="/contracts"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
            >
              Contracts
            </RouterLink>
            <RouterLink
              to="/calculations"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
            >
              Audit Trail
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid gap-6 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <h2 class="mt-4 text-base font-semibold text-slate-900">Formula Builder</h2>
          <p class="mt-1 text-sm text-slate-500">Create and version commission formulas with intermediate variables and full expression validation.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50">
            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h2 class="mt-4 text-base font-semibold text-slate-900">Impact Simulation</h2>
          <p class="mt-1 text-sm text-slate-500">Simulate how activating a new formula affects total commission across all contracts — without touching real records.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50">
            <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h2 class="mt-4 text-base font-semibold text-slate-900">Audit Trail</h2>
          <p class="mt-1 text-sm text-slate-500">Every calculation is stored with full input values and step-by-step breakdown for complete traceability.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
