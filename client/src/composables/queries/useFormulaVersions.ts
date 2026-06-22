import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { formulaVersionsApi } from '@/api/formulaVersions'

export const formulaVersionKeys = {
  all: ['formula-versions'] as const,
  detail: (id: number) => [...formulaVersionKeys.all, id] as const,
}

export function useFormulaVersions() {
  return useQuery({
    queryKey: formulaVersionKeys.all,
    queryFn: formulaVersionsApi.list,
  })
}

export function useFormulaVersion(id: number) {
  return useQuery({
    queryKey: formulaVersionKeys.detail(id),
    queryFn: () => formulaVersionsApi.get(id),
  })
}

export function useCreateFormulaVersion() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: formulaVersionsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: formulaVersionKeys.all })
    },
  })
}

export function useActivateFormulaVersion() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: formulaVersionsApi.activate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: formulaVersionKeys.all })
    },
  })
}

export function useSimulateFormulaVersion() {
  return useMutation({
    mutationFn: formulaVersionsApi.simulate,
  })
}
