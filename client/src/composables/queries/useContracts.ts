import { useQuery, useMutation } from '@tanstack/vue-query'
import { contractsApi } from '@/api/contracts'

export const contractKeys = {
  all: ['contracts'] as const,
  detail: (id: number) => [...contractKeys.all, id] as const,
}

export function useContracts() {
  return useQuery({
    queryKey: contractKeys.all,
    queryFn: contractsApi.list,
  })
}

export function useCalculateContract() {
  return useMutation({
    mutationFn: contractsApi.calculate,
  })
}
