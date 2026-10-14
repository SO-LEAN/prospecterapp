import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import '@/shared/i18n'
import './app/styles.css'
import { App } from './app/providers'

const root = document.getElementById('root')

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  )
}
