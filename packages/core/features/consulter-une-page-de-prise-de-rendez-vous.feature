# language: fr
Fonctionnalité: Consulter une page de prise de rendez-vous
  En tant que personne à qui un prospecteur a donné son lien public
  Je veux ouvrir sa page de prise de rendez-vous, sans me connecter
  Afin de voir comment je peux le rencontrer

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: Une page active propose les canaux de son prospecteur
    Soit ma prise de rendez-vous est active, en proposant la visio
    Quand on ouvre mon lien public
    Alors il propose mes créneaux
    Et il propose la visio seulement

  Scénario: Une page coupée ne propose aucun créneau
    Soit ma prise de rendez-vous est active
    Et je l'ai coupée
    Quand on ouvre mon lien public
    Alors il ne propose aucun créneau

  Scénario: Un lien inconnu ne mène à rien
    Quand on ouvre le lien public "AbCdEfGhIjKlMnOpQrStUv"
    Alors c'est introuvable
