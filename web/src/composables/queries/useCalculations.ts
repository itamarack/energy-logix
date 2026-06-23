import { useQuery } from '@tanstack/vue-query'
import { calculationsApi } from '@/api/calculations'

import { type Ref } from 'vue'

export const calculationKeys = {
  all: ['calculations'] as const,
  list: (page: Ref<number>) => [...calculationKeys.all, { page }] as const,
  detail: (id: number) => [...calculationKeys.all, id] as const,
}

export function useCalculations(page: Ref<number>) {
  return useQuery({
    queryKey: calculationKeys.list(page),
    queryFn: () => calculationsApi.list(page.value),
  })
}

export function useCalculation(id: number) {
  return useQuery({
    queryKey: calculationKeys.detail(id),
    queryFn: () => calculationsApi.get(id),
  })
}
