export const FORMULA_VERSION_ROUTES = {
  INDEX: '/formula-versions',
  CREATE: '/formula-versions/create',
  SHOW: (id: number | string) => `/formula-versions/${id}`,
  EDIT: (id: number | string) => `/formula-versions/${id}/edit`,
} as const;
