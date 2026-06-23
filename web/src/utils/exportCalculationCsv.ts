import type { CommissionCalculation } from '@/types'

function formatCurrency(value: number) {
  return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
}

export function exportCalculationCsv(calc: CommissionCalculation) {
  const rows = [
    ['Field', 'Value'],
    ['ID', calc.id.toString()],
    ['Contract', calc.contract?.name ?? ''],
    ['Formula Version', calc.formula_version?.name ?? ''],
    ['Formula Version #', calc.formula_version?.version_number.toString() ?? ''],
    ['Commission Result', formatCurrency(calc.result)],
    ['Calculated At', new Date(calc.calculated_at).toLocaleString()],
    ['', ''],
    ['Step', 'Variable', 'Expression', 'Value'],
    ...(calc.calculation_steps ?? []).map(s => [
      s.step.toString(),
      s.variable,
      s.expression,
      s.value.toString()
    ]),
  ]
  const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `calculation-${calc.id}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
