# language: fr
Fonctionnalité: Le langage publié des contextes
  En tant que contexte de Prospecter
  Je veux poser à un autre contexte les seules questions qu'il publie
  Afin que chacun change son modèle sans casser les autres

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"

  Scénario: Accounts dit qui est un membre et s'il gère les données personnelles
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui est le membre "Grace Hopper"
    Alors Accounts répond que "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui est le membre "Alan Turing"
    Alors Accounts répond que "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles

  Scénario: Un membre inconnu d'Accounts est introuvable
    Quand un autre contexte demande à Accounts qui est le membre "Personne"
    Alors c'est introuvable

  Scénario: PersonalData dit si une personne est un contact d'un compte
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Alors PersonalData répond que "Ada Lovelace" est un contact de "SOLEAN IT"
    Et PersonalData répond que "Ada Lovelace" n'est pas un contact de "Concurrent"
    Et PersonalData répond que "Grace Hopper" n'est pas un contact de "SOLEAN IT"

  Scénario: Une personne inconnue de PersonalData est introuvable
    Quand un autre contexte demande à PersonalData si "Personne" est un contact de "SOLEAN IT"
    Alors c'est introuvable

  Scénario: Prospecting dit si une organisation est prospectée par un compte
    Alors Prospecting répond que "ACME" est prospectée par "SOLEAN IT"
    Et Prospecting répond que "ACME" n'est pas prospectée par "Concurrent"

  Scénario: Une organisation inconnue de Prospecting est introuvable
    Quand un autre contexte demande à Prospecting si "Inconnue" est prospectée par "SOLEAN IT"
    Alors c'est introuvable
