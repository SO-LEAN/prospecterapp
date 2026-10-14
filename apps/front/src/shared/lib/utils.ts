import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

/** Merges class names, the last Tailwind utility of a group wins. */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}
