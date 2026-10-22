# language: fr
Fonctionnalité: Corriger un échange
  En tant que prospecteur
  Je veux corriger un échange mal saisi, ou rendre son texte conforme au RGPD
  Afin que la timeline dise ce qui s'est vraiment passé, en gardant qui l'a corrigé et quand

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un message LinkedIn reçu de "Ada Lovelace" à 14h30 : "Intéressée par une mission en mars"

  Scénario: Une correction est enregistrée avec qui l'a faite et quand
    Soit je suis connecté en tant que "Michel"
    Et nous sommes le 16/10/2026 à 10h00
    Quand je corrige cet échange :
      | canal | E-mail                                              |
      | texte | Objet : mission. Intéressée par une mission en mars |
    Alors je vois l'échange :
      | interlocuteur | Ada Lovelace                                        |
      | canal         | E-mail                                              |
      | sens          | reçu                                                |
      | moment        | 15/10/2026 14:30                                    |
      | texte         | Objet : mission. Intéressée par une mission en mars |
      | noté          | le 15/10/2026 à 14:35 par Michel                    |
      | modifié       | le 16/10/2026 à 10:00 par Michel                    |
    Et la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens | texte                                               |
      | 15/10/2026 14:30 | E-mail | reçu | Objet : mission. Intéressée par une mission en mars |

  Scénario: Une correction respecte les règles d'un échange
    Soit je suis connecté en tant que "Michel"
    Et nous sommes le 16/10/2026 à 10h00
    Quand je corrige cet échange :
      | moment | 17/10/2026 10:00 |
    Alors c'est refusé : "An exchange is never in the future"

  Scénario: Seul un appel corrigé peut ne pas avoir abouti
    Soit je suis connecté en tant que "Michel"
    Quand je corrige cet échange :
      | canal      | SMS |
      | non abouti | oui |
    Alors c'est refusé : "Only a call may be missed"

  Scénario: Sans le droit aux données personnelles, je ne corrige que le texte
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je corrige cet échange :
      | canal | SMS |
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: L'échange d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "Concurrent"
    Quand je corrige cet échange :
      | texte | Rien |
    Alors c'est introuvable
