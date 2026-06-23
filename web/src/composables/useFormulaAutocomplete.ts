import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Tribute from 'tributejs'
import 'tributejs/dist/tribute.css'
import { useFormulaVariables } from '@/composables/queries/useFormulaVariables'

type VarItem = { name: string; description: string }

export function useFormulaAutocomplete(
  getFormVariables: () => Array<{ name: string; expression: string }>
) {
  const expressionEl = ref<HTMLTextAreaElement | null>(null)
  const varExprEls = ref<Record<number, HTMLInputElement | null>>({})

  const { data: baseVariables, isLoading: isVariablesLoading } = useFormulaVariables()

  const mainExpressionVariables = computed(() => [
    ...(baseVariables.value ?? []),
    ...getFormVariables()
      .filter((v) => v.name.trim() !== '')
      .map((v) => ({ name: v.name, description: 'Intermediate variable' })),
  ])

  function variablesForRow(index: number) {
    return [
      ...(baseVariables.value ?? []),
      ...getFormVariables()
        .slice(0, index)
        .filter((v) => v.name.trim() !== '')
        .map((v) => ({ name: v.name, description: 'Intermediate variable' })),
    ]
  }

  const tributeInstances: Array<{ instance: Tribute<VarItem>; el: HTMLElement }> = []

  function detachAll() {
    tributeInstances.forEach(({ instance, el }) => {
      try {
        instance.detach(el)
      } catch {
        /* ignored */
      }
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

  watch(
    () => getFormVariables().length,
    reattachTribute
  )

  return {
    refs: {
      expressionEl,
      varExprEls,
    },
    reattachTribute,
    isVariablesLoading,
    baseVariables,
  }
}
