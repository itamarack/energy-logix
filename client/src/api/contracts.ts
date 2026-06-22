import { apiFetch } from './client'
import type { Contract, CommissionCalculation } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const contractsApi = {
  list: () =>
    apiFetch<ApiCollection<Contract>>('/api/v1/contracts').then((r) => r.data),

  calculate: (id: number) =>
    apiFetch<ApiResource<CommissionCalculation>>(`/api/v1/contracts/${id}/calculate`, {
      method: 'POST',
    }).then((r) => r.data),
}
