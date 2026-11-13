# language: fr
Fonctionnalité: Traiter un échange planifié
  En tant que prospecteur
  Je veux marquer un échange planifié fait ou manqué, l'annuler ou le déplacer
  Afin de ne jamais laisser un prospect sans savoir ce qu'il est advenu de ce qui était prévu

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00

  Scénario: Un échange eu est marqué fait, avec son compte rendu
    Soit nous sommes le 17/11/2026 à 15h00
    Quand je marque cette visio faite avec le texte "Mission de 3 mois en mars, TJM à envoyer"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal | statut | texte                                    |
      | 17/11/2026 14:00 | Visio | Fait   | Mission de 3 mois en mars, TJM à envoyer |
    Et elle n'est plus dans ses échanges à venir

  Scénario: Le moment d'un échange marqué fait se corrige, jamais dans le futur
    Soit nous sommes le 17/11/2026 à 15h00
    Quand je marque cette visio faite le 17/11/2026 à 14h20
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | statut |
      | 17/11/2026 14:20 | Fait   |

  Scénario: Un échange marqué fait n'est jamais dans le futur
    Soit nous sommes le 17/11/2026 à 15h00
    Quand je marque cette visio faite le 17/11/2026 à 16h00
    Alors c'est refusé : "An exchange is never in the future"

  Scénario: Un échange n'est fait qu'une fois son heure venue
    Soit nous sommes le 17/11/2026 à 10h00
    Quand je marque cette visio faite
    Alors c'est refusé : "A planned exchange is done or missed once its time has come"

  Scénario: Un échange n'est manqué qu'une fois son heure venue
    Soit nous sommes le 17/11/2026 à 10h00
    Quand je marque cette visio manquée
    Alors c'est refusé : "A planned exchange is done or missed once its time has come"

  Scénario: Un échange où le contact n'est pas venu est manqué
    Soit nous sommes le 17/11/2026 à 15h00
    Quand je marque cette visio manquée
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal | statut |
      | 17/11/2026 14:00 | Visio | Manqué |

  Scénario: Un échange annulé reste barré dans la timeline
    Soit nous sommes le 13/11/2026 à 10h00
    Quand j'annule cette visio
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal | statut  |
      | 17/11/2026 14:00 | Visio | Annulé  |
    Et elle n'est plus dans ses échanges à venir

  Scénario: Un échange annulé se range dans la timeline au moment où il l'a été
    Soit nous sommes le 13/11/2026 à 10h00
    Et j'ai noté un appel envoyé à "Ada Lovelace" le 12/11/2026 à 9h00
    Et j'ai noté un appel envoyé à "Ada Lovelace" le 13/11/2026 à 9h30
    Quand j'annule cette visio
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | statut |
      | 17/11/2026 14:00 | Annulé |
      | 13/11/2026 09:30 | Fait   |
      | 12/11/2026 09:00 | Fait   |

  Scénario: On dit, si on veut, que le contact a annulé
    Quand j'annule cette visio à la demande du contact
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | statut | annulé par |
      | Annulé | le contact |

  Scénario: Un échange déplacé change de moment et garde sa durée
    Quand je déplace cette visio au 18/11/2026 à 9h00
    Alors les échanges à venir de "Ada Lovelace" sont, du plus proche au plus lointain :
      | moment           | canal | durée  | statut  |
      | 18/11/2026 09:00 | Visio | 30 min | À venir |

  Scénario: Un échange déplacé change de place dans mon agenda
    Quand je déplace cette visio au 18/11/2026 à 9h00
    Alors je vois ces échanges qui chevauchent le 18/11/2026 à 9h15 pour 30 minutes :
      | interlocuteur | canal | de               | à                |
      | Ada Lovelace  | Visio | 18/11/2026 09:00 | 18/11/2026 09:30 |
    Et rien ne chevauche le 17/11/2026 à 14h00 pour 30 minutes

  Scénario: On ne déplace un échange que dans le futur
    Soit nous sommes le 13/11/2026 à 10h00
    Quand je déplace cette visio au 10/11/2026 à 9h00
    Alors c'est refusé : "A planned exchange is in the future"

  Scénario: Un échange annulé n'est plus dans mon agenda
    Quand j'annule cette visio
    Alors rien ne chevauche le 17/11/2026 à 14h00 pour 30 minutes

  Scénario: Seul un échange à venir se déplace, s'annule, se marque fait ou manqué
    Soit j'ai annulé cette visio
    Quand je la déplace au 18/11/2026 à 9h00
    Alors c'est refusé : "Only an upcoming exchange may change"

  Scénario: Un échange fait ne s'annule plus
    Soit nous sommes le 17/11/2026 à 15h00
    Et j'ai marqué cette visio faite
    Quand j'annule cette visio
    Alors c'est refusé : "Only an upcoming exchange may change"

  Scénario: Seul son titulaire traite un échange planifié
    Soit je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand j'annule cette visio
    Alors c'est refusé : "Only its holder handles a planned exchange"

  Scénario: Ce que son titulaire peut faire d'un échange à venir, avant son heure
    Soit nous sommes le 17/11/2026 à 10h00
    Alors je peux sur l'échange :
      | corriger le texte   |
      | supprimer l'échange |
      | joindre un fichier       |
      | retirer une pièce jointe |
      | déplacer            |
      | annuler             |

  Scénario: Ce que son titulaire peut faire d'un échange à venir, son heure venue
    Soit nous sommes le 17/11/2026 à 14h00
    Alors je peux sur l'échange :
      | corriger le texte   |
      | supprimer l'échange |
      | joindre un fichier       |
      | retirer une pièce jointe |
      | marquer fait        |
      | marquer manqué      |
      | déplacer            |
      | annuler             |

  Scénario: Un autre prospecteur ne traite pas l'échange planifié d'un collègue
    Soit nous sommes le 17/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Alors je peux sur l'échange :
      | corriger le texte   |
      | supprimer l'échange |
      | joindre un fichier       |
      | retirer une pièce jointe |

  Scénario: Un échange manqué se corrige comme un échange fait
    Soit nous sommes le 17/11/2026 à 15h00
    Et j'ai marqué cette visio manquée
    Alors je peux sur l'échange :
      | corriger l'échange  |
      | corriger le texte   |
      | supprimer l'échange |
      | joindre un fichier       |
      | retirer une pièce jointe |

  Scénario: Les échanges à venir d'un contact effacé sont annulés
    Soit nous sommes le 13/11/2026 à 10h00
    Quand j'efface la fiche de "Ada Lovelace"
    Alors la timeline de "ACME" est, du plus récent au plus ancien :
      | moment           | canal | statut | contact |
      | 17/11/2026 14:00 | Visio | Annulé | effacé  |
    Et les échanges à venir de "ACME" sont, du plus proche au plus lointain :
      | moment | canal | statut |
