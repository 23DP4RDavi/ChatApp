import axios from 'axios'

const TOKEN_KEY = 'token'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add token to requests if available
apiClient.interceptors.request.use(
  config => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

// Handle response errors
apiClient.interceptors.response.use(
  response => response,
  error => {
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to auth
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem('user')
      if (window.location.pathname !== '/auth') {
        window.location.href = '/auth'
      }
    }
    
    // Handle network errors
    if (!error.response) {
      console.error('Network error:', error.message)
      error.message = 'Network error. Please check your connection.'
    }
    
    return Promise.reject(error)
  }
)

export default apiClient
