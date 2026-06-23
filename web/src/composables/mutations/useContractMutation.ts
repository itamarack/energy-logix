import { ref } from 'vue'
import { useCalculateContract } from '@/composables/queries/useContracts'

export function useContractMutation() {
  const { mutateAsync: calculateContractMutation, isPending } = useCalculateContract()
  
  const loadingIds = ref<Set<number>>(new Set())
  const noActiveFormulaError = ref(false)

  async function calculate(contractId: number) {
    noActiveFormulaError.value = false
    loadingIds.value.add(contractId)
    
    try {
      await calculateContractMutation(contractId)
    } catch (err: unknown) {
      const e = err as Error & { status?: number }
      if (e.status === 422) {
        noActiveFormulaError.value = true
      }
    } finally {
      loadingIds.value.delete(contractId)
    }
  }

  function dismissError() {
    noActiveFormulaError.value = false
  }

  return {
    calculate,
    isPending,
    loadingIds,
    noActiveFormulaError,
    dismissError,
  }
}
