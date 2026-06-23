<script setup lang="ts">
import { computed, ref, h, defineComponent } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatCard from '@/components/StatCard.vue'
import DataTable from '@/components/DataTable.vue'
import ActionMenu from '@/components/ActionMenu.vue'
import { useContract, useContractCalculations } from '@/composables/queries/useContracts'
import { useContractMutation } from '@/composables/mutations/useContractMutation'
import { CONTRACT_ROUTES } from '@/routes/paths/contractRoutes'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
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

const { data: contract, isLoading: contractLoading } = useContract(id)
const { data: calculationsData, isLoading, isFetching } = useContractCalculations(id, page)

const { calculate, isPending: isCalculating, noActiveFormulaError, dismissError } = useContractMutation()

const calculations = computed(() => calculationsData.value?.data ?? [])
const pagination = computed(() => calculationsData.value?.meta)

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
    cell: info => h('span', { class: 'text-sm text-slate-500' }, formatDate(info.getValue())),
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
          <button type="button" class="premium-button" @click="calculate(id)" :disabled="isCalculating">
            <svg v-if="isCalculating" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ isCalculating ? 'Calculating…' : 'Calculate Commission' }}
          </button>
        </template>
      </PageHeader>

      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div v-if="noActiveFormulaError" class="mb-6 flex items-center justify-between rounded-md bg-amber-50 px-4 py-3 border border-amber-200">
        <div class="flex items-center gap-3">
          <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <p class="text-sm text-amber-800">You must have an <strong>Active Formula Version</strong> to calculate this contract.</p>
        </div>
        <button type="button" class="text-amber-600 hover:text-amber-900" @click="dismissError">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <StatCard title="Annual Usage" :value="contract.annual_usage.toLocaleString('en-US')" suffix="kWh" />
          <StatCard title="Contract Value" :value="formatCurrency(contract.contract_value)" />
          <StatCard title="Length" :value="contract.contract_length" suffix="months" />
          <StatCard title="Risk Score" :value="contract.risk_score" suffix="/ 10" />
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
