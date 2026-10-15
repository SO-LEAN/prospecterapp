# language: fr
Fonctionnalité: Consulter une organisation
  En tant que prospecteur
  Je veux consulter la fiche d'une organisation
  Afin de préparer mon prochain contact

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Je consulte une de mes organisations
    Soit le géocodeur situe "Strasbourg" en 7.75, 48.58
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |
      | ville           | Strasbourg        |
      | logo            | acme.png          |
    Quand je consulte l'organisation "ACME"
    Alors je vois l'organisation :
      | raison sociale  | ACME              |
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |
      | ville           | Strasbourg        |
      | position        | 7.75, 48.58       |
      | logo            | acme.png          |

  Scénario: Je consulte ma propre organisation et son nombre d'organisations actives
    Soit l'organisation "ACME" fait partie de mes organisations
    Et l'organisation "Globex" fait partie de mes organisations
    Quand je consulte l'organisation "SOLEAN IT"
    Alors je vois l'organisation :
      | raison sociale        | SOLEAN IT |
      | organisations actives | 2         |

  Scénario: Une organisation inconnue est introuvable
    Quand je consulte l'organisation "Inconnue"
    Alors c'est introuvable

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je consulte l'organisation "Initech"
    Alors c'est introuvable

  Scénario: Une autre organisation qui utilise Prospecter est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Quand je consulte l'organisation "Concurrent"
    Alors c'est introuvable
