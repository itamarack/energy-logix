import { ref } from 'vue'

export type FormulaFormState = {
  name: string
  description: string
  expression: string
  variables: Array<{ name: string; expression: string }>
}

export function useFormulaForm() {
  const form = ref<FormulaFormState>({
    name: '',
    description: '',
    expression: '',
    variables: [],
  })

  const errors = ref<Record<string, string>>({})

  function addVariable() {
    form.value.variables.push({ name: '', expression: '' })
  }

  function removeVariable(index: number) {
    form.value.variables.splice(index, 1)
  }

  function parseErrors(err: unknown) {
    const e = err as Error & { data?: { message?: string; errors?: Record<string, string[]> } }
    errors.value = {}
    if (e.data?.errors) {
      Object.entries(e.data.errors).forEach(([field, msgs]) => {
        errors.value[field] = msgs[0]
      })
    } else if (e.data?.message) {
      errors.value.expression = e.data.message
    }
  }

  function clearErrors() {
    errors.value = {}
  }

  return {
    form,
    errors,
    addVariable,
    removeVariable,
    parseErrors,
    clearErrors,
  }
}
