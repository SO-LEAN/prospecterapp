import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import '@/shared/i18n'
import './app/styles.css'
import { App } from './app/providers'
import { keepThePageFromZooming } from './shared/lib/keep-the-page-from-zooming'

const root = document.getElementById('root')

keepThePageFromZooming(document)

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  )
}
