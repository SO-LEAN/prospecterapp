import { type ClassValue, clsx } from 'clsx'
import { extendTailwindMerge } from 'tailwind-merge'

/** The scales `app/styles.css` adds to Tailwind's, so that a later utility of the same group replaces them. */
const twMerge = extendTailwindMerge({
  extend: {
    theme: {
      text: ['title', 'card-title', 'body', 'label'],
      radius: ['chip', 'button', 'field', 'row', 'card', 'dialog'],
      shadow: ['rest', 'float', 'lift', 'card'],
      ease: ['standard', 'enter', 'exit'],
    },
  },
})

/** Merges class names. The last Tailwind utility of a group wins. */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
