# language: fr
Fonctionnalité: Créer une organisation à prospecter
  En tant que prospecteur
  Je veux enregistrer les organisations que je prospecte
  Afin de les retrouver et de les situer sur une carte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: L'organisation créée appartient à mon organisation
    Quand je crée l'organisation :
      | raison sociale  | ACME              |
      | forme juridique | SAS               |
      | type            | Éditeur           |
      | e-mail          | contact@acme.test |
      | téléphone       | 03 88 00 00 00    |
      | langue          | FR                |
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
    Et je suis informé que "Organization created !"

  Scénario: Une raison sociale suffit
    Quand je crée l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | e-mail | |

  Scénario: Un e-mail suffit
    Quand je crée l'organisation :
      | e-mail | contact@acme.test |
    Et je liste mes organisations
    Alors je trouve 1 organisation sur 1 page

  Scénario: Il faut au moins une raison sociale ou un e-mail
    Quand je crée l'organisation :
      | ville | Strasbourg |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"

  Scénario: Une organisation est toujours rattachée à un propriétaire
    Quand je crée une organisation sans propriétaire :
      | raison sociale | ACME |
    Alors c'est refusé : "Owner is missing"
    Et aucune organisation "ACME" n'est enregistrée

  Scénario: L'e-mail ne peut pas être celui d'une autre personne
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je crée l'organisation :
      | raison sociale | ACME                |
      | e-mail         | contact@globex.test |
    Alors c'est refusé : "Email already used"

  Scénario: L'organisation peut faire partie d'une holding
    Soit l'organisation "Groupe ACME" fait partie de mes organisations
    Quand je crée l'organisation :
      | raison sociale | ACME        |
      | holding        | Groupe ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | Groupe ACME |

  Scénario: La holding doit exister
    Quand je crée l'organisation :
      | raison sociale | ACME     |
      | holding        | Inconnue |
    Alors c'est introuvable
    Et aucune organisation "ACME" n'est enregistrée

  Scénario: Une adresse connue du géocodeur place l'organisation sur la carte
    Soit le géocodeur situe "Strasbourg" en 7.75, 48.58
    Quand je crée l'organisation :
      | raison sociale | ACME                  |
      | rue            | 1 place de la Gare    |
      | code postal    | 67000                 |
      | ville          | Strasbourg            |
      | pays           | FR                    |
    Alors l'organisation "ACME" est enregistrée avec :
      | rue         | 1 place de la Gare |
      | code postal | 67000              |
      | ville       | Strasbourg         |
      | pays        | FR                 |
      | position    | 7.75, 48.58        |

  Scénario: Une adresse inconnue du géocodeur est gardée sans position
    Quand je crée l'organisation :
      | raison sociale | ACME     |
      | ville          | Nulle-part |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    | Nulle-part |
      | position | aucune     |

  Scénario: Sans adresse, pas de position
    Quand je crée l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    |        |
      | position | aucune |

  Scénario: Le logo envoyé est enregistré dans le stockage
    Soit j'ai une image "acme.png" de 2048 octets
    Quand je crée l'organisation :
      | raison sociale | ACME     |
      | logo           | acme.png |
    Alors l'organisation "ACME" est enregistrée avec :
      | logo | acme.png (png, 2048 octets) |
    Et le stockage contient "acme.png"
