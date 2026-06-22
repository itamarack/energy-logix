import { apiClient } from './client'
import type { CommissionCalculation } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const calculationsApi = {
  list: () =>
    apiClient.get<ApiCollection<CommissionCalculation>>('/api/v1/calculations').then((r) => r.data.data),

  get: (id: number) =>
    apiClient.get<ApiResource<CommissionCalculation>>(`/api/v1/calculations/${id}`).then((r) => r.data.data),
}
