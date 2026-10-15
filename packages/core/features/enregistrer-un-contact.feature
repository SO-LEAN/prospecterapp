# language: fr
Fonctionnalité: Enregistrer un contact
  En tant que prospecteur
  Je veux enregistrer une personne que je prospecte
  Afin de la rattacher ensuite aux organisations où elle travaille

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter

  Scénario: Le contact est gardé pour mon compte, avec sa fonction
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" :
      | prénom   | Ada                    |
      | nom      | Lovelace               |
      | fonction | Directrice commerciale |
      | e-mail   | ada@acme.test          |
    Et je consulte la fiche de "Ada Lovelace"
    Alors je vois la fiche :
      | prénom   | Ada                    |
      | nom      | Lovelace               |
      | fonction | Directrice commerciale |
      | e-mail   | ada@acme.test          |
      | masquée  | non                    |

  Scénario: Sans le droit aux données personnelles, je ne peux pas enregistrer un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'enregistre le contact "Ada Lovelace" :
      | prénom | Ada |
    Alors c'est refusé : "Only a member allowed to manage personal data may collect a contact"

  Scénario: Deux contacts de mon compte ne partagent pas un e-mail
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"
    Quand j'enregistre le contact "Grace Hopper" :
      | prénom | Grace         |
      | e-mail | ada@acme.test |
    Alors c'est refusé : "Email already used"

  Scénario: Un autre compte peut prospecter la même personne
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et le contact "Ada chez le concurrent" de "Concurrent" a l'adresse e-mail "ada@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'enregistre le contact "Ada Lovelace" :
      | prénom | Ada           |
      | e-mail | ada@acme.test |
    Alors ce n'est pas refusé
