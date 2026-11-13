# language: fr
Fonctionnalité: Consulter mon calendrier
  En tant que prospecteur
  Je veux voir mes échanges, passés et à venir, période par période
  Afin de voir ma semaine d'un coup d'œil et de retrouver un échange dans le temps

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

  Scénario: Mon calendrier ne montre que mes échanges
    Soit un autre prospecteur de "SOLEAN IT" a planifié un appel avec "Ada Lovelace" le 17/11/2026 à 9h00
    Quand je consulte mon calendrier du 16/11/2026 au 22/11/2026
    Alors il ne montre rien

  Scénario: Une période se lit en une fois, de six semaines au plus
    Quand je consulte mon calendrier du 01/11/2026 au 31/12/2026
    Alors c'est refusé : "A calendar period lasts six weeks at most"
