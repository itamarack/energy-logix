import { apiFetch } from './client'
import type { CommissionCalculation } from '@/types'

type ApiCollection<T> = { data: T[] }
type ApiResource<T> = { data: T }

export const calculationsApi = {
  list: () =>
    apiFetch<ApiCollection<CommissionCalculation>>('/api/v1/calculations').then((r) => r.data),

  get: (id: number) =>
    apiFetch<ApiResource<CommissionCalculation>>(`/api/v1/calculations/${id}`).then((r) => r.data),
}
