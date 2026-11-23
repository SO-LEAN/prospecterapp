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
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", l'objet "Mission PHP" et le message "Bonjour {firstName}, je suis disponible en janvier pour une mission PHP."
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
    Alors c'est refusé : "Only a started campaign can be run"

  Scénario: Mené à la main, un destinataire n'est plus le destinataire suivant de l'IA
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", 1 destinataire restant à traiter

  Scénario: Noter l'envoi traite le destinataire
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Alors un e-mail émis de la campagne "Relance PHP" est noté pour "Ada Lovelace", avec le texte "Bonjour Ada"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", 1 destinataire restant à traiter

  Scénario: Juste après l'envoi noté, avant que Campaigns le traite, le destinataire suivant est le prochain
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et les réactions aux gestes n'ont pas encore suivi
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Et une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", 1 destinataire restant à traiter

  Scénario: Juste après le dernier envoi noté, avant que Campaigns le traite, la campagne n'est plus menée
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Et les réactions aux gestes n'ont pas encore suivi
    Quand une IA note l'e-mail envoyé à "Grace Hopper" pour "Relance PHP", son Message-ID "<relance-grace@solean-it.test>" et le texte "Bonjour Grace"
    Et une IA demande le destinataire suivant de "Relance PHP"
    Alors c'est refusé : "Only a started campaign can be run"

  Scénario: Juste après un destinataire écarté, le destinataire suivant est le prochain
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et les réactions aux gestes n'ont pas encore suivi
    Quand j'écarte "Ada Lovelace" de la campagne "Relance PHP", pour "Son profil LinkedIn n'existe plus"
    Et une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", 1 destinataire restant à traiter

  Scénario: Le même envoi noté deux fois ne fait qu'un échange
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et une IA a noté l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Alors c'est refusé : "Exchange already noted"
    Et un seul échange est noté pour "Ada Lovelace"

  Scénario: L'e-mail que l'import a noté avant l'IA rejoint la campagne et traite le destinataire
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et j'ai activé l'import de mes e-mails Gmail
    Et l'import de ma boîte a noté l'e-mail envoyé à "ada@acme.test" de Message-ID "<relance-ada@solean-it.test>"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Alors ce n'est pas refusé
    Et un seul échange est noté pour "Ada Lovelace"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Et l'envoi à "Ada Lovelace" se retrouve dans l'échange que l'import a noté

  Scénario: Un e-mail déjà noté dans une autre campagne reste refusé comme déjà noté
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et la campagne "Relance Java" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et une IA a noté l'e-mail envoyé à "Ada Lovelace" pour "Relance Java", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Alors c'est refusé : "Exchange already noted"
    Et un seul échange est noté pour "Ada Lovelace"

  Scénario: L'envoi noté garde le texte que l'IA a réellement envoyé
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "adapté"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada, j'ai lu votre article sur PHP 8.5"
    Alors un e-mail émis de la campagne "Relance PHP" est noté pour "Ada Lovelace", avec le texte "Bonjour Ada, j'ai lu votre article sur PHP 8.5"

  Scénario: Noter à la main l'envoi d'un destinataire n'est jamais refusé comme doublon
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et j'ai noté un autre e-mail envoyé à "Ada Lovelace", hors de la campagne
    Quand je note pour "Ada Lovelace" dans la campagne "Relance PHP" le même e-mail
    Alors ce n'est pas refusé
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"

  Scénario: L'envoi noté par l'IA porte la marque d'une IA
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Quand une IA note l'e-mail envoyé à "Ada Lovelace" pour "Relance PHP", son Message-ID "<relance-ada@solean-it.test>" et le texte "Bonjour Ada"
    Alors l'échange noté pour "Ada Lovelace" est créé par une IA
