<script setup lang="ts">
import { computed, ref, h, defineComponent } from 'vue'
import { useRouter } from 'vue-router'
import { exportCalculationCsv } from '@/utils/exportCalculationCsv'
import { formatCurrency } from '@/utils/formatCurrency'
import { formatDate } from '@/utils/formatDate'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import DataTable from '@/components/DataTable.vue'
import ActionMenu from '@/components/ActionMenu.vue'
import { useCalculations } from '@/composables/queries/useCalculations'
import { CALCULATION_ROUTES } from '@/routes/paths/calculationRoutes'
import type { CommissionCalculation } from '@/types'
import {
  getCoreRowModel,
  useVueTable,
  createColumnHelper,
} from '@tanstack/vue-table'

document.title = 'Calculations — EnergyLogix'

const router = useRouter()
const page = ref(1)

const { data: calculationsData, isLoading, isFetching } = useCalculations(page)

const calculations = computed(() => calculationsData.value?.data ?? [])
const pagination = computed(() => calculationsData.value?.meta)



const columnHelper = createColumnHelper<CommissionCalculation>()

const columns = [
  columnHelper.accessor(row => row.formula_version, {
    id: 'formula_version',
    header: 'Formula Version',
    cell: info => {
      const version = info.getValue()
      return h('div', { class: 'flex items-center' }, [
        h('span', { class: 'text-slate-900' }, version?.name ?? '—'),
        h('span', { class: 'ml-2 inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-600 ring-1 ring-inset ring-slate-500/10' }, `v${version?.version_number}`)
      ])
    },
  }),
  columnHelper.accessor(row => row.contract?.name, {
    id: 'contract',
    header: 'Contract',
    cell: info => info.getValue() ?? '—',
  }),
  columnHelper.accessor('result', {
    header: 'Commission Result',
    cell: info => h('span', { class: 'font-bold text-slate-900' }, formatCurrency(info.getValue())),
  }),
  columnHelper.accessor('calculated_at', {
    header: 'Calculated At',
    cell: info => h('span', { class: 'text-slate-500' }, formatDate(info.getValue())),
  }),
  columnHelper.display({
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, ''),
    cell: props => {
      const calc = props.row.original
      return h(
        defineComponent({
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
                  'View',
                ]),
                h('div', { class: 'my-1 h-px bg-slate-100' }),
                h('button', {
                  type: 'button',
                  class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50',
                  onClick: () => exportCalculationCsv(calc),
                }, [
                  h('svg', { class: 'h-4 w-4 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3' }),
                  ]),
                  'Export CSV',
                ]),
              ]
            })
          }
        })
      )
    },
  }),
]

const table = useVueTable({
  get data() {
    return calculations.value
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
})
</script>

<template>
  <AppLayout>
    <PageHeader 
      title="Commission Calculations" 
      description="Immutable audit trail of all commission records." 
    />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <DataTable
        :table="table"
        :is-loading="isLoading"
        :is-fetching="isFetching"
        :pagination="pagination"
        v-model:page="page"
        empty-message="No calculations yet. Go to Contracts and click Calculate."
      />
    </div>
  </AppLayout>
</template>
