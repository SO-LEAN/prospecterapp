import {
  BriefcaseBusiness,
  CalendarDays,
  Handshake,
  type LucideIcon,
  Mail,
  MessageCircle,
  MessageSquareText,
  Phone,
  Video,
} from 'lucide-react'
import type { components } from '@/shared/api/schema'

export type Channel = components['schemas']['ExchangeResource']['channel']

export const CHANNEL_ICONS: Record<Channel, LucideIcon> = {
  call: Phone,
  sms: MessageSquareText,
  email: Mail,
  linkedIn: BriefcaseBusiness,
  malt: Handshake,
  meeting: CalendarDays,
  videoCall: Video,
  other: MessageCircle,
}
