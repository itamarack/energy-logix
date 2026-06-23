import { apiClient } from './client'

export type FormulaVariable = {
  name: string
  description: string
}

export const formulaVariablesApi = {
  list: () =>
    apiClient.get<{ data: FormulaVariable[] }>('/api/v1/formula-variables').then((r) => r.data.data),
}
