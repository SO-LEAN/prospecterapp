# language: fr
Fonctionnalité: Consulter le détail d'un échange
  En tant que prospecteur
  Je veux voir tout un échange, qui l'a noté et qui l'a corrigé
  Afin de relire ce qui a été dit et de savoir d'où vient ce que j'en lis

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un message LinkedIn reçu de "Ada Lovelace" à 14h30 : "Intéressée par une mission en mars"

  Scénario: Le détail montre tout l'échange et qui l'a noté
    Soit je suis connecté en tant que "Michel"
    Quand je consulte cet échange
    Alors je vois l'échange :
      | interlocuteur | Ada Lovelace                       |
      | canal         | LinkedIn                           |
      | sens          | reçu                               |
      | moment        | 15/10/2026 14:30                   |
      | texte         | Intéressée par une mission en mars |
      | noté          | le 15/10/2026 à 14:35 par Michel   |
      | modifié       |                                    |
      | signalé       | non                                |
    Et je peux sur l'échange :
      | corriger l'échange       |
      | corriger le texte        |
      | supprimer l'échange      |
      | joindre un fichier       |
      | retirer une pièce jointe |

  Scénario: Un échange avec l'organisation elle-même a l'organisation pour interlocuteur
    Soit "Michel" a noté le 16/10/2026 à 09h05 un appel reçu de "ACME" elle-même à 09h00 : "Le standard rappellera"
    Et je suis connecté en tant que "Michel"
    Quand je consulte cet échange
    Alors je vois l'échange :
      | interlocuteur | ACME                             |
      | canal         | Appel                            |
      | sens          | reçu                             |
      | moment        | 16/10/2026 09:00                 |
      | texte         | Le standard rappellera           |
      | noté          | le 16/10/2026 à 09:05 par Michel |
      | modifié       |                                  |

  Scénario: Sans le droit aux données personnelles, je lis l'échange, l'interlocuteur masqué, le texte signalé
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte cet échange
    Alors je vois l'échange :
      | interlocuteur | A***a L***e                        |
      | texte         | Intéressée par une mission en mars |
      | signalé       | oui                                |
    Et je peux sur l'échange :
      | corriger le texte |

  Scénario: L'échange d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "Concurrent"
    Quand je consulte cet échange
    Alors c'est introuvable
