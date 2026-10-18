# language: fr
Fonctionnalité: Déménager mon organisation
  En tant qu'utilisateur
  Je veux tenir à jour l'adresse de mon organisation
  Afin de partir de chez nous pour aller voir les organisations que je prospecte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58

  Scénario: Une adresse connue du géocodeur donne la position de mon organisation
    Quand je déménage mon organisation à l'adresse :
      | rue         | 1 place Kléber |
      | code postal | 67000          |
      | ville       | Strasbourg     |
      | pays        | FR             |
    Alors mon compte est enregistré avec :
      | rue de mon organisation      | 1 place Kléber |
      | ville de mon organisation    | Strasbourg     |
      | position de mon organisation | 7.75, 48.58    |
    Quand je consulte mon compte
    Alors je vois mon compte :
      | ville de mon organisation    | Strasbourg  |
      | position de mon organisation | 7.75, 48.58 |

  Scénario: Une adresse inconnue du géocodeur est gardée sans position
    Quand je déménage mon organisation à l'adresse :
      | ville | Nulle-part |
    Alors mon compte est enregistré avec :
      | ville de mon organisation    | Nulle-part |
      | position de mon organisation | aucune     |

  Scénario: Sans adresse, plus de position
    Soit je déménage mon organisation à l'adresse :
      | ville | Strasbourg |
    Quand j'efface l'adresse de mon organisation
    Alors mon compte est enregistré avec :
      | ville de mon organisation    |        |
      | position de mon organisation | aucune |

  Scénario: Un déménagement date la dernière modification de mon organisation
    Soit nous sommes le "2026-10-20 14:00"
    Quand je déménage mon organisation à l'adresse :
      | ville | Strasbourg |
    Alors mon compte est enregistré avec :
      | mon organisation modifiée le | 2026-10-20 14:00 |
