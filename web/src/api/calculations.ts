import { apiClient } from './client'
import type { CommissionCalculation, PaginatedResponse } from '@/types'

type ApiResource<T> = { data: T }

export const calculationsApi = {
  list: (page: number = 1) =>
    apiClient.get<PaginatedResponse<CommissionCalculation>>(`/api/v1/calculations?page=${page}`).then((r) => r.data),

  get: (id: number) =>
    apiClient.get<ApiResource<CommissionCalculation>>(`/api/v1/calculations/${id}`).then((r) => r.data.data),
}
