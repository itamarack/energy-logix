export const CALCULATION_ROUTES = {
  INDEX: '/calculations',
  SHOW: (id: number | string) => `/calculations/${id}`,
} as const;
