export type FormulaVariable = {
  name: string
  expression: string
}

export type FormulaVersion = {
  id: number
  name: string
  description: string | null
  version_number: number
  expression: string
  variables: FormulaVariable[]
  is_active: boolean
  created_at: string
}

export type Contract = {
  id: number
  name: string
  annual_usage: number
  contract_value: number
  contract_length: number
  risk_score: number
  created_at: string
}

export type CalculationStep = {
  step: number
  variable: string
  expression: string
  value: number
}

export type CommissionCalculation = {
  id: number
  formula_version_id: number
  contract_id: number
  input_values: Record<string, number>
  calculation_steps: CalculationStep[]
  result: number
  calculated_at: string
  formula_version?: FormulaVersion
  contract?: Contract
}

export type SimulationResult = {
  affected_contract_count: number
  current_total_commission: number
  new_total_commission: number
  difference: number
}

export type ApiError = {
  message: string
  errors?: Record<string, string[]>
}
