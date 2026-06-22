<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import { formulaVersionsApi } from '@/api/formulaVersions'
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
    <div v-if="isLoading">
      <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <div class="h-4 w-32 animate-pulse rounded bg-slate-100" />
          <div class="mt-3 h-7 w-64 animate-pulse rounded bg-slate-100" />
        </div>
      </div>
    </div>

    <template v-else-if="formulaVersion">
      <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <RouterLink to="/formula-versions" class="mb-3 inline-flex items-center text-sm font-medium text-slate-500 hover:text-blue-600">
            <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Formula Versions
          </RouterLink>
          <div class="mt-1 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold text-slate-900">{{ formulaVersion.name }}</h1>
            <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-sm font-medium text-slate-600">v{{ formulaVersion.version_number }}</span>
            <span :class="formulaVersion.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100 text-slate-600'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">
              <span v-if="formulaVersion.is_active" class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500" />
              {{ formulaVersion.is_active ? 'Active' : 'Inactive' }}
            </span>
            <button v-if="!formulaVersion.is_active" type="button" class="ml-auto rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-500" @click="activate()">
              Activate
            </button>
          </div>
        </div>
      </div>

      <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Expression -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Expression</div>
          <pre class="overflow-x-auto bg-slate-950 p-5 font-mono text-sm text-emerald-400">{{ formulaVersion.expression }}</pre>
        </div>

        <!-- Intermediate Variables -->
        <div v-if="formulaVersion.variables?.length" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Intermediate Variables</div>
          <div class="px-6 py-4">
            <table class="min-w-full divide-y divide-slate-200">
              <thead>
                <tr>
                  <th class="pb-3 pr-4 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Variable</th>
                  <th class="pb-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Expression</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="variable in formulaVersion.variables" :key="variable.name">
                  <td class="py-3 pr-4 font-mono text-sm font-medium text-blue-700">{{ variable.name }}</td>
                  <td class="py-3 font-mono text-sm text-slate-600">{{ variable.expression }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Simulation -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Impact Simulation</div>
          <div class="px-6 py-5">
            <p class="mb-4 text-sm text-slate-500">
              Simulate how activating this formula would affect total commission across all contracts — without persisting any records.
            </p>
            <button type="button" :disabled="isSimulating" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 disabled:opacity-50" @click="runSimulation">
              <svg v-if="isSimulating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              {{ isSimulating ? 'Simulating…' : 'Run Simulation' }}
            </button>

            <div v-if="simulationError" class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ simulationError }}</div>

            <div v-if="simulationResult" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Affected Contracts</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ simulationResult.affected_contract_count }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Current Total</p>
                <p class="mt-2 text-2xl font-bold text-slate-500">{{ formatCurrency(simulationResult.current_total_commission) }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">New Total</p>
                <p class="mt-2 text-2xl font-bold text-blue-600">{{ formatCurrency(simulationResult.new_total_commission) }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Difference</p>
                <p class="mt-2 text-2xl font-bold" :class="simulationResult.difference >= 0 ? 'text-emerald-600' : 'text-red-600'">
                  {{ simulationResult.difference >= 0 ? '▲' : '▼' }}
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
