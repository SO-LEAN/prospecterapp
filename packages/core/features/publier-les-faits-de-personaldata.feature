# language: fr
Fonctionnalité: Publier les faits de PersonalData
  En tant que contexte de Prospecter
  Je veux connaître l'état de chaque contact qui change, sans lire les tables de PersonalData
  Afin de compter les contacts d'un compte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Un contact collecté pour la prospection publie un fait présent
    Quand j'enregistre le contact "Ada Lovelace" pour "ACME" :
      | prénom | Ada      |
      | nom    | Lovelace |
    Alors le fait publié du contact "Ada Lovelace" est présent, révision 1

  Scénario: Un contact renommé publie sa révision suivante
    Soit le contact "Ada Lovelace" de "ACME"
    Quand je change le nom de "Ada Lovelace" :
      | prénom | Ada  |
      | nom    | King |
    Alors le fait publié du contact "Ada Lovelace" est présent, révision 2

  Scénario: Le profil d'un membre ne publie aucun fait de contact
    Soit mon profil est :
      | prénom | Grace |
    Quand je change mon nom :
      | prénom | Grace  |
      | nom    | Hopper |
    Alors aucun fait de contact n'est publié

  Scénario: Un contact oublié donne un fait terminal
    Soit le contact "Ada Lovelace" de "ACME"
    Quand j'efface la fiche de "Ada Lovelace"
    Alors le fait publié du contact "Ada Lovelace" est absent, révision 0

  Scénario: Un contact laissé sans organisation est oublié et donne un fait terminal
    Soit le contact "Ada Lovelace" de "ACME"
    Quand j'arrête de prospecter "ACME"
    Alors le fait publié du contact "Ada Lovelace" est absent, révision 0
