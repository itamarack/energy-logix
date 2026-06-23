<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import { calculationsApi } from '@/api/calculations'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'
import { exportCalculationCsv } from '@/utils/exportCalculationCsv'
import { formatCurrency } from '@/utils/formatCurrency'

const route = useRoute()
const id = Number(route.params.id)

const { data: calculation, isLoading } = useQuery({
  queryKey: ['calculations', id],
  queryFn: () => calculationsApi.get(id),
})
</script>

<template>
  <AppLayout>
    <div v-if="isLoading" class="premium-card m-4 sm:m-6 lg:m-8">
      <div class="px-6 py-8">
        <div class="h-4 w-48 animate-pulse rounded-full bg-slate-200/50" />
        <div class="mt-4 h-7 w-56 animate-pulse rounded-full bg-slate-200/50" />
      </div>
    </div>

    <template v-else-if="calculation">
      <PageHeader 
        title="Commission Audit" 
        :description="`Calculation #${calculation.id} · ${new Date(calculation.calculated_at).toLocaleString()}`"
      >
        <template #breadcrumbs>
          <div class="mb-1 flex items-center gap-2 text-sm text-slate-500">
            <RouterLink :to="CALCULATION_ROUTES.INDEX" class="hover:text-blue-600">Calculations</RouterLink>
            <span>/</span>
            <span class="font-mono">#{{ calculation.id }}</span>
          </div>
        </template>
        
        <template #actions>
          <button
            type="button"
            @click="exportCalculationCsv(calculation!)"
            class="premium-button"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export CSV
          </button>
        </template>
      </PageHeader>

      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
          <div class="space-y-6 lg:col-span-2">
            <div class="premium-card">
              <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Input Values</div>
              <div class="px-6 py-4">
                <table class="min-w-full divide-y divide-slate-100/80">
                  <tbody class="divide-y divide-slate-100/80">
                    <tr v-for="(value, key) in calculation.input_values" :key="key" class="transition-colors hover:bg-slate-50/50">
                      <td class="py-3 font-mono text-[13px] font-bold text-indigo-600">{{ key }}</td>
                      <td class="py-3 text-right font-mono text-[13px] text-slate-700">{{ value.toLocaleString() }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="premium-card">
              <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Calculation Steps</div>
              <div class="px-6 py-4">
                <table class="min-w-full divide-y divide-slate-100/80">
                  <thead class="hidden">
                    <tr>
                      <th>Step</th>
                      <th>Variable</th>
                      <th>Expression</th>
                      <th>Value</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100/80">
                    <tr v-for="step in calculation.calculation_steps" :key="step.step" :class="{ 'bg-indigo-50/50': step.variable === 'RESULT' }" class="transition-colors hover:bg-slate-50/50">
                      <td class="py-4 pr-4 text-[12px] font-semibold text-slate-400">{{ step.variable === 'RESULT' ? '' : step.step }}</td>
                      <td class="py-4 pr-4 font-mono text-[13px] font-bold" :class="step.variable === 'RESULT' ? 'text-indigo-700' : 'text-indigo-600'">{{ step.variable }}</td>
                      <td class="py-4 pr-4 font-mono text-[13px] text-slate-500">{{ step.expression }}</td>
                      <td class="py-4 text-right font-mono text-[13px]" :class="step.variable === 'RESULT' ? 'font-black text-indigo-700 text-[15px]' : 'text-slate-700'">
                        {{ step.variable === 'RESULT' ? formatCurrency(step.value) : step.value.toLocaleString() }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="mt-6 space-y-6 lg:col-span-1 lg:mt-0">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-800 p-8 text-center text-white">
              <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
              <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-blue-400/20 blur-3xl"></div>
              <p class="relative z-10 text-[11px] font-bold uppercase tracking-widest text-indigo-200/80">Final Commission Result</p>
              <p class="relative z-10 mt-3 font-mono text-5xl font-black tracking-tight">{{ formatCurrency(calculation.result) }}</p>
              <p class="relative z-10 mt-4 text-[13px] font-medium text-indigo-200">Calculated {{ new Date(calculation.calculated_at).toLocaleString() }}</p>
            </div>

            <div class="premium-card">
              <div class="border-b border-slate-200/50 bg-slate-50/50 px-6 py-4 text-[13px] font-bold uppercase tracking-widest text-slate-500">Metadata</div>
              <div class="divide-y divide-slate-100/80">
                <div class="px-6 py-4">
                  <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Formula</p>
                  <p class="mt-1 flex items-center gap-2 text-[15px] font-semibold text-slate-900">
                    {{ calculation.formula_version?.name ?? '—' }}
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">v{{ calculation.formula_version?.version_number }}</span>
                  </p>
                </div>
                <div class="px-6 py-4">
                  <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Contract</p>
                  <p class="mt-1 text-[15px] font-semibold text-slate-900">{{ calculation.contract?.name ?? '—' }}</p>
                </div>
                <div class="px-6 py-4">
                  <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Calculated At</p>
                  <p class="mt-1 text-[15px] font-semibold text-slate-900">{{ new Date(calculation.calculated_at).toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>
