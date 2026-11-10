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
    Alors Prospecting répond les faits de 2 échanges, présents, révision 1
    Quand il demande la suite
    Alors Prospecting répond les faits de 1 échange, présent, révision 1
    Et Prospecting ne donne pas de suite

  Scénario: Un compte inconnu n'a aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des organisations d'un compte inconnu
    Alors Prospecting ne répond aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des échanges d'un compte inconnu
    Alors Prospecting ne répond aucun fait
