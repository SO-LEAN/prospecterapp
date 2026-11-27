import { render } from '@testing-library/react'
import { Phone } from 'lucide-react'
import { describe, expect, it } from 'vitest'
import { IconsProvider } from './icons-provider'

describe('IconsProvider', () => {
  it('draws the icons with a 1.75 px stroke', () => {
    const { container } = render(
      <IconsProvider>
        <Phone aria-hidden />
      </IconsProvider>,
    )

    expect(container.querySelector('svg')).toHaveAttribute('stroke-width', '1.75')
  })
})
