<script setup lang="ts">
import { computed, ref, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import DataTable from '@/components/DataTable.vue'
import { useContracts, useCalculateContract } from '@/composables/queries/useContracts'
import type { Contract } from '@/types'
import {
  getCoreRowModel,
  useVueTable,
  createColumnHelper,
} from '@tanstack/vue-table'

document.title = 'Contracts — EnergyLogix'

const page = ref(1)

const loadingIds = ref<Set<number>>(new Set())
const lastCommissions = ref<Record<number, number>>({})
const noActiveFormulaError = ref(false)

const { data: contractData, isLoading, isFetching } = useContracts(page)
const { mutateAsync: calculateContract } = useCalculateContract()

const contracts = computed(() => contractData.value?.data ?? [])
const pagination = computed(() => contractData.value?.meta)

async function calculate(contractId: number) {
  noActiveFormulaError.value = false
  loadingIds.value = new Set([...loadingIds.value, contractId])
  try {
    const calc = await calculateContract(contractId)
    lastCommissions.value[contractId] = calc.result
  } catch (err: unknown) {
    const e = err as Error & { status?: number }
    if (e.status === 422) {
      noActiveFormulaError.value = true
    }
  } finally {
    const next = new Set(loadingIds.value)
    next.delete(contractId)
    loadingIds.value = next
  }
}

function formatCurrency(value: number) {
  return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}

const columnHelper = createColumnHelper<Contract>()

const columns = [
  columnHelper.accessor('name', {
    header: 'Name',
    cell: info => info.getValue(),
  }),
  columnHelper.accessor('annual_usage', {
    header: 'Annual Usage (kWh)',
    cell: info => info.getValue().toLocaleString('en-US'),
  }),
  columnHelper.accessor('contract_value', {
    header: 'Contract Value ($)',
    cell: info => info.getValue().toLocaleString('en-US'),
  }),
  columnHelper.accessor('contract_length', {
    header: 'Length (months)',
    cell: info => info.getValue().toLocaleString('en-US'),
  }),
  columnHelper.accessor('risk_score', {
    header: 'Risk Score',
    cell: info => h('span', { class: 'inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10' }, `${info.getValue().toLocaleString('en-US')} / 10`),
  }),
  columnHelper.display({
    id: 'last_commission',
    header: 'Last Commission',
    cell: props => {
      const contractId = props.row.original.id
      const commission = lastCommissions.value[contractId]
      if (commission !== undefined) {
        return h('span', { class: 'inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-[13px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20' }, formatCurrency(commission))
      }
      return h('span', { class: 'text-slate-400 font-mono text-sm' }, '—')
    },
  }),
  columnHelper.display({
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, ''),
    cell: props => {
      const contractId = props.row.original.id
      const isCalculating = loadingIds.value.has(contractId)
      
      return h('div', { class: 'text-right' }, [
        h('button', {
          type: 'button',
          class: 'premium-button !px-3 !py-1.5 !text-[12px] !rounded-lg',
          disabled: isCalculating,
          onClick: () => calculate(contractId),
        }, [
          isCalculating ? h('svg', { class: 'h-3.5 w-3.5 animate-spin mr-2 inline-block', fill: 'none', viewBox: '0 0 24 24' }, [
            h('circle', { class: 'opacity-25', cx: '12', cy: '12', r: '10', stroke: 'currentColor', 'stroke-width': '4' }),
            h('path', { class: 'opacity-75', fill: 'currentColor', d: 'M4 12a8 8 0 018-8v8z' }),
          ]) : null,
          isCalculating ? 'Calculating…' : 'Calculate'
        ])
      ])
    },
  }),
]

const table = useVueTable({
  get data() {
    return contracts.value
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
})
</script>

<template>
  <AppLayout>
    <div class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-slate-900">Contracts</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your energy supply contracts.</p>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div v-if="noActiveFormulaError" class="mb-4 flex items-start justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <span>No active formula version — please activate one first.</span>
        <button type="button" class="ml-4 shrink-0 text-amber-600 hover:text-amber-900" @click="noActiveFormulaError = false">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>



      <DataTable
        :table="table"
        :is-loading="isLoading"
        :is-fetching="isFetching"
        :pagination="pagination"
        v-model:page="page"
        empty-message="No contracts found."
      />
    </div>
  </AppLayout>
</template>
