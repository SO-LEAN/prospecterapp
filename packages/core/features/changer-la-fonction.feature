# language: fr
Fonctionnalité: Changer la fonction d'une personne
  En tant que prospecteur
  Je veux tenir à jour ce que fait un contact dans son organisation
  Afin de m'adresser à la bonne personne

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"

  Scénario: La fonction change, le reste de la fiche ne bouge pas
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je change la fonction de "Ada Lovelace" : "Directrice générale"
    Et je consulte la fiche de "Ada Lovelace"
    Alors je vois la fiche :
      | fonction | Directrice générale |
      | e-mail   | ada@acme.test       |

  Scénario: Sans le droit, je ne peux pas changer la fonction d'un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je change la fonction de "Ada Lovelace" : "Directrice générale"
    Alors c'est refusé : "Only the person or a member allowed to manage personal data may change them"
