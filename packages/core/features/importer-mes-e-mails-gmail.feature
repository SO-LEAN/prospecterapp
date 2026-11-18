# language: fr
Fonctionnalité: Activer l'import de mes e-mails Gmail
  En tant que membre connecté à Google
  Je veux activer ou couper l'import de mes e-mails Gmail, sans toucher à mon agenda, et voir s'il fonctionne
  Afin que les e-mails que j'échange avec mes contacts rejoignent Prospecter

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté

  Scénario: Sans topic du compte, l'import n'est pas proposé
    Quand je consulte mes connecteurs
    Alors l'import de mes e-mails Gmail ne m'est pas proposé

  Scénario: Avec le topic du compte, l'import est proposé
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Quand je consulte mes connecteurs
    Alors l'import de mes e-mails Gmail m'est proposé

  Scénario: Activer l'import demande l'accès à Gmail en plus de l'agenda
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et la boîte Gmail "michel@exemple.fr" en est à l'historique "1000"
    Quand j'active l'import de mes e-mails Gmail et que Google accorde l'accès à Gmail
    Alors l'import de mes e-mails Gmail est "actif"
    Et Gmail prévient Prospecter des nouveaux e-mails de "michel@exemple.fr" par le topic "projects/solean/topics/gmail"
    Et l'import part de l'historique "1000"
    Et ma connexion Google est "connectée"

  Scénario: Un accès à Gmail refusé n'active rien
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Quand j'active l'import de mes e-mails Gmail mais que je refuse l'accès à Gmail chez Google
    Alors l'import de mes e-mails Gmail est "coupé"
    Et Gmail ne prévient pas Prospecter des nouveaux e-mails de "michel@exemple.fr"
    Et ma connexion Google est "connectée"

  Scénario: Couper l'import
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et j'ai activé l'import de mes e-mails Gmail
    Quand je coupe l'import de mes e-mails Gmail
    Alors l'import de mes e-mails Gmail est "coupé"
    Et Gmail ne prévient plus Prospecter des nouveaux e-mails de "michel@exemple.fr"
    Et ma connexion Google est "connectée"

  Scénario: La surveillance de Gmail est renouvelée chaque jour
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et j'ai activé l'import de mes e-mails Gmail
    Quand le renouvellement quotidien passe
    Alors Gmail a renouvelé la surveillance de "michel@exemple.fr" par le topic "projects/solean/topics/gmail"
    Et l'import de mes e-mails Gmail est "actif"

  Scénario: Un accès retiré chez Google rend l'import à reconnecter au renouvellement
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et j'ai activé l'import de mes e-mails Gmail
    Et je retire l'accès de Prospecter dans mon compte Google
    Quand le renouvellement quotidien passe
    Alors l'import de mes e-mails Gmail est "à reconnecter"

  Scénario: Se déconnecter de Google coupe l'import
    Soit le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et j'ai activé l'import de mes e-mails Gmail
    Quand je déconnecte mon compte Google
    Alors Gmail ne prévient plus Prospecter des nouveaux e-mails de "michel@exemple.fr"

  Scénario: Sans topic du compte, l'import est refusé
    Quand j'active l'import de mes e-mails Gmail et que Google accorde l'accès à Gmail
    Alors c'est refusé : "The import of the e-mails is not available"
    Et l'import de mes e-mails Gmail est "coupé"
