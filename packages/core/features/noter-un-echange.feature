# language: fr
Fonctionnalité: Noter un échange avec un contact
  En tant que prospecteur
  Je veux noter chaque appel, SMS, e-mail, message LinkedIn ou rendez-vous avec un contact
  Afin de retrouver dans sa timeline tout ce que nous nous sommes dit

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 18/10/2026 à 19h00

  Scénario: L'échange noté apparaît en tête de la timeline du contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté un appel envoyé à "Ada Lovelace" le 10/10/2026 à 9h00
    Quand je note un échange avec "Ada Lovelace" :
      | canal  | LinkedIn                           |
      | sens   | reçu                               |
      | moment | 15/10/2026 14:30                   |
      | texte  | Intéressée par une mission en mars |
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal    | sens   | texte                              |
      | 15/10/2026 14:30 | LinkedIn | reçu   | Intéressée par une mission en mars |
      | 10/10/2026 09:00 | Appel    | envoyé |                                    |

  Scénario: Un appel peut ne pas avoir abouti
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je note un appel reçu de "Ada Lovelace" qui n'a pas abouti
    Alors la timeline de "Ada Lovelace" montre un appel reçu manqué

  Scénario: Seul un appel aboutit ou non
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je note un SMS envoyé à "Ada Lovelace" qui n'a pas abouti
    Alors c'est refusé : "Only a call may be missed"

  Scénario: Un échange n'est jamais dans le futur
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 18/10/2026 à 19h00
    Quand je note un e-mail reçu de "Ada Lovelace" le 19/10/2026 à 9h00
    Alors c'est refusé : "An exchange is never in the future"

  Scénario: L'échange est gardé pour les organisations dont le contact fait partie quand je le note
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Quand je note un SMS reçu de "Ada Lovelace"
    Alors l'échange est gardé pour "ACME" et "Groupe ACME"

  Scénario: Une organisation qu'on arrête de prospecter emporte ses échanges
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Grace Hopper" de "ACME" est aussi contact de "Groupe ACME"
    Et j'ai noté un appel avec "Grace Hopper", gardé pour "ACME" et "Groupe ACME"
    Et j'ai noté un SMS avec "Ada Lovelace", gardé pour "ACME" seulement
    Quand j'arrête de prospecter "ACME"
    Alors l'échange avec "Ada Lovelace" n'existe plus
    Et l'appel avec "Grace Hopper" est toujours dans sa timeline, gardé pour "Groupe ACME"

  Scénario: La timeline se lit par tranches
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté 30 échanges avec "Ada Lovelace"
    Quand je consulte la timeline de "Ada Lovelace"
    Alors je vois les 20 plus récents
    Et je peux lire la suite après le dernier

  Scénario: Sans le droit aux données personnelles, je ne note pas d'échange
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je note un SMS reçu de "Ada Lovelace"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: Sans le droit aux données personnelles, je ne lis pas la timeline
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la timeline de "Ada Lovelace"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: Le contact d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je note un SMS reçu de "Linus Torvalds"
    Alors c'est introuvable
