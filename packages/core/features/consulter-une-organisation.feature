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

  Scénario: Mon organisation n'est pas une organisation que je prospecte
    Quand je consulte l'organisation "SOLEAN IT"
    Alors c'est introuvable

  Scénario: Une organisation inconnue est introuvable
    Quand je consulte l'organisation "Inconnue"
    Alors c'est introuvable

  Scénario: Une autre organisation qui utilise Prospecter est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Quand je consulte l'organisation "Concurrent"
    Alors c'est introuvable

  Scénario: Sans logo ni holding, je ne peux ni retirer le logo ni détacher l'organisation
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand je consulte l'organisation "ACME"
    Alors je peux :
      | réviser l'identité      |
      | changer les coordonnées |
      | déménager               |
      | écrire des observations |
      | rattacher à une holding |
      | changer le logo         |
      | rattacher un contact    |
      | arrêter de prospecter   |

  Scénario: Avec un logo et une holding, je peux aussi les retirer
    Soit l'organisation "Groupe ACME" fait partie de mes organisations
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | logo | acme.png |
    Et "ACME" est rattachée à la holding "Groupe ACME"
    Quand je consulte l'organisation "ACME"
    Alors je peux :
      | réviser l'identité      |
      | changer les coordonnées |
      | déménager               |
      | écrire des observations |
      | rattacher à une holding |
      | détacher de la holding  |
      | changer le logo         |
      | retirer le logo         |
      | rattacher un contact    |
      | arrêter de prospecter   |
