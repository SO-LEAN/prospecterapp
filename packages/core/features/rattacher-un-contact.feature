# language: fr
Fonctionnalité: Rattacher un contact existant à une organisation
  En tant que prospecteur
  Je veux rattacher une personne que je prospecte déjà à une autre organisation où elle travaille
  Afin de savoir à qui m'adresser dans cette organisation aussi

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "Groupe ACME" a l'adresse e-mail "ada@acme.test"

  Scénario: Une personne devient le contact d'une seconde organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je rattache le contact "Ada Lovelace" à "ACME"
    Alors les contacts de "ACME" sont :
      | Ada Lovelace |
    Et les contacts de "Groupe ACME" sont :
      | Ada Lovelace |

  Scénario: Un contact déjà rattaché ne l'est pas deux fois
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je rattache le contact "Ada Lovelace" à "Groupe ACME"
    Alors c'est refusé : "This person is a contact of the organization already"

  Scénario: Le contact d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech" a l'adresse e-mail "linus@kernel.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je rattache le contact "Linus Torvalds" à "ACME"
    Alors c'est introuvable

  Scénario: Je rattache un contact à rattacher, ses échanges et ses actions à faire la suivent
    Soit nous sommes le 18/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "Grace Hopper" (grace@acme.test) a pris une visio avec moi le 20/11/2026 à 14h00
    Et j'ai noté à faire pour "Grace Hopper" "Envoyer la proposition" pour le 25/11/2026
    Quand je rattache le contact "Grace Hopper" à "ACME"
    Alors les contacts de "ACME" sont :
      | Grace Hopper |
    Et la visio avec "Grace Hopper" est gardée pour "ACME"
    Et elle est dans les actions à faire de "ACME"
    Et "Grace Hopper" n'est plus à rattacher
