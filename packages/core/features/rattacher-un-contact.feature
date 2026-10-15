# language: fr
Fonctionnalité: Rattacher un contact à une organisation
  En tant que prospecteur
  Je veux rattacher une personne que je prospecte à l'organisation où elle travaille
  Afin de savoir à qui m'adresser dans cette organisation

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"

  Scénario: Le contact est rattaché à l'organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je rattache le contact "Ada Lovelace" à "ACME"
    Alors les contacts de "ACME" sont :
      | Ada Lovelace |

  Scénario: Une personne peut être le contact de plusieurs organisations
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "Groupe ACME" fait partie de mes organisations
    Et "Ada Lovelace" est un contact de "ACME"
    Quand je rattache le contact "Ada Lovelace" à "Groupe ACME"
    Alors les contacts de "Groupe ACME" sont :
      | Ada Lovelace |
    Et les contacts de "ACME" sont :
      | Ada Lovelace |

  Scénario: Un contact déjà rattaché ne l'est pas deux fois
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "ACME"
    Quand je rattache le contact "Ada Lovelace" à "ACME"
    Alors c'est refusé : "This person is a contact of the organization already"

  Scénario: Le contact d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et le contact "Linus Torvalds" de "Concurrent" a l'adresse e-mail "linus@kernel.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je rattache le contact "Linus Torvalds" à "ACME"
    Alors c'est introuvable

  Scénario: Sans le droit aux données personnelles, je ne rattache pas de contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je rattache le contact "Ada Lovelace" à "ACME"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may manage the contacts"
