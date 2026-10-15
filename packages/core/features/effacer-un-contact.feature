# language: fr
Fonctionnalité: Effacer un contact
  En tant que prospecteur
  Je veux effacer la fiche d'une personne que mon compte ne prospecte plus
  Afin de ne garder que les données personnelles utiles

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"

  Scénario: La fiche effacée n'existe plus
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'efface la fiche de "Ada Lovelace"
    Et je consulte la fiche de "Ada Lovelace"
    Alors c'est introuvable

  Scénario: Sans le droit, je ne peux pas effacer un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'efface la fiche de "Ada Lovelace"
    Alors c'est refusé : "Only a member allowed to manage personal data may forget a prospected person"

  Scénario: Un membre n'est pas un contact : sa fiche ne s'efface pas
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "Grace" est membre de "SOLEAN IT" avec le profil :
      | prénom | Grace |
    Quand j'efface la fiche de "Grace"
    Alors c'est refusé : "Only a member allowed to manage personal data may forget a prospected person"

  Scénario: Le contact d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et le contact "Linus Torvalds" de "Concurrent" a l'adresse e-mail "linus@kernel.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'efface la fiche de "Linus Torvalds"
    Alors c'est introuvable
