import { useQuery } from '@tanstack/vue-query'
import { healthApi } from '@/api/health'

export function useHealthCheck() {
  return useQuery({
    queryKey: ['health-check'],
    queryFn: healthApi.check,
    refetchInterval: 10000, // Ping every 10 seconds
    retry: 2,
  })
}
