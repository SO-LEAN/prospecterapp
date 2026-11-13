# language: fr
Fonctionnalité: Planifier un échange
  En tant que prospecteur
  Je veux planifier un appel, un rendez-vous ou une visio avec un contact, ou avec l'organisation elle-même
  Afin de savoir à tout moment ce qui est prévu avec qui

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: L'échange planifié apparaît dans les échanges à venir du contact
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors les échanges à venir de "Ada Lovelace" sont, du plus proche au plus lointain :
      | moment           | canal | durée  | statut  |
      | 17/11/2026 14:00 | Visio | 30 min | À venir |
    Et cet échange n'est pas dans ses échanges passés

  Scénario: Les échanges à venir se lisent du plus proche au plus lointain, les échanges passés à la suite
    Soit j'ai noté un appel envoyé à "Ada Lovelace" le 12/11/2026 à 9h00
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 20/11/2026 à 9h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 14/11/2026 à 9h00
    Alors les échanges à venir de "Ada Lovelace" sont, du plus proche au plus lointain :
      | moment           | canal       | statut  |
      | 14/11/2026 09:00 | Appel       | À venir |
      | 20/11/2026 09:00 | Rendez-vous | À venir |
    Et la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal | statut |
      | 12/11/2026 09:00 | Appel | Fait   |

  Scénario: La durée d'un échange planifié est de 30 minutes par défaut
    Quand je planifie un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00 pour 1 heure
    Alors il dure de 14h00 à 15h00

  Scénario: Un échange planifié dure de 5 minutes à 8 heures
    Quand je planifie un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 9h00 pour 9 heures
    Alors c'est refusé : "A planned exchange lasts from 5 minutes to 8 hours"

  Scénario: Un échange planifié est dans le futur
    Quand je planifie un appel avec "Ada Lovelace" le 12/11/2026 à 9h00
    Alors c'est refusé : "A planned exchange is in the future"

  Scénario: Seuls un appel, un rendez-vous ou une visio se planifient
    Quand je planifie un SMS avec "Ada Lovelace" le 17/11/2026 à 9h00
    Alors c'est refusé : "Only a call, a meeting or a video call may be planned"

  Scénario: L'échange planifié est à moi
    Quand je planifie un appel avec "Ada Lovelace" le 17/11/2026 à 9h00
    Alors j'en suis le titulaire

  Scénario: L'échange planifié est gardé pour les organisations du contact
    Soit l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Quand je planifie un appel avec "Ada Lovelace" le 17/11/2026 à 9h00
    Alors l'échange est gardé pour "ACME" et "Groupe ACME"
    Et il est dans les échanges à venir de "Groupe ACME"

  Scénario: Je planifie depuis la fiche d'une organisation, avec elle-même
    Quand je planifie un rendez-vous avec "ACME" le 17/11/2026 à 9h00
    Alors il est dans les échanges à venir de "ACME"

  Scénario: Je planifie depuis la fiche d'une organisation, avec un de ses contacts
    Quand je planifie depuis "ACME" une visio avec "Ada Lovelace" le 17/11/2026 à 9h00
    Alors il est dans les échanges à venir de "Ada Lovelace"

  Scénario: Je vois mes échanges planifiés qui chevauchent un créneau
    Soit le contact "Grace Hopper" de "ACME"
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai planifié un appel avec "Grace Hopper" le 17/11/2026 à 15h00
    Quand je regarde ce qui chevauche le 17/11/2026 à 14h15 pour 30 minutes
    Alors je vois ces échanges qui le chevauchent :
      | interlocuteur | canal       | de               | à                |
      | Ada Lovelace  | Rendez-vous | 17/11/2026 14:00 | 17/11/2026 14:30 |

  Scénario: Un échange qui finit quand le créneau commence ne le chevauche pas
    Soit j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je regarde ce qui chevauche le 17/11/2026 à 14h30 pour 30 minutes
    Alors rien ne le chevauche

  Scénario: Les échanges planifiés par un collègue ne chevauchent pas les miens
    Soit "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Michel" a planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je regarde ce qui chevauche le 17/11/2026 à 14h00 pour 30 minutes
    Alors rien ne le chevauche

  Scénario: Un échange planifié se lit comme les autres, sans le droit aux données personnelles aussi
    Soit j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Alors les échanges à venir de "Ada Lovelace" sont, du plus proche au plus lointain :
      | interlocuteur | canal       | statut  |
      | A***a L***e   | Rendez-vous | À venir |
