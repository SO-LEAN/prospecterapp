# language: fr
Fonctionnalité: Déménager une organisation
  En tant que prospecteur
  Je veux tenir à jour l'adresse d'une organisation
  Afin de la retrouver au bon endroit sur la carte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | e-mail | contact@acme.test |
      | ville  | Strasbourg        |

  Scénario: Une nouvelle adresse connue du géocodeur donne la nouvelle position
    Soit le géocodeur situe "Colmar" en 7.36, 48.08
    Quand je déménage "ACME" à l'adresse :
      | rue         | 2 rue des Clefs |
      | code postal | 68000           |
      | ville       | Colmar          |
      | pays        | FR              |
    Alors l'organisation "ACME" est enregistrée avec :
      | rue         | 2 rue des Clefs   |
      | ville       | Colmar            |
      | position    | 7.36, 48.08       |
      | e-mail      | contact@acme.test |

  Scénario: Une adresse inconnue du géocodeur est gardée sans position
    Quand je déménage "ACME" à l'adresse :
      | ville | Nulle-part |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    | Nulle-part |
      | position | aucune     |

  Scénario: Sans adresse, plus de position
    Quand j'efface l'adresse de "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    |        |
      | position | aucune |

  Scénario: Mon organisation ne se déménage pas comme une organisation prospectée
    Quand je déménage "SOLEAN IT" à l'adresse :
      | ville | Strasbourg |
    Alors c'est introuvable

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je déménage "Initech" à l'adresse :
      | ville | Strasbourg |
    Alors c'est introuvable
