import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { fetchClient } from '@/shared/api/client'
import { readTheExchangesAgain } from '@/shared/api/exchanges'
import { queryKeys } from '@/shared/api/keys'
import { readAgainAfterTheReactions } from '@/shared/api/reactions'
import { dataOf } from '@/shared/api/slices'

type Attachment = { contactId: string } & (
  | { organizationId: string }
  | { corporateName: string; email: string }
)

/**
 * Attaches a contact to one of my organizations, or first creates one for them. The gesture's reactions move their
 * exchanges and to-dos with them (ADR 0146). What shows them is read again once the reactions have run.
 */
export function useAttach(onAttached: () => void) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (attachment: Attachment) => {
      const organizationId =
        'organizationId' in attachment ? attachment.organizationId : await created(attachment)
      dataOf(
        await fetchClient.POST('/api/organizations/{id}/contacts', {
          params: { path: { id: organizationId } },
          body: { personId: attachment.contactId },
        }),
      )
    },
    onSuccess: () => {
      toast.success(t('common.saved'))
      onAttached()
      readTheExchangesAgain(queryClient)
      readAgainAfterTheReactions(
        queryClient,
        queryKeys.exchangesToProcess,
        queryKeys.contact,
        queryKeys.organizationsOfContact,
        queryKeys.organizationContacts,
        queryKeys.organizationList,
      )
    },
  })
}

async function created({ corporateName, email }: { corporateName: string; email: string }): Promise<string> {
  const organization = dataOf(
    await fetchClient.POST('/api/organizations', {
      body: {
        legalIdentity: { corporateName, form: null, type: null },
        contactDetails: { email, phoneNumber: null, language: null, links: [], website: null },
        address: null,
        observations: null,
        holdingId: null,
      },
    }),
  )
  return organization.id
}
