# language: fr
@ConsultMyDay
Fonctionnalité: Consulter ma journée
  En tant que prospecteur
  Je veux voir sur le tableau de bord mes échanges planifiés du jour et les prochains à venir
  Afin de savoir, en ouvrant l'app le matin, qui j'ai au programme

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le tableau de bord montre mes échanges planifiés du jour, puis les 5 prochains
    Soit j'ai planifié avec "Ada Lovelace" un appel le 13/11/2026 à 9h00, une visio le 13/11/2026 à 16h00 et 6 rendez-vous les jours suivants
    Quand je consulte le tableau de bord
    Alors il montre aujourd'hui l'appel de 9h00 puis la visio de 16h00
    Et les 5 prochains rendez-vous, du plus proche au plus lointain

  Scénario: Le tableau de bord nomme qui je vois aujourd'hui, et ses organisations
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Globex"
    Et j'ai planifié avec "Ada Lovelace" un appel le 13/11/2026 à 9h00, une visio le 13/11/2026 à 16h00 et 0 rendez-vous les jours suivants
    Quand je consulte le tableau de bord
    Alors il montre aujourd'hui, avec :
      | échange           | montré avec  | ses organisations |
      | l'appel de 9h00   | Ada Lovelace | ACME, Globex      |
      | la visio de 16h00 | Ada Lovelace | ACME, Globex      |

  Scénario: Un échange planifié dont l'heure est passée est à traiter
    Soit j'ai planifié avec "Ada Lovelace" un appel le 13/11/2026 à 9h00, une visio le 13/11/2026 à 16h00 et 0 rendez-vous les jours suivants
    Quand je consulte le tableau de bord
    Alors l'appel de 9h00 est à traiter, la visio de 16h00 ne l'est pas

  Scénario: Un échange annulé n'est pas montré
    Soit j'ai annulé une visio avec "Ada Lovelace" le 13/11/2026 à 16h00
    Quand je consulte le tableau de bord
    Alors il ne montre pas cette visio

  Scénario: Un échange déjà fait n'est plus au programme
    Soit j'ai noté un appel avec "Ada Lovelace" le 13/11/2026 à 9h00
    Quand je consulte le tableau de bord
    Alors il ne montre rien aujourd'hui

  Scénario: Le tableau de bord ne montre que mes échanges
    Soit un autre prospecteur de "SOLEAN IT" a planifié un appel avec "Ada Lovelace" le 13/11/2026 à 16h00
    Quand je consulte le tableau de bord
    Alors il ne montre rien aujourd'hui
