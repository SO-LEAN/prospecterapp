# language: fr
Fonctionnalité: Les pièces jointes des e-mails importés
  En tant que prospecteur qui a activé l'import de ses e-mails Gmail
  Je veux retrouver jointes à l'échange importé les pièces jointes de l'e-mail
  Afin d'avoir le devis, le CV ou la fiche de poste sous la main, comme si je les avais joints moi-même

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et j'ai activé l'import de mes e-mails Gmail
    Et nous sommes le "2026-11-18 10:00"

  Scénario: Les pièces jointes sont jointes à l'échange
    Quand je reçois de "ada@acme.fr" un e-mail avec "devis.pdf" (300 Ko) en pièce jointe
    Alors l'échange importé a la pièce jointe "devis.pdf"

  # Les images intégrées au corps (le logo d'une signature) ne sont pas des pièces jointes : Gmail les décrit comme des
  # parties du message, que l'API écarte en le lisant (GmailMessageTest).

  Scénario: Une pièce jointe trop lourde est écartée et signalée
    Quand je reçois de "ada@acme.fr" un e-mail avec "video.mp4" (25 Mo)
    Alors l'échange importé n'a pas de pièce jointe
    Et son texte se termine par « Pièce jointe non importée : video.mp4 (25 Mo), à ouvrir dans Gmail »

  Scénario: Au-delà de 10 pièces jointes, les suivantes sont écartées et signalées
    Quand je reçois de "ada@acme.fr" un e-mail avec 12 pièces jointes
    Alors l'échange importé a les 10 premières
    Et son texte signale les 2 autres

  Scénario: Une petite pièce jointe écartée est dite de moins de 1 Ko
    Quand je reçois de "ada@acme.fr" un e-mail avec 12 pièces jointes, la onzième "note.txt" de 17 octets
    Alors son texte signale « Pièce jointe non importée : note.txt (moins de 1 Ko), à ouvrir dans Gmail »

  Scénario: Les pièces jointes d'un e-mail envoyé sont jointes aussi
    Quand j'envoie à "ada@acme.fr" un e-mail avec "cv.pdf" (120 Ko)
    Alors l'échange importé a la pièce jointe "cv.pdf"

  Scénario: Chaque destinataire connu a son échange et ses propres fichiers
    Soit le contact "Alan Turing" de "ACME" a l'adresse e-mail "alan@acme.fr"
    Quand j'envoie à "ada@acme.fr" et "alan@acme.fr" un e-mail avec "cv.pdf" (120 Ko)
    Alors l'échange avec "Ada Lovelace" et celui avec "Alan Turing" ont chacun leur fichier "cv.pdf"

  Scénario: Une pièce jointe importée délivrée une fois de plus garde son fichier
    Soit j'ai reçu de "ada@acme.fr" un e-mail avec "devis.pdf" (300 Ko) en pièce jointe
    Quand la pièce jointe importée est délivrée une fois de plus
    Alors l'échange importé a la pièce jointe "devis.pdf"
    Et aucune pièce jointe importée n'a échoué

  # L'import s'en tient aux limites d'un échange ; Prospecting, qui les fixe, signale ce qu'il ne peut pas tenir
  # plutôt que de refuser l'e-mail.
  Scénario: Au-delà de la place de l'échange, les fichiers envoyés au stockage sont signalés
    Quand l'import me dit échangé avec "Ada Lovelace" un e-mail dont 11 fichiers sont envoyés au stockage
    Alors l'échange importé a les 10 premières
    Et son texte signale les 1 autres

  Scénario: Un fichier envoyé au stockage plus lourd que ce qu'un échange tient est signalé
    Quand l'import me dit échangé avec "Ada Lovelace" un e-mail dont "film.mp4" de 11 Mo est envoyé au stockage
    Alors l'échange importé n'a pas de pièce jointe
    Et son texte se termine par « Pièce jointe non importée : film.mp4 (11 Mo), à ouvrir dans Gmail »

  Scénario: Un fichier que le stockage n'a plus quand l'échange est noté est signalé
    Soit le stockage privé oublie les envois avant qu'ils soient joints
    Quand je reçois de "ada@acme.fr" un e-mail avec "devis.pdf" (300 Ko) en pièce jointe
    Alors l'échange importé n'a pas de pièce jointe
    Et son texte se termine par « Pièce jointe non importée : devis.pdf (300 Ko), à ouvrir dans Gmail »
