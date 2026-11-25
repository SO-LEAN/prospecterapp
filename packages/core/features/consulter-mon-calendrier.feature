# language: fr
@ConsultCalendar
Fonctionnalité: Consulter mon calendrier
  En tant que prospecteur
  Je veux voir mes échanges, passés et à venir, et mes actions à faire, période par période
  Afin de voir ma semaine d'un coup d'œil, de retrouver un échange dans le temps et de voir ce qui est en retard

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le calendrier d'une période montre mes échanges, passés et à venir
    Soit j'ai noté un appel avec "Ada Lovelace" le 03/11/2026 à 9h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai annulé un rendez-vous avec "Ada Lovelace" le 20/11/2026 à 10h00
    Quand je consulte mon calendrier du 26/10/2026 au 06/12/2026
    Alors il montre, dans l'ordre :
      | moment           | fin   | canal       | avec         | statut  |
      | 03/11/2026 09:00 | 09:00 | Appel       | Ada Lovelace | Fait    |
      | 17/11/2026 14:00 | 14:30 | Visio       | Ada Lovelace | À venir |
      | 20/11/2026 10:00 | 10:30 | Rendez-vous | Ada Lovelace | Annulé  |

  Scénario: Seuls mes échanges planifiés se déplacent depuis mon calendrier
    Soit j'ai noté un appel avec "Ada Lovelace" le 03/11/2026 à 9h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai annulé un rendez-vous avec "Ada Lovelace" le 20/11/2026 à 10h00
    Quand je consulte mon calendrier du 26/10/2026 au 06/12/2026
    Alors il montre, dans l'ordre :
      | moment           | statut  | déplaçable |
      | 03/11/2026 09:00 | Fait    | non        |
      | 17/11/2026 14:00 | À venir | oui        |
      | 20/11/2026 10:00 | Annulé  | non        |

  Scénario: Un échange planifié dont l'heure est passée se déplace encore depuis mon calendrier
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et nous sommes le 17/11/2026 à 15h00
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il montre, dans l'ordre :
      | moment           | déplaçable |
      | 17/11/2026 14:00 | oui        |

  Scénario: Le calendrier ne montre que les échanges de la période
    Soit j'ai planifié un appel avec "Ada Lovelace" le 16/11/2026 à 0h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 23/11/2026 à 0h00
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il montre, dans l'ordre :
      | moment           | canal |
      | 16/11/2026 00:00 | Appel |

  Scénario: Un échange déplacé change de jour dans mon calendrier
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et je la déplace au 24/11/2026 à 9h00
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il ne montre rien

  Scénario: Sans le droit aux données personnelles, je vois avec qui, le texte signalé
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Et j'ai planifié un appel avec "Ada Lovelace" le 17/11/2026 à 9h00 avec le texte "Parler de son salaire actuel"
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il montre, dans l'ordre :
      | moment           | canal | avec         | de   | signalé |
      | 17/11/2026 09:00 | Appel | Ada Lovelace | ACME | oui     |

  Scénario: Mon calendrier ne montre que mes échanges
    Soit un autre prospecteur de "SOLEAN IT" a planifié un appel avec "Ada Lovelace" le 17/11/2026 à 9h00
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il ne montre rien

  Scénario: Une période se lit en une fois, de six semaines au plus
    Quand je consulte mon calendrier du 01/11/2026 au 31/12/2026
    Alors c'est refusé : "A calendar period can last at most six weeks"

  Scénario: Le calendrier montre mes actions à faire à leur échéance
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 14/11/2026, toute la journée

  Scénario: Une action sans heure reste à son jour, quel que soit le fuseau du lecteur
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Et je lis l'heure de "America/New_York"
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 14/11/2026, toute la journée

  Scénario: Une action avec une heure est à son heure, sans durée
    Soit j'ai noté à faire pour "Ada Lovelace" "Appeler la RH" pour le 14/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Appeler la RH" le 14/11/2026 à 10h00

  Scénario: Le calendrier ne montre que les actions à faire de la période
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 08/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Relancer" pour le 16/11/2026
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il ne montre rien

  Scénario: Une action faite reste dans le calendrier, faite
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Et je l'ai cochée
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 14/11/2026, faite, que je peux décocher

  Scénario: Une action échue et pas faite est en retard dans le calendrier
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 12/11/2026
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 12/11/2026, en retard

  Scénario: Une action du jour sans heure n'est pas en retard avant la fin du jour
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 13/11/2026
    Et nous sommes le 13/11/2026 à 18h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 13/11/2026, pas en retard

  Scénario: Une action du jour à une heure passée est en retard
    Soit j'ai noté à faire pour "Ada Lovelace" "Appeler la RH" pour le 13/11/2026 à 9h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Appeler la RH" le 13/11/2026 à 9h00, en retard

  Scénario: Une action faite n'est pas en retard
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 12/11/2026
    Et je l'ai cochée
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre "Envoyer mon CV" le 12/11/2026, pas en retard

  Scénario: Un échange planifié terminé et pas traité est en retard dans le calendrier
    Soit nous sommes le 12/11/2026 à 9h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 12/11/2026 à 14h00 pour 30 minutes
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre l'appel avec "Ada Lovelace" le 12/11/2026 à 14h00, en retard

  Scénario: Un échange planifié en cours n'est pas en retard
    Soit nous sommes le 12/11/2026 à 9h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 13/11/2026 à 9h45 pour 30 minutes
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre l'appel avec "Ada Lovelace" le 13/11/2026 à 9h45, pas en retard

  Scénario: Un échange passé fait, manqué ou annulé n'est pas en retard
    Soit nous sommes le 11/11/2026 à 9h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 11/11/2026 à 14h00
    Et j'ai annulé un rendez-vous avec "Ada Lovelace" le 12/11/2026 à 11h00
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai marqué cette visio manquée
    Et j'ai noté un appel avec "Ada Lovelace" le 12/11/2026 à 9h00
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il montre la visio avec "Ada Lovelace" le 11/11/2026 à 14h00, pas en retard
    Et il montre le rendez-vous avec "Ada Lovelace" le 12/11/2026 à 11h00, pas en retard
    Et il montre l'appel avec "Ada Lovelace" le 12/11/2026 à 9h00, pas en retard

  Scénario: Mon calendrier ne montre que mes actions à faire
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Grace Hopper" a noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand je consulte mon calendrier du 09/11/2026 au 15/11/2026
    Alors il ne montre rien
