<script setup lang="ts" generic="TData">
import { computed } from 'vue'
import { FlexRender, type Table } from '@tanstack/vue-table'
import type { PaginationMeta } from '@/types'

const props = defineProps<{
  table: Table<TData>
  isLoading: boolean
  isFetching: boolean
  pagination?: PaginationMeta
  page: number
  emptyMessage?: string
}>()

const emit = defineEmits<{
  (e: 'update:page', value: number): void
  (e: 'row-click', row: TData): void
}>()

const pagesToDisplay = computed(() => {
  if (!props.pagination) return []
  const range: number[] = []
  for (let i = 1; i <= props.pagination.last_page; i++) {
    range.push(i)
  }
  return range
})
</script>

<template>
  <div class="premium-card relative">
    <div v-if="isFetching && !isLoading" class="absolute inset-0 z-10 bg-white/50 backdrop-blur-[1px] flex items-center justify-center rounded-2xl">
      <svg class="h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200/70">
      <thead>
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <th v-for="header in headerGroup.headers" :key="header.id" class="px-6 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
            <FlexRender
              v-if="!header.isPlaceholder"
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 bg-white">
        <template v-if="isLoading">
          <tr v-for="i in 5" :key="i">
            <td :colspan="table.getAllColumns().length" class="px-6 py-5">
              <div class="flex items-center gap-4">
                <div class="h-4 w-40 animate-pulse rounded-full bg-slate-200/50" />
                <div class="h-4 w-32 animate-pulse rounded-full bg-slate-200/50" />
              </div>
            </td>
          </tr>
        </template>
        <template v-else-if="table.getRowModel().rows.length === 0">
          <tr>
            <td :colspan="table.getAllColumns().length" class="px-6 py-16 text-center text-sm text-slate-500">
              {{ emptyMessage ?? 'No records found.' }}
            </td>
          </tr>
        </template>
        <template v-else>
          <tr 
            v-for="row in table.getRowModel().rows" 
            :key="row.id" 
            class="transition-colors hover:bg-slate-50"
            :class="{ 'cursor-pointer': $attrs.onRowClick }"
            @click="emit('row-click', row.original)"
          >
            <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 first:font-medium first:text-slate-900">
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </template>
      </tbody>
      </table>
    </div>

    <div v-if="pagination" class="flex items-center justify-between border-t border-slate-100 bg-white px-6 py-3">
      <div class="flex flex-1 justify-between sm:hidden">
        <button @click="emit('update:page', page - 1)" :disabled="page === 1" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
        <button @click="emit('update:page', page + 1)" :disabled="page === pagination.last_page" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
      </div>
      <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-slate-700">
            Showing
            <span class="font-medium">{{ pagination.from }}</span>
            to
            <span class="font-medium">{{ pagination.to }}</span>
            of
            <span class="font-medium">{{ pagination.total }}</span>
            results
          </p>
        </div>
        <div>
          <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
            <button
              @click="emit('update:page', page - 1)"
              :disabled="page === 1"
              class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
              </svg>
            </button>
            <template v-for="p in pagesToDisplay" :key="p">
              <button
                @click="emit('update:page', p)"
                :aria-current="p === page ? 'page' : undefined"
                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-slate-300 focus:z-20 focus:outline-offset-0"
                :class="[
                  p === page 
                    ? 'z-10 bg-slate-900 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900'
                    : 'text-slate-900 hover:bg-slate-50'
                ]"
              >
                {{ p }}
              </button>
            </template>
            <button
              @click="emit('update:page', page + 1)"
              :disabled="page === pagination.last_page"
              class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>
