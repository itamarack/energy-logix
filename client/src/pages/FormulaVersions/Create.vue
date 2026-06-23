<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation } from '@tanstack/vue-query'
import Tribute from 'tributejs'
import 'tributejs/dist/tribute.css'
import AppLayout from '@/layouts/AppLayout.vue'
import { formulaVersionsApi } from '@/api/formulaVersions'
import { FORMULA_VERSION_ROUTES } from '@/routes/paths/formulaVersionRoutes'

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
  onSuccess: () => router.push(FORMULA_VERSION_ROUTES.INDEX),
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
          :to="FORMULA_VERSION_ROUTES.INDEX"
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
            <div class="premium-card p-6 sm:p-8">
              <!-- Name -->
              <div class="mb-6">
                <label for="name" class="block text-[13px] font-bold uppercase tracking-widest text-slate-500">
                  Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  :class="['mt-2 block w-full rounded-xl border bg-white px-4 py-3 text-[15px] font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 transition-all duration-200', errors.name ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-slate-200/80 focus:border-indigo-500 focus:ring-indigo-500/20']"
                  placeholder="e.g. Standard Commission v2"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
              </div>

              <!-- Description -->
              <div class="mb-8">
                <label for="description" class="block text-[13px] font-bold uppercase tracking-widest text-slate-500">Description</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-2 block w-full rounded-xl border border-slate-200/80 bg-white px-4 py-3 text-[15px] text-slate-700 placeholder:text-slate-400 transition-all duration-200 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  placeholder="Optional description of this formula"
                />
              </div>

              <!-- Expression -->
              <div class="mb-8">
                <label for="expression" class="block text-[13px] font-bold uppercase tracking-widest text-slate-500 mb-2">
                  Expression <span class="text-red-500">*</span>
                </label>
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white">
                  <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-4 py-2">
                    <span class="text-[11px] font-mono text-slate-500">formula.expr</span>
                  </div>
                  <textarea
                    id="expression"
                    ref="expressionEl"
                    v-model="form.expression"
                    rows="4"
                    :class="['ide-textarea border-0 rounded-none focus:ring-0', errors.expression ? 'text-red-600' : '']"
                    placeholder="e.g. AnnualUsage * 0.05 + ContractValue * 0.01"
                    spellcheck="false"
                  />
                </div>
                <p v-if="errors.expression" class="mt-1 text-sm text-red-600">{{ errors.expression }}</p>
              </div>

              <!-- Intermediate Variables -->
              <div>
                <div class="mb-4 flex items-center justify-between">
                  <label class="block text-[13px] font-bold uppercase tracking-widest text-slate-500">Intermediate Variables</label>
                  <button type="button" class="text-[13px] font-bold text-indigo-600 transition-colors hover:text-indigo-800" @click="addVariable">
                    + Add Variable
                  </button>
                </div>

                <p v-if="form.variables.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 py-8 text-center text-sm font-medium text-slate-500">
                  No intermediate variables added yet.
                </p>

                <div v-for="(variable, index) in form.variables" :key="index" class="group mb-4 space-y-4 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition-all duration-200 hover:border-indigo-200 hover:bg-white">
                  <div class="flex items-start gap-4">
                    <div class="flex-1 space-y-4">
                      <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-400">Variable Name</label>
                        <input
                          v-model="variable.name"
                          type="text"
                          class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-[13px] font-bold text-indigo-700 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all"
                          placeholder="e.g. BaseCommission"
                        />
                      </div>
                      <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-widest text-slate-400">Expression</label>
                        <input
                          v-model="variable.expression"
                          type="text"
                          :ref="(el) => { varExprEls[index] = el as HTMLInputElement }"
                          class="ide-textarea !py-2 !text-[13px]"
                          placeholder="Expression"
                          spellcheck="false"
                        />
                      </div>
                    </div>
                    <button type="button" class="mt-6 text-slate-400 opacity-50 transition-all hover:scale-110 hover:text-red-500 hover:opacity-100 group-hover:opacity-100" aria-label="Remove variable" @click="removeVariable(index)">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>

                <p v-if="errors.variables" class="mt-1 text-sm text-red-600">{{ errors.variables }}</p>
              </div>
            </div>

            <div class="mt-6 flex items-center gap-4">
              <button
                type="submit"
                :disabled="isPending"
                class="premium-button"
              >
                {{ isPending ? 'Saving…' : 'Save Formula' }}
              </button>
              <RouterLink :to="FORMULA_VERSION_ROUTES.INDEX" class="text-[13px] font-bold uppercase tracking-widest text-slate-500 transition-colors hover:text-slate-900">
                Cancel
              </RouterLink>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="mt-6 lg:col-span-1 lg:mt-0">
            <div class="premium-card sticky top-24 p-6">
              <h2 class="mb-1 text-[13px] font-bold uppercase tracking-widest text-slate-500">Available Variables</h2>
              <p class="mb-6 text-[13px] text-slate-400">Type <strong class="text-indigo-500">@</strong> in any expression field to insert a variable.</p>
              <ul class="space-y-5">
                <li class="flex items-start gap-4">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-500 ring-1 ring-amber-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                  </div>
                  <div>
                    <p class="font-mono text-[13px] font-bold text-indigo-700">AnnualUsage</p>
                    <p class="text-[12px] font-medium text-slate-500">Annual usage in kWh</p>
                  </div>
                </li>
                <li class="flex items-start gap-4">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 ring-1 ring-emerald-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <div>
                    <p class="font-mono text-[13px] font-bold text-indigo-700">ContractValue</p>
                    <p class="text-[12px] font-medium text-slate-500">Total contract value in $</p>
                  </div>
                </li>
                <li class="flex items-start gap-4">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-500 ring-1 ring-blue-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                  </div>
                  <div>
                    <p class="font-mono text-[13px] font-bold text-indigo-700">ContractLength</p>
                    <p class="text-[12px] font-medium text-slate-500">Duration in months</p>
                  </div>
                </li>
                <li class="flex items-start gap-4">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-500 ring-1 ring-rose-500/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                  </div>
                  <div>
                    <p class="font-mono text-[13px] font-bold text-indigo-700">RiskScore</p>
                    <p class="text-[12px] font-medium text-slate-500">Risk factor 1–10</p>
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
