# language: fr
Fonctionnalité: Le langage publié des contextes
  En tant que contexte de Prospecter
  Je veux poser à un autre contexte les seules questions qu'il publie, sur plusieurs éléments à la fois
  Afin que chacun change son modèle sans casser les autres, et qu'une question coûte un seul appel

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"

  Scénario: Accounts dit qui sont des membres et s'ils gèrent les données personnelles
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Grace Hopper |
      | Alan Turing  |
      | Personne     |
    Alors Accounts répond que "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et Accounts répond que "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et Accounts ne répond rien de "Personne"

  Scénario: Accounts dit si un membre administre son compte
    Soit "Ada Byron" est membre de "SOLEAN IT" qui l'administre
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Ada Byron   |
      | Alan Turing |
    Alors Accounts répond que "Ada Byron" administre son compte
    Et Accounts répond que "Alan Turing" n'administre pas son compte

  Scénario: Accounts dit combien de temps avant ses échanges un membre veut être rappelé
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et "Ada Byron" est membre de "SOLEAN IT" qui l'administre
    Et "Grace Hopper" a réglé ses rappels à 15 minutes avant
    Et "Alan Turing" a désactivé ses rappels
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Grace Hopper |
      | Alan Turing  |
      | Ada Byron    |
    Alors Accounts répond que "Grace Hopper" veut être rappelée 15 minutes avant
    Et Accounts répond que "Alan Turing" ne veut pas être rappelé
    Et Accounts répond que "Ada Byron" veut être rappelée 10 minutes avant

  Scénario: PersonalData nomme des contacts comme un membre les lit
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à PersonalData les noms, comme "Grace Hopper" les lit, de :
      | Ada Lovelace |
      | Personne     |
    Alors PersonalData répond les noms :
      | Ada Lovelace |
    Quand un autre contexte demande à PersonalData les noms, comme "Alan Turing" les lit, de :
      | Ada Lovelace |
    Alors PersonalData répond les noms :
      | A***a L***e |
    Quand un autre contexte demande à PersonalData les noms, comme "Personne" les lit, de :
      | Ada Lovelace |
    Alors PersonalData ne répond aucun nom

  Scénario: PersonalData dit lesquelles de plusieurs personnes sont des contacts d'un compte
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à PersonalData lesquelles sont des contacts de "SOLEAN IT" :
      | Ada Lovelace |
      | Grace Hopper |
      | Personne     |
    Alors PersonalData répond :
      | Ada Lovelace |
    Quand un autre contexte demande à PersonalData lesquelles sont des contacts de "Concurrent" :
      | Ada Lovelace |
    Alors PersonalData ne répond aucune

  Scénario: Prospecting dit lesquelles de plusieurs organisations sont prospectées par un compte
    Quand un autre contexte demande à Prospecting lesquelles sont prospectées par "SOLEAN IT" :
      | ACME     |
      | Inconnue |
    Alors Prospecting répond :
      | ACME |
    Quand un autre contexte demande à Prospecting lesquelles sont prospectées par "Concurrent" :
      | ACME |
    Alors Prospecting ne répond aucune

  Scénario: Prospecting liste les faits des organisations d'un compte, par tranche
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et l'organisation "Initech" appartient à "SOLEAN IT"
    Quand un autre contexte demande à Prospecting 2 faits des organisations de "SOLEAN IT"
    Alors Prospecting répond les faits des organisations :
      | organisation | révision | présente | géolocalisée |
      | Initech      | 1        | oui      | non          |
      | Globex       | 1        | oui      | non          |
    Quand il demande la suite
    Alors Prospecting répond les faits des organisations :
      | organisation | révision | présente | géolocalisée |
      | ACME         | 2        | oui      | non          |
    Et Prospecting ne donne pas de suite

  Scénario: Prospecting liste les faits des échanges d'un compte, par tranche
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté 3 échanges avec "ACME" elle-même
    Quand un autre contexte demande à Prospecting 2 faits des échanges de "SOLEAN IT"
    Alors Prospecting répond les faits de 2 échanges, présents, faits, révision 1
    Quand il demande la suite
    Alors Prospecting répond les faits de 1 échange, présent, fait, révision 1
    Et Prospecting ne donne pas de suite

  Scénario: Prospecting dit qui tient des échanges, quand et comment
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00 pour 1 heure au lieu "Café de Flore"
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal       | moment           | durée  | lieu          | lien | contact      | organisation |
      | moi       | À venir | Rendez-vous | 17/11/2026 14:00 | 60 min | Café de Flore |      | Ada Lovelace | ACME         |
    Quand un autre contexte demande à Prospecting, de "Concurrent", cet échange et un échange inconnu
    Alors Prospecting ne répond aucun échange

  Scénario: Prospecting dit un échange avec l'organisation elle-même, sans contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié une visio avec "ACME" le 17/11/2026 à 9h00 au lien "https://meet.example/acme"
    Et j'annule cette visio
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal | moment           | durée  | lieu | lien                      | contact | organisation |
      | moi       | Annulé  | Visio | 17/11/2026 09:00 | 30 min |      | https://meet.example/acme |         | ACME         |

  Scénario: PersonalData liste les faits des contacts d'un compte, par tranche
    Soit le contact "Alan Turing" de "ACME"
    Et le contact "Linus Torvalds" de "ACME"
    Et "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à PersonalData 2 faits des contacts de "SOLEAN IT"
    Alors PersonalData répond les faits des contacts :
      | contact        | révision | présent |
      | Linus Torvalds | 1        | oui     |
      | Alan Turing    | 1        | oui     |
    Quand il demande la suite
    Alors PersonalData répond les faits des contacts :
      | contact      | révision | présent |
      | Ada Lovelace | 1        | oui     |
    Et PersonalData ne donne pas de suite

  Scénario: Accounts liste les comptes, par tranche
    Quand un autre contexte demande à Accounts 1 compte
    Alors Accounts répond les comptes :
      | Concurrent |
    Quand il demande la suite
    Alors Accounts répond les comptes :
      | SOLEAN IT |
    Et Accounts ne donne pas de suite

  Scénario: Un compte inconnu n'a aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des organisations d'un compte inconnu
    Alors Prospecting ne répond aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des échanges d'un compte inconnu
    Alors Prospecting ne répond aucun fait
    Quand un autre contexte demande à PersonalData 10 faits des contacts d'un compte inconnu
    Alors PersonalData ne répond aucun fait
