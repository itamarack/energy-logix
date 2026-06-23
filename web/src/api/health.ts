import axios from 'axios'

export const healthApi = {
  check: () => axios.get('/up').then((r) => r.data),
}
