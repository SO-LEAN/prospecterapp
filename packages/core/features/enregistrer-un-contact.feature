# language: fr
Fonctionnalité: Enregistrer un contact
  En tant que prospecteur
  Je veux enregistrer une personne que je prospecte, depuis l'organisation où elle travaille
  Afin de savoir à qui m'adresser dans cette organisation

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"

  Scénario: Le contact est gardé pour mon compte, avec sa fonction, et rattaché à l'organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" pour "ACME" :
      | prénom   | Ada                    |
      | nom      | Lovelace               |
      | fonction | Directrice commerciale |
      | e-mail   | ada@acme.test          |
    Alors les contacts de "ACME" sont :
      | Ada Lovelace |
    Quand je consulte la fiche de "Ada Lovelace"
    Alors je vois la fiche :
      | prénom   | Ada                    |
      | nom      | Lovelace               |
      | fonction | Directrice commerciale |
      | e-mail   | ada@acme.test          |
      | masquée  | non                    |

  Scénario: Un contact n'existe pas sans organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" sans organisation :
      | prénom | Ada |
    Alors c'est refusé : "A contact belongs to at least one organization"

  Scénario: L'organisation d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" pour "Initech" :
      | prénom | Ada |
    Alors c'est introuvable

  Scénario: Sans le droit aux données personnelles, je ne peux pas enregistrer un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'enregistre le contact "Ada Lovelace" pour "ACME" :
      | prénom | Ada |
    Alors c'est refusé : "Only a member allowed to manage personal data may collect a contact"
    Et "ACME" n'a pas de contact

  Scénario: Deux contacts de mon compte ne partagent pas un e-mail
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | prénom | Grace         |
      | e-mail | ada@acme.test |
    Alors c'est refusé : "Email already used"
    Et les contacts de "ACME" sont :
      | Ada Lovelace |

  Scénario: Un autre compte peut prospecter la même personne
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Ada chez le concurrent" de "Initech" a l'adresse e-mail "ada@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" pour "ACME" :
      | prénom | Ada           |
      | e-mail | ada@acme.test |
    Alors ce n'est pas refusé
