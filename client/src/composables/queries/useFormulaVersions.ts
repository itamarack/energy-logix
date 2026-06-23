import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { formulaVersionsApi } from '@/api/formulaVersions'

import { type Ref } from 'vue'

export const formulaVersionKeys = {
  all: ['formula-versions'] as const,
  list: (page: Ref<number>) => [...formulaVersionKeys.all, { page }] as const,
  detail: (id: number) => [...formulaVersionKeys.all, id] as const,
}

export function useFormulaVersions(page: Ref<number>) {
  return useQuery({
    queryKey: formulaVersionKeys.list(page),
    queryFn: () => formulaVersionsApi.list(page.value),
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

export function useUpdateFormulaVersion() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Parameters<typeof formulaVersionsApi.update>[1] }) =>
      formulaVersionsApi.update(id, data),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: formulaVersionKeys.all })
      queryClient.invalidateQueries({ queryKey: formulaVersionKeys.detail(data.id) })
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

export function useDeactivateFormulaVersion() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: formulaVersionsApi.deactivate,
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
