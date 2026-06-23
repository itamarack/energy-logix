<script setup lang="ts">
import { computed, ref, h, defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import DataTable from '@/components/DataTable.vue'
import ActionMenu from '@/components/ActionMenu.vue'
import { formatCurrency } from '@/utils/formatCurrency'
import { useContracts, useCalculateContract } from '@/composables/queries/useContracts'
import { CONTRACT_ROUTES } from '@/routes/paths/contractRoutes'
import type { Contract } from '@/types'
import { RouterLink, useRouter } from 'vue-router'
import {
  getCoreRowModel,
  useVueTable,
  createColumnHelper,
} from '@tanstack/vue-table'

document.title = 'Contracts — EnergyLogix'

const page = ref(1)

const loadingIds = ref<Set<number>>(new Set())
const noActiveFormulaError = ref(false)

const router = useRouter()

const { data: contractData, isLoading, isFetching } = useContracts(page)
const { mutateAsync: calculateContract } = useCalculateContract()

const contracts = computed(() => contractData.value?.data ?? [])
const pagination = computed(() => contractData.value?.meta)

async function calculate(contractId: number) {
  noActiveFormulaError.value = false
  loadingIds.value = new Set([...loadingIds.value, contractId])
  try {
    await calculateContract(contractId)
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


const columnHelper = createColumnHelper<Contract>()

const columns = [
  columnHelper.accessor('name', {
    header: 'Name',
    cell: info => h(RouterLink, {
      to: CONTRACT_ROUTES.SHOW(info.row.original.id),
      class: 'text-sm font-medium text-slate-900 hover:text-blue-600 hover:underline',
    }, () => info.getValue()),
  }),
  columnHelper.accessor('annual_usage', {
    header: 'Annual Usage (kWh)',
    cell: info => info.getValue().toLocaleString('en-US'),
  }),
  columnHelper.accessor('contract_value', {
    header: 'Contract Value',
    cell: info => formatCurrency(info.getValue()),
  }),
  columnHelper.accessor('contract_length', {
    header: 'Length (months)',
    cell: info => info.getValue().toLocaleString('en-US'),
  }),
  columnHelper.accessor('risk_score', {
    header: 'Risk Score',
    cell: info => h('span', { class: 'inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10' }, `${info.getValue().toLocaleString('en-US')} / 10`),
  }),
  columnHelper.accessor('last_commission_result', {
    header: 'Last Commission',
    cell: info => {
      const commission = info.getValue()
      if (commission != null) {
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
      
      return h(defineComponent({
        setup() {
          return () => h(ActionMenu, null, {
            default: () => [
              h('button', {
                type: 'button',
                class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50',
                onClick: () => router.push(CONTRACT_ROUTES.SHOW(contractId)),
              }, [
                h('svg', { class: 'h-4 w-4 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z' }),
                  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' }),
                ]),
                'View Details',
              ]),
              h('button', {
                type: 'button',
                disabled: isCalculating,
                class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed',
                onClick: () => calculate(contractId),
              }, [
                isCalculating 
                  ? h('svg', { class: 'h-4 w-4 shrink-0 animate-spin text-slate-400', fill: 'none', viewBox: '0 0 24 24' }, [
                      h('circle', { class: 'opacity-25', cx: '12', cy: '12', r: '10', stroke: 'currentColor', 'stroke-width': '4' }),
                      h('path', { class: 'opacity-75', fill: 'currentColor', d: 'M4 12a8 8 0 018-8v8z' }),
                    ])
                  : h('svg', { class: 'h-4 w-4 shrink-0 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.82m2.56-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z' })
                    ]),
                h('span', { class: 'truncate whitespace-nowrap' }, isCalculating ? 'Calculating…' : 'Calculate'),
              ]),
            ]
          })
        }
      }))
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
    <PageHeader 
      title="Contracts" 
      description="Manage your energy supply contracts." 
    />

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
