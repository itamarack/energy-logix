<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import StatCard from '@/components/StatCard.vue'
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

const { mutate: deactivate } = useMutation({
  mutationFn: () => formulaVersionsApi.deactivate(id),
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
    <div v-if="isLoading" class="premium-card m-4 sm:m-6 lg:m-8">
      <div class="px-6 py-8">
        <div class="h-4 w-32 animate-pulse rounded-full bg-slate-200/50" />
        <div class="mt-4 h-7 w-64 animate-pulse rounded-full bg-slate-200/50" />
      </div>
    </div>

    <template v-else-if="formulaVersion">
      <PageHeader>
        <template #breadcrumbs>
          <RouterLink :to="FORMULA_VERSION_ROUTES.INDEX" class="mb-3 inline-flex items-center text-[13px] font-bold uppercase tracking-widest text-slate-500 hover:text-indigo-600 transition-colors">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Formula Versions
          </RouterLink>
        </template>
        <template #title>
          <div class="mt-1 flex flex-wrap items-center gap-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ formulaVersion.name }}</h1>
            <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-[13px] font-bold text-slate-600 ring-1 ring-inset ring-slate-500/10">v{{ formulaVersion.version_number }}</span>
            <StatusBadge :active="formulaVersion.is_active" />
          </div>
        </template>
        <template #description>
          <p v-if="formulaVersion.description" class="mt-2 text-sm text-slate-500">{{ formulaVersion.description }}</p>
        </template>
        <template #actions>
          <RouterLink :to="FORMULA_VERSION_ROUTES.EDIT(formulaVersion.id)" class="premium-button-secondary !px-4 !py-2 !text-[13px]">
            Edit Formula
          </RouterLink>
          <a 
            v-if="!formulaVersion.is_active" 
            :href="`/api/v1/formula-versions/${formulaVersion.id}/report`" 
            class="premium-button-secondary !bg-white hover:!bg-slate-50 !px-4 !py-2 !text-[13px]"
            download
          >
            Download Closing Report
          </a>
          <button v-if="!formulaVersion.is_active" type="button" class="premium-button !px-4 !py-2 !text-[13px]" @click="activate()">
            Activate Version
          </button>
          <button v-else type="button" class="premium-button !bg-rose-500 !shadow-rose-500/30 hover:!bg-rose-600 !px-4 !py-2 !text-[13px]" @click="deactivate()">
            Deactivate
          </button>
        </template>
      </PageHeader>

      <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="premium-card overflow-hidden">
          <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Expression</div>
          <div class="relative overflow-hidden bg-slate-50 px-6 py-5">
            <pre class="font-mono text-[14px] font-medium leading-relaxed text-slate-800">{{ formulaVersion.expression }}</pre>
          </div>
        </div>

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

            <div v-if="simulationResult" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
              <StatCard title="Affected Contracts" :value="simulationResult.affected_contract_count" />
              <StatCard title="Current Total" :value="formatCurrency(simulationResult.current_total_commission)" />
              <StatCard title="New Total" :value="formatCurrency(simulationResult.new_total_commission)" />
              <StatCard 
                title="Difference" 
                :value="(simulationResult.difference > 0 ? '+' : '') + formatCurrency(simulationResult.difference)" 
              />
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>
