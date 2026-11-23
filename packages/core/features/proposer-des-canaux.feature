# language: fr
Fonctionnalité: Proposer des canaux
  En tant que prospecteur
  Je veux choisir comment on peut me rencontrer quand on me réserve : un appel, une visio
  Afin de ne proposer que ce que je veux bien faire

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: Je propose l'appel et la visio
    Quand je propose les canaux "Appel, Visio"
    Alors mes canaux proposés sont "Appel, Visio"

  Scénario: Je ne propose plus que la visio
    Soit je propose les canaux "Appel, Visio"
    Quand je propose les canaux "Visio"
    Alors mes canaux proposés sont "Visio"

  Scénario: Coupée, ma prise de rendez-vous peut ne proposer aucun canal
    Soit je propose les canaux "Appel"
    Quand je ne propose plus aucun canal
    Alors je ne propose aucun canal

  Scénario: Active, ma prise de rendez-vous propose au moins un canal
    Soit ma prise de rendez-vous est active
    Quand je ne propose plus aucun canal
    Alors c'est refusé : "Booking must offer at least one channel"
