# language: fr
Fonctionnalité: Mener une campagne avec une IA
  En tant que prospecteur
  Je veux qu'une IA mène une campagne démarrée : elle prend chaque destinataire à traiter, lui envoie elle-même le message, puis note l'échange envoyé
  Afin de mener une campagne sans écrire chaque message, Prospecter n'envoyant toujours rien

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 16/11/2026 à 17h00

  Scénario: L'IA prend le destinataire suivant
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", l'objet "Mission PHP" et le message "Bonjour {prénom}, je suis disponible en janvier pour une mission PHP."
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Ada Lovelace", l'objet "Mission PHP" et le message "Bonjour Ada, je suis disponible en janvier pour une mission PHP."
    Et le message est à envoyer "tel quel", 2 destinataires restant à traiter

  Scénario: L'IA reçoit le destinataire suivant avec le message à adapter
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "adapté"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors le message est à envoyer "adapté", 2 destinataires restant à traiter

  Scénario: Une campagne arrêtée ne donne plus de destinataire suivant
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et la campagne "Relance PHP" est arrêtée
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors c'est refusé : "Only a started campaign is run"

  Scénario: Mené à la main, un destinataire n'est plus le destinataire suivant de l'IA
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", 1 destinataire restant à traiter
