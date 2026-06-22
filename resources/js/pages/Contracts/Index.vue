<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { Contract } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{ contracts: Contract[] }>()

const search = ref('')
const lastCommissions = ref<Record<number, number>>({})
const loadingIds = ref<Set<number>>(new Set())
const noActiveFormulaError = ref(false)

const filtered = computed(() =>
    props.contracts.filter((c) =>
        c.name.toLowerCase().includes(search.value.toLowerCase()),
    ),
)

async function calculate(contractId: number) {
    noActiveFormulaError.value = false
    loadingIds.value = new Set([...loadingIds.value, contractId])

    try {
        const response = await fetch(`/api/v1/contracts/${contractId}/calculate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ??
                    '',
                Accept: 'application/json',
            },
        })

        if (response.status === 422) {
            noActiveFormulaError.value = true
            return
        }

        const data = await response.json()
        lastCommissions.value[contractId] = data.data?.result ?? data.result
    } finally {
        const next = new Set(loadingIds.value)
        next.delete(contractId)
        loadingIds.value = next
    }
}

function formatCurrency(value: number): string {
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}
</script>

<template>
    <div>
        <Head title="Contracts" />

        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-semibold text-slate-900">Contracts</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ contracts.length }} energy contract{{ contracts.length === 1 ? '' : 's' }}
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                v-if="noActiveFormulaError"
                class="mb-4 flex items-start justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
            >
                <span>No active formula version — please activate one first.</span>
                <button
                    type="button"
                    class="ml-4 shrink-0 text-amber-600 hover:text-amber-900"
                    aria-label="Dismiss"
                    @click="noActiveFormulaError = false"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

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
                        placeholder="Search contracts…"
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
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Annual Usage (kWh)
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Contract Value ($)
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Length (months)
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Risk Score
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500"
                            >
                                Last Commission
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
                            v-for="contract in filtered"
                            :key="contract.id"
                            class="transition-colors hover:bg-slate-50"
                        >
                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                            >
                                {{ contract.name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ contract.annual_usage.toLocaleString('en-US') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ contract.contract_value.toLocaleString('en-US') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ contract.contract_length.toLocaleString('en-US') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ contract.risk_score.toLocaleString('en-US') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span
                                    v-if="lastCommissions[contract.id] !== undefined"
                                    class="font-semibold text-blue-700"
                                >
                                    {{ formatCurrency(lastCommissions[contract.id]) }}
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition-colors hover:text-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="loadingIds.has(contract.id)"
                                    @click="calculate(contract.id)"
                                >
                                    <svg
                                        v-if="loadingIds.has(contract.id)"
                                        class="h-3.5 w-3.5 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8v8z"
                                        />
                                    </svg>
                                    {{ loadingIds.has(contract.id) ? 'Calculating…' : 'Calculate' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <p class="text-sm text-slate-500">
                                    {{
                                        search
                                            ? 'No contracts match your search.'
                                            : 'No contracts found.'
                                    }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
