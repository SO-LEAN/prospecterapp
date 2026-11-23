# language: fr
Fonctionnalité: L'état de synchronisation de mes échanges planifiés
  En tant que prospecteur dont le compte Google est connecté
  Je veux savoir si chaque échange planifié est bien dans mon agenda Google, relancer une poussée en échec, et que ma connexion pousse mes échanges à venir
  Afin de me fier à mon agenda Google sans avoir à y penser

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté

  Scénario: Un échange poussé est synchronisé
    Soit Google ne répond pas
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors elle est "en attente" de synchronisation
    Quand Google répond de nouveau et que sa poussée est tentée de nouveau
    Alors elle est "synchronisée"

  Scénario: Un échec de poussée se voit et se relance
    Soit Google refuse les événements que je pousse
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors elle est "en échec" de synchronisation
    Et Google a refusé son événement le 13/11/2026 à 10h00
    Quand Google accepte de nouveau mes événements et que je relance sa synchronisation
    Alors elle est "synchronisée"
    Et mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 17/11/2026 de 14h00 à 14h30

  Scénario: Mes connecteurs comptent mes échanges à venir synchronisés et en échec, et les relancent tous
    Soit j'ai planifié une visio avec "Ada Lovelace" le 13/11/2026 à 11h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et Google refuse les événements que je pousse
    Et j'ai planifié un appel avec "Ada Lovelace" le 18/11/2026 à 9h00
    Et nous sommes le 13/11/2026 à 15h00
    Alors mes connecteurs comptent 1 échange synchronisé et 1 en échec
    Quand Google accepte de nouveau mes événements et que je relance toutes mes synchronisations en échec
    Alors mes connecteurs comptent 2 échanges synchronisés et 0 en échec

  Scénario: Connecter mon compte pousse mes échanges à venir
    Soit je déconnecte mon compte Google
    Et j'ai planifié un appel avec "Ada Lovelace" le 13/11/2026 à 11h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 18/11/2026 à 9h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 19/11/2026 à 9h00
    Et nous sommes le 13/11/2026 à 15h00
    Quand je connecte mon compte Google
    Alors mon agenda Google a les événements du 17/11/2026 à 14h00, du 18/11/2026 à 9h00 et du 19/11/2026 à 9h00

  Scénario: Me reconnecter pendant la poussée de mes échanges à venir garde ma nouvelle connexion
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et mes échanges à venir sont en cours de poussée
    Et nous sommes le 13/11/2026 à 10h05
    Quand je déconnecte mon compte Google
    Et je reconnecte mon compte Google
    Et la poussée de mes échanges à venir se termine
    Alors ma connexion Google est connectée depuis le 13/11/2026 à 10h05
    Et elle est "synchronisée"

  Scénario: Sans changement de ma connexion pendant la poussée de mes échanges à venir, la poussée l'écrit
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et mes échanges à venir sont en cours de poussée
    Et Google refuse désormais mon jeton
    Quand la poussée de mes échanges à venir se termine
    Alors ma connexion Google est "à reconnecter"

  Scénario: Une connexion à reconnecter laisse les échanges en attente
    Soit Google refuse désormais mon jeton
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors elle est "en attente" de synchronisation
    Quand je reconnecte mon compte Google
    Alors elle est "synchronisée"

  Scénario: Un échange synchronisé déplacé pendant que ma connexion est à reconnecter est en attente
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et Google refuse désormais mon jeton
    Quand je la déplace au 18/11/2026 à 9h00
    Alors elle est "en attente" de synchronisation

  Scénario: Mon agenda « Prospecter » recréé reçoit de nouveau mes échanges à venir
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 18/11/2026 à 9h00
    Et j'ai supprimé l'agenda « Prospecter » de Google
    Quand je la déplace au 19/11/2026 à 9h00
    Alors mon agenda Google a les événements du 17/11/2026 à 14h00 et du 19/11/2026 à 9h00

  Scénario: Sans connexion Google, un échange n'a pas d'état de synchronisation
    Soit je déconnecte mon compte Google
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors elle n'a pas d'état de synchronisation

  Scénario: Un échange passé jamais poussé n'a pas d'état de synchronisation
    Soit je déconnecte mon compte Google
    Et j'ai planifié une visio avec "Ada Lovelace" le 13/11/2026 à 11h00
    Et nous sommes le 13/11/2026 à 15h00
    Quand je connecte mon compte Google
    Alors elle n'a pas d'état de synchronisation

  Scénario: L'échange d'un autre prospecteur n'a pas d'état pour moi, et je ne le relance pas
    Soit un autre prospecteur de "SOLEAN IT" a planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors elle n'a pas d'état de synchronisation
    Quand je relance sa synchronisation
    Alors c'est refusé : "Only the exchange's holder can push it"
