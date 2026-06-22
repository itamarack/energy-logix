/**
 * Base fetch wrapper that reads VITE_API_BASE_URL and handles JSON errors.
 */
const BASE = import.meta.env.VITE_API_BASE_URL ?? ''

export async function apiFetch<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(init?.headers ?? {}),
    },
    ...init,
  })

  const json = await res.json().catch(() => null)

  if (!res.ok) {
    const message = json?.message ?? `Request failed: ${res.status}`
    const err = Object.assign(new Error(message), { status: res.status, data: json })
    throw err
  }

  return json as T
}
