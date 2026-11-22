# language: fr
Fonctionnalité: Ajouter une période inactive
  En tant que prospecteur
  Je veux dire les jours où je suis absent, mes vacances par exemple
  Afin qu'on ne puisse pas me réserver ces jours-là, quelles que soient mes plages

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 20/11/2026 à 10h00

  Scénario: Une période inactive
    Quand j'ajoute une période inactive du 21/12/2026 au 04/01/2027
    Alors on ne peut pas me réserver du 21/12/2026 au 04/01/2027 inclus
    Et on peut me réserver le 20/12/2026 et le 05/01/2027

  Scénario: Une période inactive d'un seul jour
    Quand j'ajoute une période inactive du 24/12/2026 au 24/12/2026
    Alors on ne peut pas me réserver du 24/12/2026 au 24/12/2026 inclus
    Et on peut me réserver le 23/12/2026 et le 25/12/2026

  Scénario: Une période inactive finit au plus tôt le jour où elle commence
    Quand j'ajoute une période inactive du 04/01/2027 au 21/12/2026
    Alors c'est refusé : "An inactive period ends on or after its first day"

  Scénario: Je retire une période inactive
    Soit j'ai ajouté une période inactive du 21/12/2026 au 04/01/2027
    Quand je retire la période inactive du 21/12/2026 au 04/01/2027
    Alors on peut me réserver le 21/12/2026 et le 04/01/2027
    Et je n'ai aucune période inactive

  Scénario: Je retire une période inactive, les autres restent
    Soit j'ai ajouté une période inactive du 21/12/2026 au 04/01/2027
    Et j'ai ajouté une période inactive du 10/02/2027 au 12/02/2027
    Quand je retire la période inactive du 21/12/2026 au 04/01/2027
    Alors on peut me réserver le 21/12/2026 et le 04/01/2027
    Et on ne peut pas me réserver du 10/02/2027 au 12/02/2027 inclus

  Scénario: On ne retire pas une période inactive inconnue
    Quand je retire une période inactive que je n'ai pas
    Alors c'est introuvable
