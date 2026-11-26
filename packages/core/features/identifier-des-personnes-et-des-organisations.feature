# language: fr
@IdentifyPersonsAndOrganizations
Fonctionnalité: Identifier ensemble des personnes et les organisations où elles travaillent
  En tant que l'IA qui sert un prospecteur
  Je veux identifier en une fois des organisations et les personnes qui y travaillent, telles que je les vois
  Afin que chaque personne soit cherchée d'abord parmi les contacts de l'organisation qu'elle nomme

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations
    Et l'organisation "Globex" fait partie de mes organisations
    Et le contact "Ada Lovelace" de "ACME"
    Et le contact "Adà Lovelace" de "Globex"

  Scénario: Une personne est cherchée d'abord parmi les candidats de l'organisation qu'elle nomme
    Soit les organisations à identifier :
      | nom    |
      | Globex |
    Quand j'identifie avec elles les personnes :
      | prénom | nom      | organisation |
      | Ada    | Lovelace | 1            |
    Alors les organisations sont identifiées :
      | statut      | candidates |
      | à confirmer | Globex     |
    Et les personnes sont identifiées :
      | statut      | candidates                 |
      | à confirmer | Adà Lovelace, Ada Lovelace |

  Scénario: Une personne qui ne nomme aucune organisation est cherchée dans tout le compte
    Soit les organisations à identifier :
      | nom    |
      | Globex |
    Quand j'identifie avec elles les personnes :
      | prénom | nom      | organisation |
      | Ada    | Lovelace |              |
    Alors les personnes sont identifiées :
      | statut      | candidates                 |
      | à confirmer | Ada Lovelace, Adà Lovelace |

  Scénario: Une position hors de la liste ne désigne aucune organisation
    Soit les organisations à identifier :
      | nom    |
      | Globex |
    Quand j'identifie avec elles les personnes :
      | prénom | nom      | organisation |
      | Ada    | Lovelace | 2            |
    Alors les personnes sont identifiées :
      | statut      | candidates                 |
      | à confirmer | Ada Lovelace, Adà Lovelace |

  Scénario: Les organisations sont identifiées dans l'ordre donné
    Soit les organisations à identifier :
      | nom     |
      | Initech |
      | ACME    |
    Quand j'identifie avec elles les personnes :
      | prénom | nom    | organisation |
      | Grace  | Hopper | 2            |
    Alors les organisations sont identifiées :
      | statut      | candidates |
      | nouvelle    |            |
      | à confirmer | ACME       |
    Et les personnes sont identifiées :
      | statut   | candidates |
      | nouvelle |            |
