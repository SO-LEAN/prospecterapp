# language: fr
@ListOrganizationContacts
Fonctionnalité: Consulter les contacts d'une organisation
  En tant que prospecteur
  Je veux voir les contacts d'une organisation
  Afin de savoir qui joindre chez elle

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et le contact "Alan Turing" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Les contacts d'une organisation, dans l'ordre où ils y ont été rattachés
    Quand je consulte les contacts de "ACME"
    Alors ses contacts sont :
      | contact      |
      | Ada Lovelace |
      | Alan Turing  |

  Scénario: Un contact effacé n'est plus parmi les contacts de son organisation, avant même que les réactions suivent
    Soit les réactions aux gestes n'ont pas encore suivi
    Quand j'efface la fiche de "Ada Lovelace"
    Et je consulte les contacts de "ACME"
    Alors ses contacts sont :
      | contact     |
      | Alan Turing |

  Scénario: Les contacts d'une organisation inconnue sont introuvables
    Quand je consulte les contacts d'une organisation inconnue
    Alors c'est introuvable, pour la raison "unknownOrganization"
    Et le refus nomme l'organisation demandée
