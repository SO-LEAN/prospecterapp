# language: fr
Fonctionnalité: Réserver un créneau
  En tant que personne à qui un prospecteur a donné son lien public
  Je veux réserver un de ses créneaux libres en disant qui je suis, sans me connecter
  Afin qu'il devienne un échange planifié de son agenda

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour  | de    | à     |
      | lundi | 14:00 | 15:00 |
      | mardi | 09:00 | 09:30 |
    Et ma prise de rendez-vous est active

  Scénario: Une réservation devient un échange planifié de son prospecteur, avec un contact à rattacher
    Quand on réserve une visio par mon lien public le 24/11/2026 à 9h00 :
      | prénom    | Ada                  |
      | nom       | Lovelace             |
      | téléphone | 06 12 34 56 78       |
      | e-mail    | ada@acme.fr          |
      | objet     | Mission data en mars |
    Alors j'ai une visio planifiée le 24/11/2026 de 9h00 à 9h15 avec "Ada Lovelace", au texte "Mission data en mars"
    Et "Ada Lovelace" est un contact à rattacher, au 06 12 34 56 78 et à ada@acme.fr

  Scénario: L'échange d'une réservation dit qu'on l'a réservé par le lien public
    Soit le contact "Grace Hopper" de "ACME"
    Et j'ai planifié un appel avec "Grace Hopper" le 24/11/2026 à 9h15
    Quand on réserve une visio par mon lien public le 24/11/2026 à 9h00
    Et je consulte mon calendrier du 24/11/2026 au 24/11/2026
    Alors il montre la visio du 24/11/2026 à 9h00, réservée par mon lien public
    Et il montre l'appel du 24/11/2026 à 9h15, que j'ai planifié

  Scénario: Le détail de l'échange d'une réservation dit qu'on l'a réservé par le lien public
    Quand on réserve une visio par mon lien public le 24/11/2026 à 9h00
    Et je consulte l'échange de cette réservation
    Alors son détail dit qu'on l'a réservé par mon lien public

  Scénario: Un contact connu par son e-mail est reconnu
    Soit le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00 avec l'e-mail "ADA@acme.fr"
    Alors j'ai un appel planifié avec "Ada Lovelace" de "ACME"
    Et aucun contact n'est créé

  Scénario: Un contact connu par son téléphone est reconnu
    Soit le contact "Ada Lovelace" de "ACME" a le téléphone "+33612345678"
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00 avec le téléphone "06 12 34 56 78" et l'e-mail "ada.perso@gmail.com"
    Alors j'ai un appel planifié avec "Ada Lovelace" de "ACME"
    Et aucun contact n'est créé

  Scénario: Tous les champs sont requis, sauf l'objet
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00 sans téléphone
    Alors c'est refusé : "A booker gives their first name, last name, phone and e-mail"

  Scénario: Un créneau pris entre-temps est refusé
    Soit quelqu'un vient de réserver par mon lien public le 24/11/2026 à 9h00
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00
    Alors c'est refusé : "This slot was just taken"

  Scénario: Un créneau que le lien ne propose pas est refusé
    Quand on réserve un appel par mon lien public le 24/11/2026 à 10h00
    Alors c'est refusé : "This slot was just taken"

  Scénario: Un créneau réservé n'est plus libre
    Soit quelqu'un vient de réserver par mon lien public le 24/11/2026 à 9h00
    Quand on ouvre mon lien public
    Alors le créneau du 24/11/2026 à 9h00 n'est pas libre

  Scénario: Une seule réservation à venir par e-mail
    Soit on a réservé par mon lien public avec "ada@acme.fr" le 24/11/2026 à 9h00
    Quand on réserve par mon lien public avec "ADA@acme.fr" le 30/11/2026 à 14h00
    Alors c'est refusé : "You already have an appointment"
    Et on est prévenu du rendez-vous du 24/11/2026 à 9h00

  Scénario: Un rendez-vous passé n'empêche pas d'en réserver un autre
    Soit on a réservé par mon lien public avec "ada@acme.fr" le 24/11/2026 à 9h00
    Et nous sommes le 24/11/2026 à 10h00
    Quand on réserve par mon lien public avec "ada@acme.fr" le 30/11/2026 à 14h00
    Alors ce n'est pas refusé

  Scénario: Un canal non proposé est refusé
    Soit ma prise de rendez-vous est active, en proposant la visio
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00
    Alors c'est refusé : "This channel is not offered"

  Scénario: Un lien coupé ne réserve rien
    Soit je l'ai coupée
    Quand on réserve un appel par mon lien public le 24/11/2026 à 9h00
    Alors c'est refusé : "This booking link offers no slot"
