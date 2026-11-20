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
