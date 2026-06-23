<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import { formulaVersionsApi } from '@/api/formulaVersions'
import { FORMULA_VERSION_ROUTES } from '@/routes/paths/formulaVersionRoutes'
import type { SimulationResult } from '@/types'

const route = useRoute()
const id = Number(route.params.id)
const queryClient = useQueryClient()

const isSimulating = ref(false)
const simulationResult = ref<SimulationResult | null>(null)
const simulationError = ref<string | null>(null)

const { data: formulaVersion, isLoading } = useQuery({
  queryKey: ['formula-versions', id],
  queryFn: () => formulaVersionsApi.get(id),
})

const { mutate: activate } = useMutation({
  mutationFn: () => formulaVersionsApi.activate(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['formula-versions'] })
    queryClient.invalidateQueries({ queryKey: ['formula-versions', id] })
  },
})

async function runSimulation() {
  isSimulating.value = true
  simulationResult.value = null
  simulationError.value = null
  try {
    simulationResult.value = await formulaVersionsApi.simulate(id)
  } catch (err: unknown) {
    const e = err as Error & { data?: { message?: string } }
    simulationError.value = e.data?.message ?? e.message ?? 'An unexpected error occurred.'
  } finally {
    isSimulating.value = false
  }
}

function formatCurrency(value: number) {
  return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}
</script>

<template>
  <AppLayout>
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="premium-card m-4 sm:m-6 lg:m-8">
      <div class="px-6 py-8">
        <div class="h-4 w-32 animate-pulse rounded-full bg-slate-200/50" />
        <div class="mt-4 h-7 w-64 animate-pulse rounded-full bg-slate-200/50" />
      </div>
    </div>

    <template v-else-if="formulaVersion">
      <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <RouterLink :to="FORMULA_VERSION_ROUTES.INDEX" class="mb-3 inline-flex items-center text-[13px] font-bold uppercase tracking-widest text-slate-500 hover:text-indigo-600 transition-colors">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Formula Versions
          </RouterLink>
          <div class="mt-1 flex flex-wrap items-center gap-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ formulaVersion.name }}</h1>
            <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-[13px] font-bold text-slate-600 ring-1 ring-inset ring-slate-500/10">v{{ formulaVersion.version_number }}</span>
            <span
              :class="formulaVersion.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100/80 text-slate-600'"
              class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
            >
              <span v-if="formulaVersion.is_active" class="mr-2 relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
              </span>
              {{ formulaVersion.is_active ? 'Active' : 'Inactive' }}
            </span>
            <button v-if="!formulaVersion.is_active" type="button" class="ml-auto premium-button !px-4 !py-2 !text-[13px]" @click="activate()">
              Activate Formula
            </button>
          </div>
        </div>
      </div>

      <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Expression -->
        <div class="premium-card overflow-hidden">
          <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Expression</div>
          <div class="relative overflow-hidden bg-slate-50 px-6 py-5">
            <pre class="font-mono text-[14px] font-medium leading-relaxed text-slate-800">{{ formulaVersion.expression }}</pre>
          </div>
        </div>

        <!-- Intermediate Variables -->
        <div v-if="formulaVersion.variables?.length" class="premium-card">
          <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Intermediate Variables</div>
          <div class="px-6 py-4">
            <table class="min-w-full divide-y divide-slate-100/80">
              <thead>
                <tr>
                  <th class="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400">Variable</th>
                  <th class="pb-3 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400">Expression</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100/80">
                <tr v-for="variable in formulaVersion.variables" :key="variable.name" class="transition-colors hover:bg-slate-50/50">
                  <td class="py-4 pr-4 font-mono text-[13px] font-bold text-indigo-700">{{ variable.name }}</td>
                  <td class="py-4 font-mono text-[13px] text-slate-600">{{ variable.expression }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Simulation -->
        <div class="premium-card">
          <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Impact Simulation</div>
          <div class="px-6 py-6">
            <p class="mb-6 text-[14px] text-slate-500">
              Simulate how activating this formula would affect total commission across all contracts — without persisting any records.
            </p>
            <button type="button" :disabled="isSimulating" class="premium-button !bg-indigo-600 hover:!bg-indigo-500 !px-5" @click="runSimulation">
              <svg v-if="isSimulating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              {{ isSimulating ? 'Simulating…' : 'Run Simulation' }}
            </button>

            <div v-if="simulationError" class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
              <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              {{ simulationError }}
            </div>

            <div v-if="simulationResult" class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4">
              <div class="premium-card !p-5">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Affected Contracts</p>
                <p class="mt-3 text-3xl font-black text-slate-900">{{ simulationResult.affected_contract_count }}</p>
              </div>
              <div class="premium-card !p-5">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Current Total</p>
                <p class="mt-3 font-mono text-3xl font-black text-slate-500">{{ formatCurrency(simulationResult.current_total_commission) }}</p>
              </div>
              <div class="premium-card !p-5">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">New Total</p>
                <p class="mt-3 font-mono text-3xl font-black text-indigo-600">{{ formatCurrency(simulationResult.new_total_commission) }}</p>
              </div>
              <div class="premium-card !p-5 relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2" :class="simulationResult.difference >= 0 ? 'bg-emerald-500' : 'bg-red-500'"></div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Difference</p>
                <p class="mt-3 font-mono text-3xl font-black" :class="simulationResult.difference >= 0 ? 'text-emerald-600' : 'text-red-600'">
                  <span class="text-xl">{{ simulationResult.difference >= 0 ? '▲' : '▼' }}</span>
                  {{ formatCurrency(Math.abs(simulationResult.difference)) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>
