export const CONTRACT_ROUTES = {
  INDEX: '/contracts',
  SHOW: (id: number | string) => `/contracts/${id}`,
} as const;
