# language: fr
Fonctionnalité: Consulter mes échanges à traiter
  En tant que prospecteur
  Je veux voir les échanges planifiés dont l'heure est passée et que je n'ai marqués ni faits, ni manqués, ni annulés
  Afin d'avoir une vue claire sur le travail à faire et de ne rien laisser traîner

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Un échange planifié dont la fin est passée est à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 10/11/2026 à 14h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je consulte mes échanges à traiter
    Alors ils sont, du plus ancien au plus récent :
      | moment           | canal | avec         |
      | 10/11/2026 14:00 | Visio | Ada Lovelace |

  Scénario: Un échange traité n'est plus à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 10/11/2026 à 14h00
    Et nous sommes le 13/11/2026 à 10h00
    Quand je la marque faite
    Alors je n'ai plus d'échange à traiter

  Scénario: Un échange en cours n'est pas encore à traiter
    Soit j'ai planifié une visio avec "Ada Lovelace" le 13/11/2026 à 9h45
    Et nous sommes le 13/11/2026 à 10h00
    Alors je n'ai pas d'échange à traiter
