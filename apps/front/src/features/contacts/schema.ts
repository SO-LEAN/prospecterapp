import { z } from 'zod'
import { text } from '@/shared/forms/text'

export const jobTitleSchema = z.object({ jobTitle: text(100) })
