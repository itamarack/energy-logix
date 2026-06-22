import { useQuery } from '@tanstack/vue-query'
import { calculationsApi } from '@/api/calculations'

export const calculationKeys = {
  all: ['calculations'] as const,
  detail: (id: number) => [...calculationKeys.all, id] as const,
}

export function useCalculations() {
  return useQuery({
    queryKey: calculationKeys.all,
    queryFn: calculationsApi.list,
  })
}

export function useCalculation(id: number) {
  return useQuery({
    queryKey: calculationKeys.detail(id),
    queryFn: () => calculationsApi.get(id),
  })
}
