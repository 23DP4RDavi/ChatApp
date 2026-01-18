import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'dark',
    themes: {
      dark: {
        colors: {
          primary: '#7eb7ea',
          secondary: '#ffb3d9',
          accent: '#ffd166',
          error: '#ff9ba5',
          info: '#c5a8e0',
          success: '#9ee6a8',
          warning: '#ffb88c',
          background: '#1a1625',
          surface: '#251e35'
        }
      },
      light: {
        colors: {
          primary: '#5a9bd4',
          secondary: '#ff8fc7',
          accent: '#ffb84d',
          error: '#f44336',
          info: '#9c7ac8',
          success: '#4caf50',
          warning: '#ff9800'
        }
      }
    }
  }
})

export default vuetify
