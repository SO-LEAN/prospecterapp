# language: fr
Fonctionnalité: Pousser mes échanges planifiés dans Google Agenda
  En tant que prospecteur dont le compte Google est connecté
  Je veux que chaque échange planifié soit poussé dans mon agenda Google, et que chaque changement fait dans Prospecter y soit reporté
  Afin de retrouver mes rendez-vous sur mon téléphone, avec les rappels de Google quand Prospecter est fermé

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et mes rappels sont réglés à 10 minutes avant

  Scénario: Un échange planifié est ajouté à mon agenda Google
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00 au lien "https://meet.google.com/abc-defg-hij"
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 17/11/2026 de 14h00 à 14h30
    Et l'événement a lieu à "https://meet.google.com/abc-defg-hij"
    Et l'événement mène à la fiche de "Ada Lovelace"
    Et l'événement me rappelle 10 minutes avant

  Scénario: Un échange planifié avec l'organisation elle-même mène à sa fiche
    Quand je planifie un rendez-vous avec "ACME" le 17/11/2026 à 9h00 pour 1 heure au lieu "Café de Flore"
    Alors mon agenda Google a l'événement « Rendez-vous · ACME » le 17/11/2026 de 9h00 à 10h00
    Et l'événement a lieu à "Café de Flore"
    Et l'événement mène à la fiche de "ACME"

  Scénario: Sans rappels, l'événement ne me rappelle pas
    Soit je désactive mes rappels
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors l'événement ne me rappelle pas

  Scénario: Un échange déplacé est déplacé dans Google
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je la déplace au 18/11/2026 à 9h00
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 18/11/2026 de 9h00 à 9h30

  Scénario: Un échange annulé est retiré de Google
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand j'annule cette visio
    Alors l'événement n'est plus dans mon agenda Google

  Scénario: Un échange fait reste dans Google
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et nous sommes le 17/11/2026 à 15h00
    Quand je marque cette visio faite
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 17/11/2026 de 14h00 à 14h30

  Scénario: Un échange effacé est retiré de Google
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand j'arrête de prospecter "ACME"
    Alors l'événement n'est plus dans mon agenda Google

  Scénario: Le nom d'un contact effacé quitte Google
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand j'efface la fiche de "Ada Lovelace"
    Alors l'événement n'est plus dans mon agenda Google

  Scénario: Le nom d'un contact effacé quitte l'événement d'un échange fait, qui mène à son organisation
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et nous sommes le 17/11/2026 à 15h00
    Et j'ai marqué cette visio faite
    Quand j'efface la fiche de "Ada Lovelace"
    Alors mon agenda Google a l'événement « Visio · ACME » le 17/11/2026 de 14h00 à 14h30
    Et l'événement mène à la fiche de "ACME"

  Scénario: Sans le droit aux données personnelles, mon agenda Google nomme le contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Et mon compte Google est connecté
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 17/11/2026 de 14h00 à 14h30

  Scénario: L'échange planifié par un autre membre n'est pas poussé dans mon agenda
    Quand un autre prospecteur de "SOLEAN IT" a planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors rien n'est poussé dans Google

  Scénario: Sans connexion Google, rien n'est poussé
    Soit je déconnecte mon compte Google
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors rien n'est poussé dans Google

  Scénario: Sans client Google de mon compte, rien n'est poussé
    Soit aucun client Google ne sert mon compte
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors rien n'est poussé dans Google

  Scénario: Un échange noté une fois fait n'est pas poussé
    Quand j'ai noté un appel envoyé à "Ada Lovelace" le 12/11/2026 à 9h00
    Alors rien n'est poussé dans Google

  Scénario: Le même changement reçu deux fois ne crée pas de doublon
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et la planification de la visio est poussée de nouveau
    Alors mon agenda Google a un seul événement, « Visio · Ada Lovelace (ACME) »

  Scénario: Un jeton que Google refuse met ma connexion à reconnecter, sans rien pousser
    Soit Google refuse désormais mon jeton
    Quand je planifie une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Alors ma connexion Google est "à reconnecter"
    Et rien n'est poussé dans Google

  Scénario: Un échange planifié pendant que ma connexion est à reconnecter n'est retiré de rien une fois effacé
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et Google refuse désormais mon jeton
    Et j'ai planifié un appel avec "Ada Lovelace" le 18/11/2026 à 9h00
    Quand j'arrête de prospecter "ACME"
    Alors rien n'est retiré de Google pour cet appel

  Scénario: Un échange planifié pendant que ma connexion était à reconnecter est poussé à la reconnexion, et retiré une fois effacé
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et Google refuse désormais mon jeton
    Et j'ai planifié un appel avec "Ada Lovelace" le 18/11/2026 à 9h00
    Et mon compte Google est connecté
    Quand j'arrête de prospecter "ACME"
    Alors l'événement n'est plus dans mon agenda Google

  Scénario: Mon agenda « Prospecter » supprimé dans Google est recréé au changement suivant
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai supprimé l'agenda « Prospecter » de Google
    Quand je la déplace au 18/11/2026 à 9h00
    Alors mon agenda Google a l'événement « Visio · Ada Lovelace (ACME) » le 18/11/2026 de 9h00 à 9h30
