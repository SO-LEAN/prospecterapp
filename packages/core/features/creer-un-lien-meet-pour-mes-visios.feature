# language: fr
Fonctionnalité: Créer un lien Meet pour mes visios
  En tant que prospecteur dont le compte Google est connecté
  Je veux que Prospecter crée un lien Google Meet pour la visio que je planifie sans lien
  Afin de ne plus passer par Google pour l'obtenir

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: Une visio planifiée sans lien reçoit un lien Meet
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Alors une fois poussée, la visio a un lien Meet
    Et l'événement a lieu à ce lien Meet

  Scénario: Une visio planifiée depuis l'organisation reçoit un lien Meet aussi
    Quand je planifie depuis "ACME" une visio avec "ACME" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Alors une fois poussée, la visio a un lien Meet

  Scénario: Mon agenda « Prospecter » supprimé dans Google, la visio reçoit son lien dans l'agenda recréé
    Soit j'ai supprimé l'agenda « Prospecter » de Google
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Alors une fois poussée, la visio a un lien Meet

  Scénario: Une visio avec son lien le garde
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00 au lien "https://teams.microsoft.com/l/meetup-join/xyz", en demandant un lien Meet
    Alors son lien reste "https://teams.microsoft.com/l/meetup-join/xyz"
    Et Google n'a été prié de créer aucun lien Meet

  Scénario: Sans le demander, une visio sans lien n'en reçoit pas
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors la visio n'a pas de lien
    Et Google n'a été prié de créer aucun lien Meet

  Scénario: Le lien n'est créé qu'une fois
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Quand je la déplace au 18/11/2026 à 9h00
    Alors Google n'a été prié qu'une fois de créer un lien Meet

  Scénario: Un lien Meet retiré ne revient pas
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Quand je corrige le lien de cet échange en ""
    Et la planification de la visio est poussée de nouveau
    Alors la visio n'a pas de lien

  Scénario: Sans connexion Google, la visio attend son lien
    Soit je déconnecte mon compte Google
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Alors la visio n'a pas de lien
    Et elle attend son lien

  Scénario: Une visio annulée n'attend plus son lien
    Soit je déconnecte mon compte Google
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Quand j'annule cette visio
    Alors elle n'attend plus son lien

  Scénario: Seule une visio demande un lien
    Quand je planifie un appel avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Alors c'est refusé : "Only a video call has a link"
