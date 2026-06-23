import { apiClient } from './client'
import type { FormulaVersion, SimulationResult } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const formulaVersionsApi = {
  list: () =>
    apiClient.get<ApiCollection<FormulaVersion>>('/api/v1/formula-versions').then((r) => r.data.data),

  get: (id: number) =>
    apiClient.get<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}`).then((r) => r.data.data),

  create: (data: Omit<FormulaVersion, 'id' | 'version_number' | 'is_active' | 'created_at'>) =>
    apiClient.post<ApiResource<FormulaVersion>>('/api/v1/formula-versions', data).then((r) => r.data.data),

  update: (id: number, data: Omit<FormulaVersion, 'id' | 'version_number' | 'is_active' | 'created_at'>) =>
    apiClient.put<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}`, data).then((r) => r.data.data),

  activate: (id: number) =>
    apiClient.post<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}/activate`).then((r) => r.data.data),

  deactivate: (id: number) =>
    apiClient.post<ApiResource<FormulaVersion>>(`/api/v1/formula-versions/${id}/deactivate`).then((r) => r.data.data),

  simulate: (id: number) =>
    apiClient.post<SimulationResult>(`/api/v1/formula-versions/${id}/simulate`).then((r) => r.data),
}
