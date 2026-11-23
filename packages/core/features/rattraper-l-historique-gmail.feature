# language: fr
Fonctionnalité: Rattraper l'historique Gmail d'un contact ou d'une organisation
  En tant que prospecteur qui a activé l'import de ses e-mails Gmail
  Je veux rattraper, depuis la fiche d'un contact ou d'une organisation, les e-mails que nous avions déjà échangés
  Afin de retrouver dans la timeline ce qui s'est dit avant que je l'ajoute, ou avant l'import

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et l'organisation "Globex" d'e-mail "contact@globex.fr" appartient à "SOLEAN IT"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et ma boîte Gmail contenait avant l'import :
      | moment           | de                | à                 | copie       | dossier | objet            |
      | 2026-09-02 09:00 | ada@acme.fr       | michel@exemple.fr |             | INBOX   | Présentation     |
      | 2026-09-03 09:00 | michel@exemple.fr | ada@acme.fr       |             | SENT    | RE: Présentation |
      | 2026-09-10 09:00 | bob@acme.fr       | michel@exemple.fr | ada@acme.fr | INBOX   | Réunion          |
      | 2026-09-12 09:00 | ada@acme.fr       | michel@exemple.fr |             | SPAM    | Offre            |
    Et j'ai activé l'import de mes e-mails Gmail
    Et nous sommes le "2026-11-18 11:00"

  Scénario: Rattraper les e-mails échangés avec un contact
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens   | statut |
      | 03/09/2026 09:00 | E-mail | envoyé | Fait   |
      | 02/09/2026 09:00 | E-mail | reçu   | Fait   |
    Et 2 échanges sont importés

  Scénario: Rattraper deux fois n'importe rien de plus
    Soit j'ai rattrapé l'historique Gmail de "Ada Lovelace"
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors 2 échanges sont importés

  Scénario: Un échange supprimé n'est pas rattrapé
    Soit j'ai rattrapé l'historique Gmail de "Ada Lovelace"
    Et je supprime l'échange importé "Présentation"
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens   | statut |
      | 03/09/2026 09:00 | E-mail | envoyé | Fait   |

  Scénario: Rattraper l'historique d'une organisation par son e-mail
    Soit ma boîte Gmail contient :
      | moment           | de                | à                 | copie             | dossier | objet     |
      | 2026-10-01 09:00 | contact@globex.fr | michel@exemple.fr |                   | INBOX   | Devis     |
      | 2026-10-02 09:00 | michel@exemple.fr | contact@globex.fr |                   | SENT    | RE: Devis |
      | 2026-10-03 09:00 | michel@exemple.fr | bob@globex.fr     | contact@globex.fr | SENT    | Relance   |
    Quand je rattrape l'historique Gmail de "Globex"
    Alors la timeline de "Globex" est, du plus récent au plus ancien :
      | moment           | canal  | sens   | interlocuteur |
      | 02/10/2026 09:00 | E-mail | envoyé | Globex        |
      | 01/10/2026 09:00 | E-mail | reçu   | Globex        |

  Scénario: Le rattrapage se limite aux 12 derniers mois
    Soit ma boîte Gmail contient :
      | moment           | de          | à                 | copie | dossier | objet  |
      | 2025-11-17 09:00 | ada@acme.fr | michel@exemple.fr |       | INBOX   | Ancien |
      | 2025-11-19 09:00 | ada@acme.fr | michel@exemple.fr |       | INBOX   | Récent |
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens   | statut |
      | 03/09/2026 09:00 | E-mail | envoyé | Fait   |
      | 02/09/2026 09:00 | E-mail | reçu   | Fait   |
      | 19/11/2025 09:00 | E-mail | reçu   | Fait   |

  Scénario: Le rattrapage lit les 200 e-mails les plus récents
    Soit ma boîte Gmail contient 199 e-mails de "ada@acme.fr" reçus depuis le "2026-10-01 09:00"
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors 199 échanges sont importés
    Et l'e-mail "RE: Présentation" n'est pas importé

  Plan du scénario: Le rattrapage est proposé sur la fiche d'un contact ou d'une organisation qui a un e-mail
    Alors le rattrapage Gmail de "<correspondant>" m'est proposé

    Exemples:
      | correspondant |
      | Ada Lovelace  |
      | Globex        |

  Scénario: Sans import activé, le rattrapage n'est pas proposé
    Soit je coupe l'import de mes e-mails Gmail
    Alors le rattrapage Gmail de "Ada Lovelace" ne m'est pas proposé

  Scénario: Sans le droit aux données personnelles, le rattrapage n'est pas proposé
    Soit je perds le droit aux données personnelles
    Alors le rattrapage Gmail de "Globex" ne m'est pas proposé

  Scénario: Sans e-mail, le rattrapage n'est pas proposé
    Soit le contact "Alan Turing" de "ACME"
    Alors le rattrapage Gmail de "Alan Turing" ne m'est pas proposé

  Scénario: L'accès retiré chez Google, l'import est à reconnecter
    Soit je retire l'accès de Prospecter dans mon compte Google
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors ce n'est pas refusé
    Et l'import de mes e-mails Gmail est "à reconnecter"
    Et aucun échange n'est importé

  Scénario: Sans import activé, pas de rattrapage
    Soit je coupe l'import de mes e-mails Gmail
    Quand je rattrape l'historique Gmail de "Ada Lovelace"
    Alors c'est refusé : "The e-mail import is not active"

  Scénario: Sans le droit aux données personnelles, pas de rattrapage
    Soit je perds le droit aux données personnelles
    Quand je rattrape l'historique Gmail de "Globex"
    Alors c'est refusé : "Only a member allowed to manage personal data can reimport e-mails from their mailbox"

  Scénario: Sans e-mail, pas de rattrapage
    Soit le contact "Alan Turing" de "ACME"
    Quand je rattrape l'historique Gmail de "Alan Turing"
    Alors c'est refusé : "The contact or organization has no e-mail address to search for"

  Plan du scénario: Un contact ou une organisation d'un autre compte reste introuvable
    Soit l'organisation "Initech SAS" qui utilise Prospecter
    Et l'organisation "Initech" d'e-mail "contact@initech.fr" appartient à "Initech SAS"
    Et le contact "Grace Hopper" de "Initech" a l'adresse e-mail "grace@initech.fr"
    Quand je rattrape l'historique Gmail de "<correspondant>"
    Alors c'est introuvable

    Exemples:
      | correspondant |
      | Grace Hopper  |
      | Initech       |

  Plan du scénario: Sans connexion, un contact ou une organisation d'un autre compte reste introuvable
    Soit l'organisation "Initech SAS" qui utilise Prospecter
    Et l'organisation "Initech" d'e-mail "contact@initech.fr" appartient à "Initech SAS"
    Et le contact "Grace Hopper" de "Initech" a l'adresse e-mail "grace@initech.fr"
    Et j'ai déconnecté mon compte Google
    Quand je rattrape l'historique Gmail de "<correspondant>"
    Alors c'est introuvable, pour la raison "<raison>"

    Exemples:
      | correspondant | raison              |
      | Grace Hopper  | unknownContact      |
      | Initech       | unknownOrganization |
