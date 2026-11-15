# language: fr
Fonctionnalité: Connecter mon compte Google
  En tant que membre
  Je veux connecter mon compte Google à Prospecter, voir l'état de la connexion et la couper quand je veux
  Afin que mes échanges planifiés rejoignent mon agenda Google

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Une fois l'accès accordé par Google, mon compte est connecté
    Quand je connecte mon compte Google "michel@exemple.fr" et que Google accorde l'accès
    Alors ma connexion Google est "connectée", au compte "michel@exemple.fr"

  Scénario: Un accès refusé ne connecte rien
    Quand je connecte mon compte Google et que je refuse l'accès chez Google
    Alors je n'ai pas de connexion Google

  Scénario: Un code que Google refuse ne connecte rien
    Quand je connecte mon compte Google et que Google refuse le code
    Alors c'est refusé : "Google did not grant the access"
    Et je n'ai pas de connexion Google

  Scénario: Un accès sans l'agenda ne connecte rien
    Quand je connecte mon compte Google "michel@exemple.fr" sans accorder l'accès à l'agenda
    Alors c'est refusé : "The access to the calendar was not granted"
    Et je n'ai pas de connexion Google

  Scénario: Une autorisation n'est bonne que dix minutes
    Soit j'ai demandé à connecter mon compte Google "michel@exemple.fr"
    Et 11 minutes ont passé
    Quand Google me ramène avec l'accès accordé
    Alors c'est refusé : "No authorization is waiting for this answer"
    Et je n'ai pas de connexion Google

  Scénario: Une réponse de Google ne sert qu'une fois
    Soit mon compte Google "michel@exemple.fr" est connecté
    Et je déconnecte mon compte Google
    Quand Google me ramène avec l'accès accordé
    Alors c'est refusé : "No authorization is waiting for this answer"
    Et je n'ai pas de connexion Google

  Scénario: Une reconnexion sans l'agenda garde la connexion précédente
    Soit mon compte Google "michel@exemple.fr" est connecté
    Quand je connecte mon compte Google "autre@exemple.fr" sans accorder l'accès à l'agenda
    Alors c'est refusé : "The access to the calendar was not granted"
    Et ma connexion Google est "connectée", au compte "michel@exemple.fr"

  Scénario: Une autorisation demandée par un autre membre ne me connecte pas
    Soit un autre membre de "SOLEAN IT" a demandé à connecter son compte Google "autre@exemple.fr"
    Quand j'apporte la réponse de Google à sa demande
    Alors c'est refusé : "No authorization is waiting for this answer"
    Et je n'ai pas de connexion Google

  Scénario: Un jeton refusé par Google demande de reconnecter
    Soit mon compte Google est connecté
    Quand Google refuse mon jeton
    Alors ma connexion Google est "à reconnecter"

  Scénario: Un jeton toujours accepté garde la connexion
    Soit mon compte Google est connecté
    Quand mes connexions sont vérifiées
    Alors ma connexion Google est "connectée"

  Scénario: Je reconnecte un compte à reconnecter
    Soit mon compte Google "michel@exemple.fr" est connecté
    Et Google refuse mon jeton
    Quand je connecte mon compte Google "michel@exemple.fr" et que Google accorde l'accès
    Et mes connexions sont vérifiées
    Alors ma connexion Google est "connectée", au compte "michel@exemple.fr"

  Scénario: Reconnecter le même compte garde l'accès que Google vient d'accorder
    Soit mon compte Google "michel@exemple.fr" est connecté
    Quand je connecte mon compte Google "michel@exemple.fr" et que Google accorde l'accès
    Et mes connexions sont vérifiées
    Alors ma connexion Google est "connectée", au compte "michel@exemple.fr"

  Scénario: Je déconnecte mon compte Google
    Soit mon compte Google est connecté
    Quand je le déconnecte
    Alors je n'ai pas de connexion Google
    Et l'accès est révoqué chez Google

  Scénario: Je ne déconnecte pas un compte que je n'ai pas connecté
    Quand je déconnecte mon compte Google
    Alors c'est refusé : "No connection to Google"

  Scénario: Un seul compte Google par membre
    Soit mon compte Google "michel@exemple.fr" est connecté
    Quand je connecte mon compte Google "autre@exemple.fr" et que Google accorde l'accès
    Alors ma connexion Google est au compte "autre@exemple.fr"
    Et l'accès précédent est révoqué chez Google

  Scénario: Ma connexion n'est qu'à moi
    Soit un autre membre de "SOLEAN IT" a connecté son compte Google
    Quand je consulte mes connecteurs
    Alors je n'ai pas de connexion Google

  Scénario: Google m'est proposé quand un client Google sert mon compte
    Quand je consulte mes connecteurs
    Alors Google m'est proposé

  Scénario: Sans client Google, Google ne m'est pas proposé
    Soit aucun client Google ne sert mon compte
    Quand je consulte mes connecteurs
    Alors Google ne m'est pas proposé

  Scénario: Sans client Google, une autorisation est refusée
    Soit aucun client Google ne sert mon compte
    Quand je demande à connecter mon compte Google
    Alors c'est refusé : "Google is not available"
    Et je n'ai pas de connexion Google
