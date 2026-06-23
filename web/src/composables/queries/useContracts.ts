import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { contractsApi } from '@/api/contracts'

import { type Ref } from 'vue'

export const contractKeys = {
  all: ['contracts'] as const,
  list: (page: Ref<number>) => [...contractKeys.all, { page }] as const,
  detail: (id: number) => [...contractKeys.all, id] as const,
  calculations: (id: number, page: Ref<number>) => [...contractKeys.all, id, 'calculations', { page }] as const,
}

export function useContracts(page: Ref<number>) {
  return useQuery({
    queryKey: contractKeys.list(page),
    queryFn: () => contractsApi.list(page.value),
  })
}

export function useContract(id: number) {
  return useQuery({
    queryKey: contractKeys.detail(id),
    queryFn: () => contractsApi.get(id),
  })
}

export function useContractCalculations(id: number, page: Ref<number>) {
  return useQuery({
    queryKey: contractKeys.calculations(id, page),
    queryFn: () => contractsApi.calculations(id, page.value),
  })
}

export function useCalculateContract() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: contractsApi.calculate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: contractKeys.all })
    },
  })
}
