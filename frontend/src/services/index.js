import apiClient from './api'

export const authService = {
  async register(userData) {
    const response = await apiClient.post('/register', userData)
    if (response.data.access_token) {
      localStorage.setItem('auth_token', response.data.access_token)
    }
    return response.data
  },

  async login(credentials) {
    const response = await apiClient.post('/login', credentials)
    if (response.data.access_token) {
      localStorage.setItem('auth_token', response.data.access_token)
    }
    return response.data
  },

  async logout() {
    try {
      await apiClient.post('/logout')
    } finally {
      localStorage.removeItem('auth_token')
    }
  },

  async getUser() {
    const response = await apiClient.get('/user')
    return response.data
  }
}

export const messageService = {
  async getMessages() {
    const response = await apiClient.get('/messages')
    return response.data
  },

  async sendMessage(messageData) {
    const response = await apiClient.post('/messages', messageData)
    return response.data
  },

  async deleteMessage(id) {
    const response = await apiClient.delete(`/messages/${id}`)
    return response.data
  }
}

export const drawingService = {
  async getDrawings() {
    const response = await apiClient.get('/drawings')
    return response.data
  },

  async saveDrawing(drawingData) {
    const response = await apiClient.post('/drawings', drawingData)
    return response.data
  },

  async deleteDrawing(id) {
    const response = await apiClient.delete(`/drawings/${id}`)
    return response.data
  }
}

export const userService = {
  async getUsers() {
    const response = await apiClient.get('/users')
    return response.data
  },

  async getUser(id) {
    const response = await apiClient.get(`/users/${id}`)
    return response.data
  }
}
