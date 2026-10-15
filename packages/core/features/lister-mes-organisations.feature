# language: fr
Fonctionnalité: Lister mes organisations
  En tant que prospecteur
  Je veux parcourir et rechercher les organisations de mon organisation
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
    Alors je trouve 2 organisations sur 1 page
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
    Alors je trouve 2 organisations sur 1 page
    Et la liste affiche :
      | nom    |
      | Globex |
      | ACME   |

  Scénario: Les organisations sont paginées
    Soit 5 organisations font partie de mes organisations
    Quand je liste la page 2 de mes organisations, 2 par page
    Alors je trouve 5 organisations sur 3 pages
    Et la liste affiche :
      | nom             |
      | Organisation 03 |
      | Organisation 02 |

  Scénario: Sans organisation, la liste est vide
    Quand je liste mes organisations
    Alors je trouve 0 organisation sur 1 page
