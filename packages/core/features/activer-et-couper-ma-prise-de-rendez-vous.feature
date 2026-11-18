# language: fr
Fonctionnalité: Activer et couper ma prise de rendez-vous
  En tant que prospecteur
  Je veux activer mon lien public de prise de rendez-vous, et le couper du jour au lendemain
  Afin qu'on me réserve quand je le veux bien, sans perdre mes réglages

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: J'active mon lien public
    Soit mes plages sont réglées
    Quand j'active ma prise de rendez-vous, en proposant l'appel et la visio
    Alors mon lien de prise de rendez-vous est actif
    Et j'ai un lien public, à un code aléatoire

  Scénario: Sans Google connecté, mon lien ne s'active pas
    Soit j'ai déconnecté mon compte Google
    Quand j'active ma prise de rendez-vous, en proposant l'appel et la visio
    Alors c'est refusé : "Booking needs a connected calendar, which invites the booker"

  Scénario: Avec Google à reconnecter, mon lien ne s'active pas
    Soit Google refuse mon jeton
    Quand j'active ma prise de rendez-vous, en proposant l'appel et la visio
    Alors c'est refusé : "Booking needs a connected calendar, which invites the booker"

  Scénario: Il faut au moins un canal
    Quand j'active ma prise de rendez-vous sans proposer ni l'appel ni la visio
    Alors c'est refusé : "Booking offers at least one channel"

  Scénario: Je coupe mon lien d'un coup
    Soit ma prise de rendez-vous est active
    Quand je la coupe
    Alors mon lien public ne propose plus aucun créneau
    Et mes réglages sont gardés

  Scénario: Réactivé, mon lien reste le même
    Soit ma prise de rendez-vous est active
    Et je l'ai coupée
    Quand je l'active à nouveau
    Alors mon lien public est le même qu'avant
    Et il propose mes créneaux
