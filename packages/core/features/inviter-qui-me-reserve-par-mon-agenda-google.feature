# language: fr
Fonctionnalité: Inviter qui me réserve par mon agenda Google
  En tant que personne qui a réservé un créneau d'un prospecteur par son lien public
  Je veux recevoir l'invitation de son agenda Google, avec le lien Meet ou le numéro où il m'appellera, et ses mises à jour
  Afin de savoir quand et comment nous nous parlerons, sans que le prospecteur n'envoie rien lui-même

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour     | de    | à     |
      | mardi    | 09:00 | 09:30 |
      | mercredi | 10:00 | 10:30 |
    Et ma prise de rendez-vous est active

  Scénario: Une visio réservée invite le demandeur, avec un lien Meet
    Quand on réserve une visio par mon lien public le 24/11/2026 à 9h00
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace » le 24/11/2026 de 9h00 à 9h15
    Et l'événement invite "ada@acme.fr"
    Et une fois poussée, la visio a un lien Meet
    Et Google envoie l'invitation à "ada@acme.fr"

  Scénario: Un appel réservé invite le demandeur, avec le numéro où je l'appellerai
    Soit mon profil est :
      | prénom | Alan                |
      | nom    | Turing              |
      | e-mail | alan@solean-it.test |
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00
    Alors l'événement invite "ada@acme.fr"
    Et l'événement dit que "Alan" l'appellera au 06 12 34 56 78

  Scénario: Un rendez-vous réservé que je déplace prévient le demandeur
    Soit on a réservé une visio par mon lien public le 24/11/2026 à 9h00
    Quand je la déplace au 25/11/2026 à 10h00
    Alors Google envoie la mise à jour à "ada@acme.fr"

  Scénario: Un rendez-vous réservé que j'annule prévient le demandeur
    Soit on a réservé une visio par mon lien public le 24/11/2026 à 9h00
    Quand j'annule cette visio
    Alors l'événement n'est plus dans mon agenda Google
    Et Google envoie l'annulation à "ada@acme.fr"

  Scénario: Marquer un rendez-vous réservé fait ne prévient personne
    Soit on a réservé une visio par mon lien public le 24/11/2026 à 9h00
    Et nous sommes le 24/11/2026 à 9h30
    Quand je la marque faite
    Alors Google n'envoie rien à "ada@acme.fr"

  Scénario: Un rendez-vous réservé passé que je supprime ne prévient personne
    Soit on a réservé une visio par mon lien public le 24/11/2026 à 9h00
    Et nous sommes le 24/11/2026 à 9h30
    Et j'ai marqué cette visio faite
    Quand je supprime l'échange de cette réservation
    Alors l'événement n'est plus dans mon agenda Google
    Et Google n'envoie rien à "ada@acme.fr"

  Scénario: Un échange que je planifie moi-même n'invite toujours personne
    Soit le contact "Ada Lovelace" de "ACME"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Quand je planifie une visio avec "Ada Lovelace" le 24/11/2026 à 14h00
    Alors l'événement n'invite personne
    Et Google n'envoie rien à "ada@acme.fr"

  Scénario: Un demandeur effacé n'est plus invité
    Soit on a réservé une visio par mon lien public le 24/11/2026 à 9h00
    Quand j'efface le contact "Ada Lovelace"
    Alors l'événement n'est plus dans mon agenda Google
    Et Google envoie l'annulation à "ada@acme.fr"
