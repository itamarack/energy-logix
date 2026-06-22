import { apiClient } from './client'
import type { Contract, CommissionCalculation } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const contractsApi = {
  list: () =>
    apiClient.get<ApiCollection<Contract>>('/api/v1/contracts').then((r) => r.data.data),

  calculate: (id: number) =>
    apiClient.post<ApiResource<CommissionCalculation>>(`/api/v1/contracts/${id}/calculate`).then((r) => r.data.data),
}
