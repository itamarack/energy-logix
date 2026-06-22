import axios from 'axios'

const BASE = import.meta.env.VITE_API_BASE_URL ?? ''

export const apiClient = axios.create({
  baseURL: BASE,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

apiClient.interceptors.response.use((response) => response, (error) => {
  const message = error.response?.data?.message || error.message || 'Request failed'
  const enhancedError = new Error(message)
  Object.assign(enhancedError, {
    status: error.response?.status,
    data: error.response?.data
  })
  return Promise.reject(enhancedError)
})
