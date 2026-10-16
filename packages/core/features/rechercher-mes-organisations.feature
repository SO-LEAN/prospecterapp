# language: fr
Fonctionnalité: Rechercher parmi mes organisations
  En tant que prospecteur
  Je veux parcourir et rechercher les organisations que je prospecte
  Afin de retrouver rapidement celle que je cherche

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Je ne vois que les organisations de mon organisation, les plus récentes d'abord
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | forme juridique | SAS        |
      | ville           | Strasbourg |
    Et l'organisation "Globex" fait partie de mes organisations
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je liste mes organisations
    Alors je trouve 2 organisations
    Et il n'y a pas de suite
    Et la liste affiche :
      | nom      | ville      |
      | Globex   |            |
      | ACME SAS | Strasbourg |

  Scénario: Je recherche par raison sociale ou par e-mail
    Soit l'organisation "ACME" fait partie de mes organisations
    Et l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@acme-partner.test |
    Et l'organisation "Initech" fait partie de mes organisations
    Quand je cherche "acme" dans mes organisations
    Alors je trouve 2 organisations
    Et la liste affiche :
      | nom    |
      | Globex |
      | ACME   |

  Scénario: Je trouve une organisation par le début de ses mots, sans souci des accents ni des majuscules
    Soit l'organisation "Société Générale d'Électricité" fait partie de mes organisations
    Et l'organisation "Globex" fait partie de mes organisations
    Quand je cherche "gen ELEC" dans mes organisations
    Alors la liste affiche :
      | nom                            |
      | Société Générale d'Électricité |

  Scénario: Un mot cherché commence un mot, il ne se trouve pas au milieu
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand je cherche "cme" dans mes organisations
    Alors je trouve 0 organisation

  Scénario: Les organisations arrivent par tranches, chacune après la précédente
    Soit 5 organisations font partie de mes organisations
    Quand je liste mes organisations, 2 à la fois
    Alors la liste affiche :
      | nom             |
      | Organisation 05 |
      | Organisation 04 |
    Et il y a une suite
    Quand je demande la suite
    Alors la liste affiche :
      | nom             |
      | Organisation 03 |
      | Organisation 02 |
    Quand je demande la suite
    Alors la liste affiche :
      | nom             |
      | Organisation 01 |
    Et il n'y a pas de suite

  Scénario: Sans organisation, la liste est vide
    Quand je liste mes organisations
    Alors je trouve 0 organisation
    Et il n'y a pas de suite
