<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import AppLayout from '@/layouts/AppLayout.vue'
import { calculationsApi } from '@/api/calculations'

const route = useRoute()
const id = Number(route.params.id)

const { data: calculation, isLoading } = useQuery({
  queryKey: ['calculations', id],
  queryFn: () => calculationsApi.get(id),
})

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
          <div class="h-4 w-48 animate-pulse rounded bg-slate-100" />
          <div class="mt-2 h-7 w-56 animate-pulse rounded bg-slate-100" />
        </div>
      </div>
    </div>

    <template v-else-if="calculation">
      <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <div class="mb-1 flex items-center gap-2 text-sm text-slate-500">
            <RouterLink to="/calculations" class="hover:text-blue-600">Calculations</RouterLink>
            <span>/</span>
            <span class="font-mono">#{{ calculation.id }}</span>
          </div>
          <h1 class="text-2xl font-semibold text-slate-900">Commission Audit</h1>
          <p class="mt-1 text-sm text-slate-500">
            Calculation #{{ calculation.id }} · {{ new Date(calculation.calculated_at).toLocaleString() }}
          </p>
        </div>
      </div>

      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
          <div class="space-y-6 lg:col-span-2">
            <!-- Input Values -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Input Values</div>
              <div class="px-6 py-4">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead>
                    <tr>
                      <th class="pb-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Variable</th>
                      <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Value</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(value, key) in calculation.input_values" :key="key">
                      <td class="py-3 font-mono text-sm font-medium text-blue-700">{{ key }}</td>
                      <td class="py-3 text-right font-mono text-sm text-slate-700">{{ value.toLocaleString() }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Calculation Steps -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Calculation Steps</div>
              <div class="px-6 py-4">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead>
                    <tr>
                      <th class="w-12 pb-3 pr-4 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Step</th>
                      <th class="pb-3 pr-4 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Variable</th>
                      <th class="pb-3 pr-4 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Expression</th>
                      <th class="pb-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Value</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="step in calculation.calculation_steps" :key="step.step" :class="{ 'bg-blue-50 font-semibold': step.variable === 'RESULT' }">
                      <td class="py-3 pr-4 text-sm text-slate-500">{{ step.variable === 'RESULT' ? '' : step.step }}</td>
                      <td class="py-3 pr-4 font-mono text-sm font-medium" :class="step.variable === 'RESULT' ? 'text-blue-700' : 'text-blue-600'">{{ step.variable }}</td>
                      <td class="py-3 pr-4 font-mono text-sm text-slate-600">{{ step.expression }}</td>
                      <td class="py-3 text-right font-mono text-sm" :class="step.variable === 'RESULT' ? 'font-bold text-blue-700' : 'text-slate-700'">
                        {{ step.variable === 'RESULT' ? formatCurrency(step.value) : step.value.toLocaleString() }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="mt-6 space-y-6 lg:col-span-1 lg:mt-0">
            <!-- Summary -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
              <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-700">Summary</div>
              <div class="divide-y divide-slate-100">
                <div class="px-6 py-4">
                  <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Formula</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ calculation.formula_version?.name ?? '—' }}
                    <span class="ml-1 font-mono text-xs text-slate-400">v{{ calculation.formula_version?.version_number }}</span>
                  </p>
                </div>
                <div class="px-6 py-4">
                  <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Contract</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">{{ calculation.contract?.name ?? '—' }}</p>
                </div>
                <div class="px-6 py-4">
                  <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Calculated At</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">{{ new Date(calculation.calculated_at).toLocaleString() }}</p>
                </div>
              </div>
            </div>

            <!-- Result card -->
            <div class="rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 p-6 text-center text-white shadow-lg">
              <p class="text-sm font-medium uppercase tracking-wider text-blue-200">Commission Result</p>
              <p class="mt-2 text-5xl font-bold">{{ formatCurrency(calculation.result) }}</p>
              <p class="mt-2 text-sm text-blue-200">Calculated {{ new Date(calculation.calculated_at).toLocaleString() }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>
