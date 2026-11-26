# language: fr
@ListOrganizationContacts
Fonctionnalité: Consulter les contacts d'une organisation
  En tant que prospecteur
  Je veux voir les contacts d'une organisation, comme j'ai le droit de les lire
  Afin de savoir qui joindre chez elle

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Alan Turing" de "ACME"

  Scénario: Les contacts d'une organisation, dans l'ordre où ils y ont été rattachés
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et la fonction de "Ada Lovelace" est "CTO"
    Quand je consulte les contacts de "ACME"
    Alors ses contacts sont :
      | contact      | masqué | fonction | e-mail        |
      | Ada Lovelace | non    | CTO      | ada@acme.test |
      | Alan Turing  | non    |          |               |

  Scénario: Sans le droit aux données personnelles, les contacts d'une organisation sont masqués, sauf leur nom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Et la fonction de "Ada Lovelace" est "CTO"
    Quand je consulte les contacts de "ACME"
    Alors ses contacts sont :
      | contact      | masqué | fonction | e-mail   |
      | Ada Lovelace | oui    | C***O    | a***@*** |
      | Alan Turing  | oui    |          |          |

  Scénario: Un contact effacé n'est plus parmi les contacts de son organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les réactions aux gestes n'ont pas encore suivi
    Quand j'efface la fiche de "Ada Lovelace"
    Et je consulte les contacts de "ACME"
    Alors ses contacts sont :
      | contact     | masqué | fonction | e-mail |
      | Alan Turing | non    |          |        |

  Scénario: Les contacts d'une organisation inconnue sont introuvables
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je consulte les contacts d'une organisation inconnue
    Alors c'est introuvable, pour la raison "unknownOrganization"
    Et le refus nomme l'organisation demandée
