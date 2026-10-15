# language: fr
Fonctionnalité: Commencer à prospecter une organisation
  En tant que prospecteur
  Je veux enregistrer une organisation que je commence à prospecter
  Afin de la suivre et de la situer sur une carte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: L'organisation prospectée appartient à mon organisation
    Quand je commence à prospecter l'organisation :
      | raison sociale  | ACME               |
      | forme juridique | SAS                |
      | type            | Éditeur            |
      | e-mail          | contact@acme.test  |
      | téléphone       | 03 88 00 00 00     |
      | langue          | FR                 |
      | observations    | Rencontré au salon |
    Alors l'organisation "ACME" est enregistrée avec :
      | forme juridique | SAS                |
      | type            | Éditeur            |
      | e-mail          | contact@acme.test  |
      | téléphone       | 03 88 00 00 00     |
      | langue          | FR                 |
      | observations    | Rencontré au salon |
      | propriétaire    | SOLEAN IT          |
    Et l'organisation "SOLEAN IT" compte 1 organisation active

  Scénario: Une raison sociale suffit
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | e-mail | |

  Scénario: Un e-mail suffit
    Quand je commence à prospecter l'organisation :
      | e-mail | contact@acme.test |
    Et je liste mes organisations
    Alors je trouve 1 organisation sur 1 page

  Scénario: Il faut au moins une raison sociale ou un e-mail
    Quand je commence à prospecter l'organisation :
      | ville | Strasbourg |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"
    Et l'organisation "SOLEAN IT" compte 0 organisation active

  Scénario: L'e-mail ne peut pas être celui d'une autre organisation ou d'un utilisateur
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                |
      | e-mail         | contact@globex.test |
    Alors c'est refusé : "Email already used"

  Scénario: L'organisation peut faire partie d'une holding que je prospecte
    Soit l'organisation "Groupe ACME" fait partie de mes organisations
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME        |
      | holding        | Groupe ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | Groupe ACME |

  Scénario: La holding doit exister
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME     |
      | holding        | Inconnue |
    Alors c'est introuvable
    Et l'organisation "SOLEAN IT" compte 0 organisation active

  Scénario: La holding doit être une de mes organisations
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME    |
      | holding        | Initech |
    Alors c'est introuvable

  Scénario: Une adresse connue du géocodeur place l'organisation sur la carte
    Soit le géocodeur situe "Strasbourg" en 7.75, 48.58
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME               |
      | rue            | 1 place de la Gare |
      | code postal    | 67000              |
      | ville          | Strasbourg         |
      | pays           | FR                 |
    Alors l'organisation "ACME" est enregistrée avec :
      | rue         | 1 place de la Gare |
      | code postal | 67000              |
      | ville       | Strasbourg         |
      | pays        | FR                 |
      | position    | 7.75, 48.58        |

  Scénario: Une adresse inconnue du géocodeur est gardée sans position
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Nulle-part |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    | Nulle-part |
      | position | aucune     |

  Scénario: Sans adresse, pas de position
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    |        |
      | position | aucune |
