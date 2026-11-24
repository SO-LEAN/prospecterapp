# language: fr
@SetWeeklyWindows
Fonctionnalité: Régler mes plages
  En tant que prospecteur
  Je veux dire, jour par jour, à quelles heures de la semaine on peut me réserver
  Afin qu'on ne me propose des rendez-vous qu'à ces heures-là

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et je lis l'heure de "Europe/Paris"

  Scénario: Mes plages horaires de la semaine
    Quand je règle mes plages :
      | jour  | de    | à     |
      | lundi | 14:00 | 17:00 |
      | jeudi | 09:00 | 12:00 |
    Alors mes plages sont :
      | jour  | de    | à     |
      | lundi | 14:00 | 17:00 |
      | jeudi | 09:00 | 12:00 |
    Et mes plages sont à l'heure de "Europe/Paris"

  Scénario: Régler mes plages remplace les précédentes
    Soit mes plages sont réglées
    Quand je règle mes plages :
      | jour     | de    | à     |
      | mercredi | 08:30 | 10:45 |
    Alors mes plages sont :
      | jour     | de    | à     |
      | mercredi | 08:30 | 10:45 |

  Scénario: Une plage tient au moins un créneau de 15 minutes, au quart d'heure
    Quand je règle une plage le lundi de 14h00 à 14h10
    Alors c'est refusé : "A window must last at least 15 minutes"

  Scénario: Une plage qui finit avant de commencer
    Quand je règle une plage le lundi de 17h00 à 14h00
    Alors c'est refusé : "A window must last at least 15 minutes"

  Scénario: Une plage hors du quart d'heure
    Quand je règle une plage le lundi de 14h05 à 17h00
    Alors c'est refusé : "A window must start and end on a quarter hour"

  Scénario: Deux plages le même jour
    Quand je règle mes plages :
      | jour  | de    | à     |
      | lundi | 09:00 | 12:00 |
      | lundi | 14:00 | 17:00 |
    Alors c'est refusé : "A day can hold only one window"

  Scénario: Je règle mes plages depuis New York
    Soit je lis l'heure de "America/New_York"
    Quand je règle une plage le lundi de 14h00 à 17h00
    Alors mes plages sont à l'heure de "America/New_York"

  Scénario: Un fuseau inconnu est refusé
    Quand je règle une plage le lundi de 14h00 à 17h00 à l'heure de "Mars/Olympus"
    Alors c'est refusé : "Unknown time zone Mars/Olympus"

  Scénario: Un jour inconnu est refusé
    Quand je règle une plage un jour qui n'existe pas, "funday"
    Alors c'est refusé : "A window must fall on a day of the week"
    Et le refus nomme le jour "funday"
