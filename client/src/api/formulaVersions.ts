import { apiFetch } from './client'
import type { FormulaVersion, SimulationResult } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const formulaVersionsApi = {
  list: () =>
    apiFetch<ApiCollection<FormulaVersion>>('/api/v1/formula-versions').then((r) => r.data),

  get: (id: number) =>
    apiFetch<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}`).then((r) => r.data),

  create: (payload: {
    name: string
    description?: string
    expression: string
    variables: { name: string; expression: string }[]
  }) =>
    apiFetch<ApiResource<FormulaVersion>>('/api/v1/formula-versions', {
      method: 'POST',
      body: JSON.stringify(payload),
    }).then((r) => r.data),

  activate: (id: number) =>
    apiFetch<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}/activate`, {
      method: 'POST',
    }).then((r) => r.data),

  simulate: (id: number) =>
    apiFetch<SimulationResult>(`/api/v1/formula-versions/${id}/simulate`, {
      method: 'POST',
    }),
}
