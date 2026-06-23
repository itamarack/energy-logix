import { useQuery } from '@tanstack/vue-query'
import { formulaVariablesApi } from '@/api/formulaVariables'

export const formulaVariableKeys = {
  all: ['formula-variables'] as const,
}

export function useFormulaVariables() {
  return useQuery({
    queryKey: formulaVariableKeys.all,
    queryFn: formulaVariablesApi.list,
    staleTime: 1000 * 60 * 60, // 1 hour
  })
}
