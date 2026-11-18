# language: fr
Fonctionnalité: Une campagne garde ce que l'équipe a fait avec un contact effacé
  En tant que prospecteur
  Je veux qu'une campagne démarrée garde ce que l'équipe a fait avec chaque destinataire, même effacé ensuite
  Afin que son avancement et son bilan restent vrais

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a le téléphone "06 12 34 56 78"
    Et le contact "Alan Turing" de "ACME" a le téléphone "06 98 76 54 32"
    Et le contact "Grace Hopper" de "ACME" a le téléphone "06 11 22 33 44"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et la campagne "Appels ESN" démarrée, canal "Appel", avec "Ada Lovelace", "Alan Turing" et "Grace Hopper"

  Scénario: Un destinataire traité puis effacé reste traité
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Quand j'efface le contact "Ada Lovelace"
    Alors dans la campagne "Appels ESN", un contact effacé est traité

  Scénario: Un appel manqué puis effacé reste manqué
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN", appel manqué
    Quand j'efface le contact "Ada Lovelace"
    Alors dans la campagne "Appels ESN", un contact effacé est traité, appel manqué

  Scénario: Un destinataire écarté puis effacé reste écarté
    Soit "Alan Turing" est écarté de la campagne "Appels ESN"
    Quand j'efface le contact "Alan Turing"
    Alors dans la campagne "Appels ESN", un contact effacé est écarté

  Scénario: Un destinataire à traiter effacé n'est plus à traiter
    Quand j'efface le contact "Grace Hopper"
    Alors dans la campagne "Appels ESN", un contact effacé n'est plus à traiter

  Scénario: L'avancement compte ce qui a été fait avant l'effacement
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Et "Alan Turing" est écarté de la campagne "Appels ESN"
    Quand j'efface les contacts "Ada Lovelace" et "Alan Turing"
    Alors l'avancement de la campagne "Appels ESN" est 1 traité sur 3, 1 écarté
