<script setup lang="ts">
import { computed, ref, h, defineComponent } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import DataTable from '@/components/DataTable.vue'
import ActionMenu from '@/components/ActionMenu.vue'
import { useContract, useContractCalculations, useCalculateContract } from '@/composables/queries/useContracts'
import { CONTRACT_ROUTES } from '@/routes/paths/contractRoutes'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'
import type { CommissionCalculation } from '@/types'
import {
  getCoreRowModel,
  useVueTable,
  createColumnHelper,
} from '@tanstack/vue-table'

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

document.title = 'Contract — EnergyLogix'

const page = ref(1)
const noActiveFormulaError = ref(false)

const { data: contract, isLoading: contractLoading } = useContract(id)
const { data: calculationsData, isLoading, isFetching } = useContractCalculations(id, page)
const { mutateAsync: calculateContract, isPending: isCalculating } = useCalculateContract()

const calculations = computed(() => calculationsData.value?.data ?? [])
const pagination = computed(() => calculationsData.value?.meta)

async function calculate() {
  noActiveFormulaError.value = false
  try {
    await calculateContract(id)
  } catch (err: unknown) {
    const e = err as Error & { status?: number }
    if (e.status === 422) noActiveFormulaError.value = true
  }
}

function formatCurrency(value: number) {
  return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}

const columnHelper = createColumnHelper<CommissionCalculation>()

const columns = [
  columnHelper.accessor(row => row.formula_version, {
    id: 'formula_version',
    header: 'Formula Version',
    cell: info => {
      const v = info.getValue()
      return h('div', { class: 'flex items-center gap-2' }, [
        h('span', { class: 'text-sm text-slate-900' }, v?.name ?? '—'),
        h('span', { class: 'rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-500 ring-1 ring-inset ring-slate-500/10' }, `v${v?.version_number}`),
      ])
    },
  }),
  columnHelper.accessor('result', {
    header: 'Commission Result',
    cell: info => h('span', { class: 'font-bold text-slate-900' }, formatCurrency(info.getValue())),
  }),
  columnHelper.accessor('calculated_at', {
    header: 'Calculated At',
    cell: info => h('span', { class: 'text-sm text-slate-500' }, new Date(info.getValue()).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })),
  }),
  columnHelper.display({
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, ''),
    cell: props => {
      const calc = props.row.original
      return h(defineComponent({
        setup() {
          return () => h(ActionMenu, null, {
            default: () => [
              h('button', {
                type: 'button',
                class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50',
                onClick: () => router.push(CALCULATION_ROUTES.SHOW(calc.id)),
              }, [
                h('svg', { class: 'h-4 w-4 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z' }),
                  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' }),
                ]),
                'View Audit',
              ]),
            ]
          })
        }
      }))
    },
  }),
]

const table = useVueTable({
  get data() { return calculations.value },
  columns,
  getCoreRowModel: getCoreRowModel(),
})
</script>

<template>
  <AppLayout>
    <div v-if="contractLoading" class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="h-4 w-24 animate-pulse rounded-full bg-slate-200/50" />
        <div class="mt-3 h-7 w-56 animate-pulse rounded-full bg-slate-200/50" />
      </div>
    </div>

    <template v-else-if="contract">
      <PageHeader 
        :title="contract.name" 
        description="Commission calculation history for this contract."
      >
        <template #breadcrumbs>
          <div class="mb-1 flex items-center gap-2 text-sm text-slate-500">
            <RouterLink :to="CONTRACT_ROUTES.INDEX" class="hover:text-blue-600">Contracts</RouterLink>
            <span>/</span>
            <span>{{ contract.name }}</span>
          </div>
        </template>
        <template #actions>
          <button
            type="button"
            :disabled="isCalculating"
            class="premium-button"
            @click="calculate"
          >
            <svg v-if="isCalculating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.82m2.56-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
            </svg>
            {{ isCalculating ? 'Calculating…' : 'Calculate Now' }}
          </button>
        </template>
      </PageHeader>

      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div v-if="noActiveFormulaError" class="mb-4 flex items-start justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
          <span>No active formula version — please activate one first.</span>
          <button type="button" class="ml-4 shrink-0 text-amber-600 hover:text-amber-900" @click="noActiveFormulaError = false">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div class="premium-card !p-5">
            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Annual Usage</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ contract.annual_usage.toLocaleString('en-US') }} <span class="text-xs font-normal text-slate-400">kWh</span></p>
          </div>
          <div class="premium-card !p-5">
            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Contract Value</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ formatCurrency(contract.contract_value) }}</p>
          </div>
          <div class="premium-card !p-5">
            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Length</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ contract.contract_length }} <span class="text-xs font-normal text-slate-400">months</span></p>
          </div>
          <div class="premium-card !p-5">
            <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Risk Score</p>
            <p class="mt-2 text-lg font-bold text-slate-900">{{ contract.risk_score }} <span class="text-xs font-normal text-slate-400">/ 10</span></p>
          </div>
        </div>

        <div class="mb-3 text-[13px] font-bold uppercase tracking-widest text-slate-400">Calculation History</div>
        <DataTable
          :table="table"
          :is-loading="isLoading"
          :is-fetching="isFetching"
          :pagination="pagination"
          v-model:page="page"
          empty-message="No calculations yet for this contract."
          @row-click="(row) => router.push(CALCULATION_ROUTES.SHOW(row.id))"
        />
      </div>
    </template>
  </AppLayout>
</template>
