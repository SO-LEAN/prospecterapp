# language: fr
Fonctionnalité: Libérer le rendez-vous d'un échange effacé
  En tant que prospecteur
  Je veux qu'une réservation par mon lien public s'efface avec l'échange qu'elle est devenue
  Afin que son créneau soit de nouveau libre, et que rien ne reste de qui l'a réservé

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour  | de    | à     |
      | mardi | 09:00 | 09:30 |
    Et ma prise de rendez-vous est active

  Scénario: Le créneau d'un échange réservé que je supprime est de nouveau libre
    Soit quelqu'un vient de réserver par mon lien public le 24/11/2026 à 9h00
    Quand je supprime l'échange de cette réservation
    Et on ouvre mon lien public
    Alors le créneau du 24/11/2026 à 9h00 est libre

  Scénario: Qui a réservé un échange que je supprime peut réserver de nouveau
    Soit on a réservé par mon lien public avec "ada@acme.fr" le 24/11/2026 à 9h00
    Et je supprime l'échange de cette réservation
    Quand on réserve par mon lien public avec "ada@acme.fr" le 24/11/2026 à 9h15
    Alors ce n'est pas refusé

  Scénario: Arrêter de prospecter l'organisation de qui a réservé libère son créneau
    Soit on a réservé par mon lien public avec "ada@acme.fr" le 24/11/2026 à 9h00
    Et je rattache à "ACME" qui a réservé
    Quand j'arrête de prospecter "ACME"
    Et on ouvre mon lien public
    Alors le créneau du 24/11/2026 à 9h00 est libre
