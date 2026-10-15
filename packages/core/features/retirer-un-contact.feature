# language: fr
Fonctionnalité: Retirer un contact d'une organisation
  En tant que prospecteur
  Je veux retirer une personne qui ne travaille plus dans une organisation
  Afin que ses contacts restent justes

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"
    Et "Ada Lovelace" est un contact de "ACME"

  Scénario: Le contact est retiré, sa fiche reste
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je retire le contact "Ada Lovelace" de "ACME"
    Alors "ACME" n'a pas de contact
    Et ce n'est pas refusé
    Quand je consulte la fiche de "Ada Lovelace"
    Alors je vois la fiche :
      | e-mail | ada@acme.test |

  Scénario: Une personne qui n'est pas son contact ne se retire pas
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Grace Hopper" de "SOLEAN IT" a l'adresse e-mail "grace@navy.test"
    Quand je retire le contact "Grace Hopper" de "ACME"
    Alors c'est refusé : "This person is no contact of the organization"

  Scénario: Sans le droit aux données personnelles, je ne retire pas de contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je retire le contact "Ada Lovelace" de "ACME"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may manage the contacts"
