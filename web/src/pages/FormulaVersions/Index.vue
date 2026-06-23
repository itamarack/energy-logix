<script setup lang="ts">
import { computed, ref, h, defineComponent } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import DataTable from '@/components/DataTable.vue'
import ActionMenu from '@/components/ActionMenu.vue'
import { useFormulaVersions, useActivateFormulaVersion, useDeactivateFormulaVersion } from '@/composables/queries/useFormulaVersions'
import { FORMULA_VERSION_ROUTES } from '@/routes/paths/formulaVersionRoutes'
import type { FormulaVersion } from '@/types'
import { RouterLink } from 'vue-router'
import {
  getCoreRowModel,
  useVueTable,
  createColumnHelper,
} from '@tanstack/vue-table'

document.title = 'Formula Versions — EnergyLogix'

const page = ref(1)

const { data: formulaVersionData, isLoading, isFetching } = useFormulaVersions(page)
const { mutate: activate } = useActivateFormulaVersion()
const { mutate: deactivate } = useDeactivateFormulaVersion()

const formulaVersions = computed(() => formulaVersionData.value?.data ?? [])
const pagination = computed(() => formulaVersionData.value?.meta)

const columnHelper = createColumnHelper<FormulaVersion>()

const columns = [
  columnHelper.accessor('version_number', {
    header: 'Version',
    cell: info => h('span', { class: 'font-mono text-sm font-medium text-slate-500' }, `v${info.getValue()}`),
  }),
  columnHelper.accessor('name', {
    header: 'Name',
    cell: info => h(RouterLink, { 
      to: FORMULA_VERSION_ROUTES.SHOW(info.row.original.id), 
      class: 'text-sm font-medium text-slate-900 hover:text-blue-600 hover:underline' 
    }, () => info.getValue()),
  }),
  columnHelper.accessor('description', {
    header: 'Description',
    cell: info => h('span', { class: 'max-w-xs truncate text-sm text-slate-500 block' }, info.getValue() ?? '—'),
  }),
  columnHelper.accessor('is_active', {
    header: 'Status',
    cell: info => {
      const isActive = info.getValue()
      if (isActive) {
        return h('span', { class: 'inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-600/20' }, [
          h('span', { class: 'mr-2 relative flex h-2 w-2' }, [
            h('span', { class: 'absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75' }),
            h('span', { class: 'relative inline-flex h-2 w-2 rounded-full bg-emerald-500' })
          ]),
          'Active'
        ])
      }
      return h('span', { class: 'inline-flex items-center rounded-full bg-slate-100/80 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600' }, 'Inactive')
    },
  }),
  columnHelper.accessor('created_at', {
    header: 'Created',
    cell: info => h('span', { class: 'text-sm text-slate-500' }, new Date(info.getValue()).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })),
  }),
  columnHelper.display({
    id: 'actions',
    header: () => h('div', { class: 'text-right' }, ''),
    cell: props => {
      const fv = props.row.original
      return h(
        defineComponent({
          setup() {
            return () => h(ActionMenu, null, {
              default: () => [
                h(RouterLink, {
                  to: FORMULA_VERSION_ROUTES.SHOW(fv.id),
                  class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50'
                }, () => [
                  h('svg', { class: 'h-4 w-4 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z' }),
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
                  ]),
                  'View'
                ]),
                h(RouterLink, {
                  to: FORMULA_VERSION_ROUTES.EDIT(fv.id),
                  class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50'
                }, () => [
                  h('svg', { class: 'h-4 w-4 text-slate-400', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125' })
                  ]),
                  'Edit'
                ]),
                h('div', { class: 'my-1 h-px bg-slate-100' }),
                fv.is_active
                  ? h('button', {
                      type: 'button',
                      class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50',
                      onClick: () => deactivate(fv.id)
                    }, [
                      h('svg', { class: 'h-4 w-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
                      ]),
                      'Deactivate'
                    ])
                  : h('button', {
                      type: 'button',
                      class: 'flex w-full items-center gap-2.5 px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50',
                      onClick: () => activate(fv.id)
                    }, [
                      h('svg', { class: 'h-4 w-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z' })
                      ]),
                      'Activate'
                    ])
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
    return formulaVersions.value
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
})
</script>

<template>
  <AppLayout>
    <PageHeader 
      title="Formula Versions" 
      description="Manage and version your commission calculation formulas"
    >
      <template #actions>
        <RouterLink
          :to="FORMULA_VERSION_ROUTES.CREATE"
          class="premium-button"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          New Formula
        </RouterLink>
      </template>
    </PageHeader>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <DataTable
        :table="table"
        :is-loading="isLoading"
        :is-fetching="isFetching"
        :pagination="pagination"
        v-model:page="page"
        empty-message="No formula versions yet."
      />
    </div>
  </AppLayout>
</template>
