<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { FormulaVersion } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{ formulaVersions: FormulaVersion[] }>()

const search = ref('')

const filtered = computed(() =>
    props.formulaVersions.filter((f) =>
        f.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
)

function activate(id: number) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    fetch(`/api/v1/formula-versions/${id}/activate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
    }).then(() => router.reload({ only: ['formulaVersions'] }))
}
</script>

<template>
    <div>
        <Head title="Formula Versions" />

        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">Formula Versions</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Manage and version your commission calculation formulas
                        </p>
                    </div>
                    <Link
                        href="/formula-versions/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        New Formula
                    </Link>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-4">
                <div class="relative max-w-xs">
                    <svg
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"
                        />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search formulas…"
                        class="block w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Version
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Description
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Created
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr
                            v-for="fv in filtered"
                            :key="fv.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td
                                class="whitespace-nowrap px-6 py-4 font-mono text-sm font-medium text-slate-500"
                            >
                                v{{ fv.version_number }}
                            </td>
                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                            >
                                <Link
                                    :href="`/formula-versions/${fv.id}`"
                                    class="hover:text-blue-600 hover:underline"
                                >
                                    {{ fv.name }}
                                </Link>
                            </td>
                            <td class="max-w-xs truncate px-6 py-4 text-sm text-slate-500">
                                {{ fv.description ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    :class="
                                        fv.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                >
                                    <span
                                        v-if="fv.is_active"
                                        class="mr-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    />
                                    {{ fv.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                {{ new Date(fv.created_at).toLocaleDateString() }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <Link
                                        :href="`/formula-versions/${fv.id}`"
                                        class="font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        View
                                    </Link>
                                    <button
                                        type="button"
                                        :disabled="fv.is_active"
                                        :class="
                                            fv.is_active
                                                ? 'cursor-not-allowed text-slate-300'
                                                : 'font-medium text-emerald-600 hover:text-emerald-800'
                                        "
                                        class="text-sm transition-colors"
                                        @click="activate(fv.id)"
                                    >
                                        Activate
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg
                                        class="h-10 w-10 text-slate-300"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <p class="text-sm text-slate-500">
                                        {{
                                            search
                                                ? 'No formulas match your search.'
                                                : 'No formula versions yet.'
                                        }}
                                    </p>
                                    <Link
                                        v-if="!search"
                                        href="/formula-versions/create"
                                        class="text-sm font-medium text-blue-600 hover:underline"
                                    >
                                        Create your first formula →
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
