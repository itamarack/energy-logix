import { apiClient } from './client'
import type { Contract, CommissionCalculation, PaginatedResponse } from '@/types'

type ApiResource<T> = { data: T }

export const contractsApi = {
  list: (page: number = 1) =>
    apiClient.get<PaginatedResponse<Contract>>(`/api/v1/contracts?page=${page}`).then((r) => r.data),

  calculate: (id: number) =>
    apiClient.post<ApiResource<CommissionCalculation>>(`/api/v1/contracts/${id}/calculate`).then((r) => r.data.data),
}
