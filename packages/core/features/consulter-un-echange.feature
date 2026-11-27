# language: fr
@ConsultExchange
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

  Scénario: Le détail nomme son contact et les membres qui l'ont noté et corrigé, comme PersonalData me les lit
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Grace                |
      | nom    | Hopper               |
      | e-mail | grace@solean-it.test |
    Quand je corrige le texte de cet échange en "Intéressée par une mission en avril"
    Alors il nomme :
      | interlocuteur | Ada Lovelace                        |
      | noté par      |                                     |
      | modifié par   | Grace Hopper <grace@solean-it.test> |

  Scénario: Le détail d'un échange avec l'organisation elle-même nomme l'organisation
    Soit "Michel" a noté le 16/10/2026 à 09h05 un appel reçu de "ACME" elle-même à 09h00 : "Le standard rappellera"
    Et je suis connecté en tant que "Michel"
    Alors il nomme :
      | interlocuteur | ACME |

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

  Scénario: Sans le droit aux données personnelles, je lis l'échange, le nom de l'interlocuteur, le texte signalé
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte cet échange
    Alors je vois l'échange :
      | interlocuteur | Ada Lovelace                       |
      | texte         | Intéressée par une mission en mars |
      | signalé       | oui                                |
