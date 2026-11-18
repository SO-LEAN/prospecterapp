# language: fr
Fonctionnalité: Consulter ma prise de rendez-vous
  En tant que prospecteur
  Je veux retrouver mes réglages de prise de rendez-vous
  Afin de les changer, et de savoir si mon lien est actif

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Jamais réglée, ma prise de rendez-vous est coupée et vide
    Alors mon lien de prise de rendez-vous est coupé
    Et je n'ai aucune plage
    Et je ne propose aucun canal
    Et je n'ai aucune période inactive
    Et je n'ai pas encore de lien public
    Et mon agenda n'est pas connecté

  Scénario: Elle dit si mon agenda est connecté
    Soit mon compte Google est connecté
    Alors mon agenda est connecté

  Scénario: Un agenda à reconnecter n'est pas connecté
    Soit mon compte Google est connecté
    Et Google refuse mon jeton
    Alors mon agenda n'est pas connecté

  Scénario: Mon lien actif est suspendu tant que mon agenda est à reconnecter
    Soit mon compte Google est connecté
    Et ma prise de rendez-vous est active
    Et Google refuse mon jeton
    Alors mon lien de prise de rendez-vous est suspendu

  Scénario: Mon lien actif avec mon agenda connecté n'est pas suspendu
    Soit mon compte Google est connecté
    Et ma prise de rendez-vous est active
    Alors mon lien de prise de rendez-vous n'est pas suspendu

  Scénario: Mes périodes inactives passées disparaissent d'elles-mêmes, au jour de mon fuseau
    Soit nous sommes le 20/11/2026 à 10h00
    Et j'ai ajouté une période inactive du 21/12/2026 au 04/01/2027
    Et j'ai ajouté une période inactive du 01/02/2027 au 05/02/2027
    Et je lis l'heure de "Europe/Paris"
    Quand nous sommes le "2027-01-04 23:30 UTC"
    Alors mes périodes inactives sont :
      | du         | au         |
      | 01/02/2027 | 05/02/2027 |
