# language: fr
@ConsultWhatIsToProcess
Fonctionnalité: Consulter ce que j'ai à traiter
  En tant que prospecteur
  Je veux voir les échanges planifiés dont l'heure est passée et que je n'ai marqués ni faits, ni manqués, ni annulés,
  et mes actions à faire du jour et en retard
  Afin d'avoir une vue claire sur le travail à faire et de ne rien laisser traîner

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Un échange planifié dont la fin est passée est à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 10/11/2026 à 14h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mes échanges à traiter
    Alors ils sont, du plus ancien au plus récent :
      | moment           | canal | avec         |
      | 10/11/2026 14:00 | Visio | Ada Lovelace |

  Scénario: Ce que j'ai à traiter nomme avec qui sont mes échanges, un contact et ses organisations, ou l'organisation elle-même
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Globex"
    Et j'ai planifié une visio avec "Ada Lovelace" le 10/11/2026 à 14h00
    Et j'ai planifié un rendez-vous avec "ACME" le 11/11/2026 à 9h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mes échanges à traiter
    Alors ils sont, du plus ancien au plus récent :
      | moment           | montré avec  | ses organisations |
      | 10/11/2026 14:00 | Ada Lovelace | ACME, Globex      |
      | 11/11/2026 09:00 | ACME         |                   |

  Scénario: Un échange traité n'est plus à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 10/11/2026 à 14h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je la marque faite
    Alors je n'ai plus d'échange à traiter

  Scénario: Un échange en cours n'est pas encore à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 13/11/2026 à 9h45
    Et nous sommes le 13/11/2026 à 10h00
    Alors je n'ai pas d'échange à traiter

  Scénario: Une action échue et pas faite est à traiter
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 12/11/2026
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte ce que j'ai à traiter
    Alors il y a "Envoyer mon CV", en retard d'un jour

  Scénario: Une action du jour est à traiter dès le matin
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 13/11/2026
    Et nous sommes le 13/11/2026 à 8h00
    Quand je consulte ce que j'ai à traiter
    Alors il y a "Envoyer mon CV", pour aujourd'hui

  Scénario: Les actions à traiter vont de la plus en retard à celles du jour
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 13/11/2026 à 9h00
    Et j'ai noté à faire pour "Ada Lovelace" "Relancer" pour le 10/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Préparer la proposition" pour le 13/11/2026
    Et nous sommes le 13/11/2026 à 8h00
    Quand je consulte ce que j'ai à traiter
    Alors mes actions à traiter sont :
      | texte                   | retard        |
      | Relancer                | 3 jours       |
      | Préparer la proposition | aujourd'hui   |
      | Envoyer mon CV          | aujourd'hui   |

  Scénario: Une action à venir ou faite n'est pas à traiter
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Relancer" pour le 10/11/2026
    Et j'ai coché "Relancer"
    Et nous sommes le 13/11/2026 à 23h00
    Quand je consulte ce que j'ai à traiter
    Alors je n'ai rien à traiter

  Scénario: Le jour d'une action à traiter est celui du lecteur
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 13/11/2026
    Et nous sommes le 13/11/2026 à 3h00
    Et je lis l'heure de "America/New_York"
    Quand je consulte ce que j'ai à traiter
    Alors je n'ai rien à traiter

  Scénario: Les contacts à rattacher sont à traiter
    Soit nous sommes le 18/11/2026 à 10h00
    Et "Grace Hopper" (grace@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et nous sommes le 21/11/2026 à 10h00
    Quand je consulte ce que j'ai à traiter
    Alors il y a "Grace Hopper" à rattacher, effacée le 20/12/2026 sans rattachement

  Scénario: Un contact à rattacher effacé n'est plus à rattacher, avant même que les réactions suivent
    Soit nous sommes le 18/11/2026 à 10h00
    Et "Grace Hopper" (grace@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et "Alan Turing" (alan@acme.fr) a pris une visio avec moi le 20/11/2026 à 16h00
    Et nous sommes le 21/11/2026 à 10h00
    Et les réactions aux gestes n'ont pas encore suivi
    Quand j'efface la fiche de "Grace Hopper"
    Et je consulte ce que j'ai à traiter
    Alors les contacts à rattacher sont, le prochain effacé d'abord :
      | contact     |
      | Alan Turing |

  Scénario: Un contact à rattacher n'est à traiter que par le prospecteur qui a le rendez-vous
    Soit nous sommes le 18/11/2026 à 10h00
    Et "Grace Hopper" (grace@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand je consulte ce que j'ai à traiter
    Alors je n'ai rien à traiter
