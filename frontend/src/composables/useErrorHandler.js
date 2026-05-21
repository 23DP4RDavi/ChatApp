export function useErrorHandler() {
  const getErrorMessage = (error) => {
    if (error.response?.data?.message) {
      return error.response.data.message
    }
    if (error.response?.status === 401) {
      return 'Unauthorized. Please log in again.'
    }
    if (error.response?.status === 403) {
      return 'You do not have permission to perform this action.'
    }
    if (error.response?.status === 404) {
      return 'The requested resource was not found.'
    }
    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response.data?.errors
      if (errors && typeof errors === 'object') {
        return Object.values(errors).flat().join(', ')
      }
      return 'Validation failed. Please check your input.'
    }
    if (error.response?.status === 500) {
      return 'Server error. Please try again later.'
    }
    if (error.message === 'Network Error') {
      return 'Network error. Please check your connection.'
    }
    return error.message || 'An unexpected error occurred.'
  }

  const getFieldErrors = (error) => {
    if (error.response?.status === 422 && error.response.data?.errors) {
      return error.response.data.errors
    }
    return {}
  }

  return {
    getErrorMessage,
    getFieldErrors
  }
}
