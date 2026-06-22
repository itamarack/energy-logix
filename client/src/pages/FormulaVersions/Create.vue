<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation } from '@tanstack/vue-query'
import Tribute from 'tributejs'
import 'tributejs/dist/tribute.css'
import AppLayout from '@/layouts/AppLayout.vue'
import { formulaVersionsApi } from '@/api/formulaVersions'

document.title = 'New Formula Version — EnergyLogix'

const router = useRouter()

const form = ref({
  name: '',
  description: '',
  expression: '',
  variables: [] as Array<{ name: string; expression: string }>,
})

const errors = ref<Record<string, string>>({})
const expressionEl = ref<HTMLTextAreaElement | null>(null)
const varExprEls = ref<Record<number, HTMLInputElement | null>>({})

const BASE_VARIABLES = [
  { name: 'AnnualUsage', description: 'Annual usage in kWh' },
  { name: 'ContractValue', description: 'Total contract value in $' },
  { name: 'ContractLength', description: 'Duration in months' },
  { name: 'RiskScore', description: 'Risk factor 1–10' },
]

const mainExpressionVariables = computed(() => [
  ...BASE_VARIABLES,
  ...form.value.variables
    .filter((v) => v.name.trim() !== '')
    .map((v) => ({ name: v.name, description: 'Intermediate variable' })),
])

function variablesForRow(index: number) {
  return [
    ...BASE_VARIABLES,
    ...form.value.variables
      .filter((v, i) => i !== index && v.name.trim() !== '')
      .map((v) => ({ name: v.name, description: 'Intermediate variable' })),
  ]
}

type VarItem = { name: string; description: string }
const tributeInstances: Array<{ instance: Tribute<VarItem>; el: HTMLElement }> = []

function detachAll() {
  tributeInstances.forEach(({ instance, el }) => {
    try { instance.detach(el) } catch { /* ignored */ }
  })
  tributeInstances.length = 0
}

function createTribute(getItems: () => VarItem[]) {
  return new Tribute<VarItem>({
    trigger: '@',
    lookup: 'name',
    fillAttr: 'name',
    values: (text, cb) => cb(getItems().filter((v) => v.name.toLowerCase().startsWith(text.toLowerCase()))),
    menuItemTemplate: (item) =>
      `<span class="tribute-name">${item.original.name}</span><span class="tribute-desc">${item.original.description}</span>`,
    noMatchTemplate: () => '<span class="tribute-none">No variables match</span>',
    selectTemplate: (item) => (item ? item.original.name : ''),
    allowSpaces: false,
    autocompleteMode: false,
    replaceTextSuffix: '',
  })
}

function attachTributeToEl(el: HTMLElement, getItems: () => VarItem[]) {
  const instance = createTribute(getItems)
  instance.attach(el)
  tributeInstances.push({ instance, el })
}

async function reattachTribute() {
  await nextTick()
  detachAll()
  if (expressionEl.value) {
    attachTributeToEl(expressionEl.value, () => mainExpressionVariables.value)
  }
  Object.entries(varExprEls.value).forEach(([indexStr, el]) => {
    if (el) {
      const index = parseInt(indexStr)
      attachTributeToEl(el, () => variablesForRow(index))
    }
  })
}

onMounted(reattachTribute)
onBeforeUnmount(detachAll)
watch(() => form.value.variables.length, reattachTribute)

function addVariable() {
  form.value.variables.push({ name: '', expression: '' })
}

function removeVariable(index: number) {
  form.value.variables.splice(index, 1)
  delete varExprEls.value[index]
}

const { mutate: submit, isPending } = useMutation({
  mutationFn: () =>
    formulaVersionsApi.create({
      name: form.value.name,
      description: form.value.description || undefined,
      expression: form.value.expression,
      variables: form.value.variables,
    }),
  onSuccess: () => router.push('/formula-versions'),
  onError: (err: unknown) => {
    const e = err as Error & { data?: { message?: string; errors?: Record<string, string[]> } }
    errors.value = {}
    if (e.data?.errors) {
      Object.entries(e.data.errors).forEach(([field, msgs]) => {
        errors.value[field] = msgs[0]
      })
    } else if (e.data?.message) {
      errors.value.expression = e.data.message
    }
  },
})

function handleSubmit() {
  submit()
}
</script>

<template>
  <AppLayout>
    <div class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <RouterLink
          to="/formula-versions"
          class="mb-3 inline-flex items-center text-sm font-medium text-slate-500 hover:text-blue-600"
        >
          <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Formula Versions
        </RouterLink>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">New Formula Version</h1>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <form @submit.prevent="handleSubmit">
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
          <div class="lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <!-- Name -->
              <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-slate-700">
                  Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  :class="['mt-1 block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2', errors.name ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20']"
                  placeholder="e.g. Standard Commission v2"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
              </div>

              <!-- Description -->
              <div class="mb-5">
                <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                  placeholder="Optional description of this formula"
                />
              </div>

              <!-- Expression -->
              <div class="mb-5">
                <label for="expression" class="block text-sm font-medium text-slate-700">
                  Expression <span class="text-red-500">*</span>
                </label>
                <textarea
                  id="expression"
                  ref="expressionEl"
                  v-model="form.expression"
                  rows="4"
                  :class="['mt-1 block w-full rounded-lg border px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2', errors.expression ? 'border-red-400 bg-slate-950 text-emerald-400 placeholder:text-slate-600 focus:border-red-400 focus:ring-red-400/20' : 'border-slate-700 bg-slate-950 text-emerald-400 placeholder:text-slate-600 focus:border-blue-500 focus:ring-blue-500/20']"
                  placeholder="e.g. AnnualUsage * 0.05 + ContractValue * 0.01"
                />
                <p v-if="errors.expression" class="mt-1 text-sm text-red-600">{{ errors.expression }}</p>
              </div>

              <!-- Intermediate Variables -->
              <div>
                <div class="mb-3 flex items-center justify-between">
                  <label class="block text-sm font-medium text-slate-700">Intermediate Variables</label>
                  <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-800" @click="addVariable">
                    + Add Variable
                  </button>
                </div>

                <p v-if="form.variables.length === 0" class="rounded-lg border border-dashed border-slate-200 py-4 text-center text-sm text-slate-400">
                  No intermediate variables added yet.
                </p>

                <div v-for="(variable, index) in form.variables" :key="index" class="mb-3 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                  <div class="flex items-center gap-3">
                    <div class="flex-1">
                      <label class="mb-1 block text-xs font-medium text-slate-500">Variable Name</label>
                      <input
                        v-model="variable.name"
                        type="text"
                        class="block w-full rounded-lg border border-slate-200 px-3 py-2 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        placeholder="e.g. BaseCommission"
                      />
                    </div>
                    <button type="button" class="mt-5 text-slate-400 transition-colors hover:text-red-500" aria-label="Remove variable" @click="removeVariable(index)">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Expression</label>
                    <input
                      v-model="variable.expression"
                      type="text"
                      :ref="(el) => { varExprEls[index] = el as HTMLInputElement }"
                      class="block w-full rounded-lg border border-slate-200 px-3 py-2 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                      placeholder="Expression"
                    />
                  </div>
                </div>

                <p v-if="errors.variables" class="mt-1 text-sm text-red-600">{{ errors.variables }}</p>
              </div>
            </div>

            <div class="mt-6 flex items-center gap-4">
              <button
                type="submit"
                :disabled="isPending"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 disabled:opacity-50"
              >
                {{ isPending ? 'Saving…' : 'Save Formula' }}
              </button>
              <RouterLink to="/formula-versions" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                Cancel
              </RouterLink>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="mt-6 lg:col-span-1 lg:mt-0">
            <div class="sticky top-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
              <h2 class="mb-1 text-sm font-semibold text-slate-700">Available Variables</h2>
              <p class="mb-4 text-xs text-slate-400">Type <strong>@</strong> in any expression field to insert a variable.</p>
              <ul class="space-y-4">
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 text-lg">⚡</span>
                  <div>
                    <p class="font-mono text-sm font-semibold text-blue-700">AnnualUsage</p>
                    <p class="text-xs text-slate-500">Annual usage in kWh</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 text-lg">💰</span>
                  <div>
                    <p class="font-mono text-sm font-semibold text-blue-700">ContractValue</p>
                    <p class="text-xs text-slate-500">Total contract value in $</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 text-lg">📅</span>
                  <div>
                    <p class="font-mono text-sm font-semibold text-blue-700">ContractLength</p>
                    <p class="text-xs text-slate-500">Duration in months</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <span class="mt-0.5 text-lg">⚠️</span>
                  <div>
                    <p class="font-mono text-sm font-semibold text-blue-700">RiskScore</p>
                    <p class="text-xs text-slate-500">Risk factor 1–10</p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<style>
.tribute-container { border-radius:.5rem;border:1px solid #e2e8f0;box-shadow:0 10px 15px -3px rgb(0 0 0/.1),0 4px 6px -4px rgb(0 0 0/.1);background:white;z-index:9999;min-width:220px;overflow:hidden }
.tribute-container ul { list-style:none;margin:0;padding:0 }
.tribute-container ul li { display:flex;align-items:center;gap:.75rem;padding:.5rem .75rem;cursor:pointer;font-size:.875rem;transition:background-color .1s }
.tribute-container ul li.highlight,.tribute-container ul li:hover { background-color:#eff6ff;color:#1d4ed8 }
.tribute-name { font-family:ui-monospace,monospace;font-weight:600 }
.tribute-desc { font-size:.75rem;color:#94a3b8 }
.tribute-container ul li.highlight .tribute-desc,.tribute-container ul li:hover .tribute-desc { color:#93c5fd }
.tribute-none { display:block;padding:.5rem .75rem;font-size:.875rem;color:#94a3b8 }
</style>
