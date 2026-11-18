# language: fr
Fonctionnalité: Configurer le client Google de mon compte
  En tant qu'administrateur d'un compte
  Je veux configurer le client OAuth Google de mon compte, son id et son secret
  Afin que les membres de mon compte connectent leur agenda Google par le client de leur propre Workspace

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est administrateur de "SOLEAN IT"
    Et je suis connecté en tant que "Michel"

  Scénario: Un compte sans client n'en a pas
    Quand je consulte le client Google de mon compte
    Alors mon compte n'a pas de client Google

  Scénario: Je configure le client de mon compte
    Quand je configure le client Google "client-solean" de secret "secret-solean"
    Alors le client Google de mon compte est "client-solean", son secret renseigné
    Et Google est proposé à un prospecteur de "SOLEAN IT"

  Scénario: Les membres connectent leur compte par ce client
    Soit je configure le client Google "client-solean" de secret "secret-solean"
    Quand un prospecteur de "SOLEAN IT" connecte son compte Google
    Alors Google a accordé l'accès au client "client-solean" de secret "secret-solean"
    Et la connexion Google du prospecteur de "SOLEAN IT" est "connectée"

  Scénario: Un client a son id
    Quand je configure le client Google "" de secret "secret-solean"
    Alors c'est refusé : "A client needs its id"
    Et mon compte n'a pas de client Google

  Scénario: Un client a son secret
    Quand je configure le client Google "client-solean" de secret ""
    Alors c'est refusé : "A client needs its secret"
    Et mon compte n'a pas de client Google

  Scénario: Un membre qui n'est pas administrateur ne configure pas le client
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je configure le client Google "client-solean" de secret "secret-solean"
    Alors c'est refusé : "Only an administrator of the account configures its clients"
    Et Google n'est pas proposé à un prospecteur de "SOLEAN IT"

  Scénario: Un membre qui n'est pas administrateur ne voit pas le client
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je consulte le client Google de mon compte
    Alors c'est refusé : "Only an administrator of the account configures its clients"

  Scénario: Un membre qui n'est pas administrateur ne retire pas le client
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je retire le client Google de mon compte
    Alors c'est refusé : "Only an administrator of the account configures its clients"
    Et Google est proposé à un prospecteur de "SOLEAN IT"

  Scénario: Le client d'un compte ne sert qu'à lui
    Soit l'organisation "Babbage" qui utilise Prospecter
    Et le client Google "client-babbage" de secret "secret-babbage" sert "Babbage"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Quand un prospecteur de "SOLEAN IT" connecte son compte Google
    Alors Google a accordé l'accès au client "client-solean" de secret "secret-solean"

  Scénario: Un compte sans client ne se sert pas de celui d'un autre
    Soit l'organisation "Babbage" qui utilise Prospecter
    Et le client Google "client-babbage" de secret "secret-babbage" sert "Babbage"
    Alors Google n'est pas proposé à un prospecteur de "SOLEAN IT"

  Scénario: Les nouvelles connexions passent par le nouveau client
    Soit le client Google "client-ancien" de secret "secret-ancien" sert "SOLEAN IT"
    Quand je configure le client Google "client-nouveau" de secret "secret-nouveau"
    Et un prospecteur de "SOLEAN IT" connecte son compte Google
    Alors le client Google de mon compte est "client-nouveau", son secret renseigné
    Et Google a accordé l'accès au client "client-nouveau" de secret "secret-nouveau"

  Scénario: Les connexions faites par l'ancien client sont à reconnecter
    Soit le client Google "client-ancien" de secret "secret-ancien" sert "SOLEAN IT"
    Et un prospecteur de "SOLEAN IT" a connecté son compte Google
    Quand je configure le client Google "client-nouveau" de secret "secret-nouveau"
    Et les connexions du prospecteur de "SOLEAN IT" sont vérifiées
    Alors la connexion Google du prospecteur de "SOLEAN IT" est "à reconnecter"

  Scénario: Un nouveau secret du même client garde les connexions
    Soit le client Google "client-solean" de secret "secret-ancien" sert "SOLEAN IT"
    Et un prospecteur de "SOLEAN IT" a connecté son compte Google
    Quand je configure le client Google "client-solean" de secret "secret-nouveau"
    Et les connexions du prospecteur de "SOLEAN IT" sont vérifiées
    Alors la connexion Google du prospecteur de "SOLEAN IT" est "connectée"
    Et Google a vérifié l'accès avec le client "client-solean" de secret "secret-nouveau"

  Scénario: Je retire le client
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Quand je retire le client Google de mon compte
    Alors mon compte n'a pas de client Google
    Et Google n'est pas proposé à un prospecteur de "SOLEAN IT"

  Scénario: Je ne retire pas un client absent
    Quand je retire le client Google de mon compte
    Alors c'est refusé : "No client at Google"

  Scénario: Je renseigne le topic par lequel Gmail prévient Prospecter
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Quand je renseigne le topic Pub/Sub "projects/solean/topics/gmail" poussé par "push@solean.iam.gserviceaccount.com"
    Alors le client Google de mon compte a le topic Pub/Sub "projects/solean/topics/gmail" poussé par "push@solean.iam.gserviceaccount.com"

  Scénario: Les notifications de Gmail d'un compte sont poussées par le compte de service de son topic
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je renseigne le topic Pub/Sub "projects/solean/topics/gmail" poussé par "push@solean.iam.gserviceaccount.com"
    Alors les notifications de Gmail de "SOLEAN IT" sont poussées par "push@solean.iam.gserviceaccount.com"

  Scénario: Sans topic, personne ne pousse les notifications de Gmail d'un compte
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Alors les notifications de Gmail de "SOLEAN IT" ne sont poussées par personne

  Scénario: Sans client, personne ne pousse les notifications de Gmail d'un compte
    Alors les notifications de Gmail de "SOLEAN IT" ne sont poussées par personne

  Scénario: Un topic a son nom complet
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Quand je renseigne le topic Pub/Sub "gmail" poussé par "push@solean.iam.gserviceaccount.com"
    Alors c'est refusé : "A topic is named projects/<project>/topics/<topic>"
    Et le client Google de mon compte n'a pas de topic Pub/Sub

  Scénario: Seul un administrateur renseigne le topic
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je renseigne le topic Pub/Sub "projects/solean/topics/gmail" poussé par "push@solean.iam.gserviceaccount.com"
    Alors c'est refusé : "Only an administrator of the account configures its clients"

  Scénario: Je retire le topic Pub/Sub
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je renseigne le topic Pub/Sub "projects/solean/topics/gmail" poussé par "push@solean.iam.gserviceaccount.com"
    Quand je retire le topic Pub/Sub de mon compte
    Alors le client Google de mon compte n'a pas de topic Pub/Sub
