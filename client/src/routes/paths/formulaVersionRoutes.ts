export const FORMULA_VERSION_ROUTES = {
  INDEX: '/formula-versions',
  CREATE: '/formula-versions/create',
  SHOW: (id: number | string) => `/formula-versions/${id}`,
} as const;
