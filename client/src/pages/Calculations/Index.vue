<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import { calculationsApi } from '@/api/calculations'
import type { CommissionCalculation } from '@/types'

document.title = 'Calculations — EnergyLogix'

const router = useRouter()
const search = ref('')

const { data: calculations, isLoading } = useQuery({
  queryKey: ['calculations'],
  queryFn: calculationsApi.list,
})

const filtered = computed(() =>
  (calculations.value ?? []).filter((c: CommissionCalculation) => {
    const term = search.value.toLowerCase()
    return (
      c.formula_version?.name?.toLowerCase().includes(term) ||
      c.contract?.name?.toLowerCase().includes(term)
    )
  }),
)

function formatCurrency(value: number) {
  return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}
</script>

<template>
  <AppLayout>
    <div class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-slate-900">Commission Calculations</h1>
        <p class="mt-1 text-sm text-slate-500">Immutable audit trail of all commission records</p>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-4">
        <div class="relative max-w-xs">
          <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
          </svg>
          <input v-model="search" type="text" placeholder="Search by formula or contract…" class="block w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="isLoading" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 border-b border-slate-100 px-6 py-4 last:border-0">
          <div class="h-4 w-40 animate-pulse rounded bg-slate-100" />
          <div class="h-4 w-32 animate-pulse rounded bg-slate-100" />
          <div class="h-4 w-24 animate-pulse rounded bg-slate-100" />
        </div>
      </div>

      <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Formula Version</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Contract</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Commission Result</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Calculated At</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="calc in filtered" :key="calc.id" class="cursor-pointer transition-colors hover:bg-slate-50" @click="router.push(`/calculations/${calc.id}`)">
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <span class="font-medium text-slate-900">{{ calc.formula_version?.name ?? '—' }}</span>
                <span class="ml-1.5 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-500">v{{ calc.formula_version?.version_number }}</span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ calc.contract?.name ?? '—' }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">{{ formatCurrency(calc.result) }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                {{ new Date(calc.calculated_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
              </td>
            </tr>
            <tr v-if="filtered.length === 0">
              <td colspan="4" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center gap-2">
                  <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <p class="text-sm text-slate-500">
                    {{ search ? 'No calculations match your search.' : 'No calculations yet. Go to Contracts and click Calculate.' }}
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
