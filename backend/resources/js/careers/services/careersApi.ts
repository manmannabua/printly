import axios from 'axios'

// Standalone axios instance for public careers API — no credentials, no CSRF
const careersApi = axios.create({
  baseURL: '/api/public/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: false,
})

export default careersApi
