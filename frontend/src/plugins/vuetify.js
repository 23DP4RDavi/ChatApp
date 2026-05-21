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
        dark: true,
        colors: {
          background: '#111318',
          surface: '#1a1b1e',
          'surface-variant': '#25262b',
          primary: '#7c3aed',
          'primary-darken-1': '#6d28d9',
          secondary: '#5865f2',
          error: '#f85149',
          info: '#58a6ff',
          success: '#3fb950',
          warning: '#e6a817',
          'on-background': '#dbdee1',
          'on-surface': '#c1c2c5',
        }
      }
    }
  }
})

export default vuetify
