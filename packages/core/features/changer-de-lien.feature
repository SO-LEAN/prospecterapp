# language: fr
@ChangeBookingLink
Fonctionnalité: Changer de lien
  En tant que prospecteur
  Je veux changer le code de mon lien public
  Afin que ceux à qui je l'avais donné ne puissent plus me réserver

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: Je change de lien
    Soit ma prise de rendez-vous est active
    Quand je change de lien
    Alors l'ancien lien ne mène plus à rien
    Et le nouveau propose mes créneaux

  Scénario: Sans lien encore, je n'en change pas
    Quand je change de lien
    Alors c'est refusé : "The booking page has no link to change yet"
