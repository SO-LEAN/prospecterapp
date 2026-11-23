# language: fr
Fonctionnalité: Mener une campagne
  En tant que prospecteur
  Je veux passer d'un destinataire au suivant, en notant l'échange que j'ai eu avec chacun ou en l'écartant
  Afin de mener une campagne démarrée comme une liste de tâches, jusqu'à ce qu'elle soit terminée

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a le téléphone "06 12 34 56 78"
    Et le contact "Alan Turing" de "ACME" a le téléphone "06 98 76 54 32"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et la campagne "Appels ESN" démarrée, canal "Appel", avec "Ada Lovelace" et "Alan Turing"
    Et nous sommes le 16/11/2026 à 17h00

  Scénario: Noter l'échange d'un destinataire le traite
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" l'échange :
      | moment | 16/11/2026 à 16h55              |
      | texte  | Intéressée, rappeler en janvier |
    Alors "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Et la timeline de "Ada Lovelace" montre l'appel, de la campagne "Appels ESN"

  Scénario: L'échange prend le canal de la campagne
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" un échange
    Alors c'est un appel émis

  Scénario: Un appel qui n'aboutit pas est noté manqué, et le destinataire est traité
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" un appel manqué
    Alors "Ada Lovelace" est traitée dans la campagne "Appels ESN", appel manqué

  Scénario: J'écarte un destinataire sans échange
    Quand j'écarte "Alan Turing" de la campagne "Appels ESN", pour "N'est plus dans la société"
    Alors "Alan Turing" est écarté dans la campagne "Appels ESN", pour "N'est plus dans la société"

  Scénario: Un destinataire traité ne reçoit pas un second échange de la campagne
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" un échange
    Alors c'est refusé : "This recipient is already processed"

  Scénario: Un destinataire écarté n'est pas écarté de nouveau
    Soit "Alan Turing" est écarté de la campagne "Appels ESN"
    Quand j'écarte "Alan Turing" de la campagne "Appels ESN"
    Alors c'est refusé : "This recipient is already processed"

  Scénario: Quand plus personne n'est à traiter, la campagne est terminée
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Quand j'écarte "Alan Turing" de la campagne "Appels ESN"
    Alors la campagne "Appels ESN" est terminée le 16/11/2026 à 17h00

  Scénario: Le dernier échange noté termine la campagne
    Soit "Alan Turing" est écarté de la campagne "Appels ESN"
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" un échange
    Alors la campagne "Appels ESN" est terminée le 16/11/2026 à 17h00

  Scénario: Le dernier destinataire à traiter effacé termine la campagne
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Quand j'efface le contact "Alan Turing"
    Alors la campagne "Appels ESN" est terminée le 16/11/2026 à 17h00

  Scénario: On ne mène pas une campagne arrêtée
    Soit la campagne "Appels ESN" est arrêtée
    Quand je note pour "Ada Lovelace" dans la campagne "Appels ESN" un échange
    Alors c'est refusé : "Only a started campaign can be run"

  Scénario: On n'écarte personne d'une campagne arrêtée
    Soit la campagne "Appels ESN" est arrêtée
    Quand j'écarte "Alan Turing" de la campagne "Appels ESN"
    Alors c'est refusé : "Only a started campaign can be run"

  Scénario: On ne note pour la campagne que l'échange d'un de ses destinataires
    Soit le contact "Grace Hopper" de "ACME" a le téléphone "06 11 22 33 44"
    Quand je note pour "Grace Hopper" dans la campagne "Appels ESN" un échange
    Alors c'est introuvable

  Scénario: L'avancement de la campagne
    Soit "Ada Lovelace" est traitée dans la campagne "Appels ESN"
    Quand je consulte la campagne "Appels ESN"
    Alors son avancement est 1 traité sur 2, 0 écarté
