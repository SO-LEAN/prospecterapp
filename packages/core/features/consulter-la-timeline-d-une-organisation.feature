# language: fr
Fonctionnalité: La timeline d'une organisation
  En tant que prospecteur
  Je veux voir dans une seule timeline les échanges avec une organisation et avec chacun de ses contacts
  Afin de savoir où j'en suis avec elle, et qui a communiqué

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et le contact "Robert Noyce" de "ACME"
    Et nous sommes le 18/10/2026 à 19h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: La timeline réunit les échanges de tous les contacts et de l'organisation, avec qui a communiqué
    Soit j'ai noté un e-mail reçu de "Ada Lovelace" le 10/10/2026 à 9h00
    Et j'ai noté un appel envoyé à "Robert Noyce" le 12/10/2026 à 11h00
    Quand je note un appel reçu de "ACME" elle-même le 15/10/2026 à 16h00
    Alors la timeline de "ACME" est, du plus récent au plus ancien :
      | moment           | interlocuteur | canal  | sens   |
      | 15/10/2026 16:00 | ACME          | Appel  | reçu   |
      | 12/10/2026 11:00 | Robert Noyce  | Appel  | envoyé |
      | 10/10/2026 09:00 | Ada Lovelace  | E-mail | reçu   |

  Scénario: Un échange avec l'organisation elle-même n'apparaît dans la timeline d'aucun contact
    Quand je note un appel reçu de "ACME" elle-même
    Alors la timeline de "Ada Lovelace" est vide

  Scénario: Un contact de deux organisations apparaît dans les deux timelines
    Soit l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Quand je note un SMS reçu de "Ada Lovelace"
    Alors la timeline de "ACME" montre ce SMS
    Et la timeline de "Groupe ACME" montre ce SMS

  Scénario: Les échanges d'avant l'arrivée d'un contact ne le suivent pas
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et j'ai noté un SMS reçu de "Ada Lovelace"
    Quand je rattache le contact "Ada Lovelace" à "Globex"
    Alors la timeline de "Globex" est vide

  Scénario: Je note un échange avec un contact depuis la fiche de l'organisation
    Quand je note depuis "ACME" un e-mail envoyé à "Robert Noyce"
    Alors la timeline de "Robert Noyce" montre cet e-mail
    Et la timeline de "ACME" le montre avec l'interlocuteur "Robert Noyce"

  Scénario: L'interlocuteur choisi depuis une organisation en fait partie
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Grace Hopper" de "Globex"
    Quand je note depuis "ACME" un e-mail envoyé à "Grace Hopper"
    Alors c'est refusé : "This person is no contact of the organization"

  Scénario: La timeline se lit par tranches
    Soit j'ai noté 30 échanges avec "ACME" elle-même
    Quand je consulte la timeline de "ACME"
    Alors je vois les 20 plus récents
    Et je peux lire la suite après le dernier
    Et je peux sur la timeline :
      | consulter la timeline |
      | noter un échange      |

  Scénario: La fiche d'une organisation mène à sa timeline
    Quand je consulte l'organisation "ACME"
    Alors je peux sur sa timeline :
      | consulter la timeline |
      | noter un échange      |

  Scénario: Sans le droit aux données personnelles, je ne note pas d'échange avec l'organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je note un appel reçu de "ACME" elle-même
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: Sans le droit aux données personnelles, je ne lis pas la timeline de l'organisation
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la timeline de "ACME"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: Sans le droit aux données personnelles, la fiche d'une organisation ne mène pas à sa timeline
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte l'organisation "ACME"
    Alors je ne peux rien sur sa timeline

  Scénario: L'organisation d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je consulte la timeline de "Initech"
    Alors c'est introuvable

  Scénario: Je ne note pas d'échange avec l'organisation d'un autre compte
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je note un appel reçu de "Initech" elle-même
    Alors c'est introuvable
