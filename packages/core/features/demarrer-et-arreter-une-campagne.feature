# language: fr
Fonctionnalité: Démarrer et arrêter une campagne
  En tant que prospecteur
  Je veux démarrer une campagne une fois sa liste bien définie, puis l'arrêter sans la supprimer
  Afin qu'il ne reste qu'à la mener, et qu'une campagne menée reste visible

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et la campagne "Relance" figée, canal "E-mail", avec "Ada Lovelace"
    Et nous sommes le 16/11/2026 à 17h00

  Scénario: Je démarre une campagne figée
    Quand je démarre la campagne "Relance"
    Alors elle est démarrée le 16/11/2026 à 17h00

  Scénario: On ne démarre pas un brouillon
    Soit la campagne "Brouillon" en brouillon
    Quand je démarre la campagne "Brouillon"
    Alors c'est refusé : "Only a frozen campaign can start"

  Scénario: Démarrée, la campagne ne change plus
    Soit la campagne "Relance" est démarrée
    Quand j'en retire "Ada Lovelace"
    Alors c'est refusé : "A started campaign can no longer change"

  Scénario: Démarrée, la campagne ne se supprime plus
    Soit la campagne "Relance" est démarrée
    Quand je supprime la campagne "Relance"
    Alors c'est refusé : "A started campaign can be stopped, not deleted"

  Scénario: J'arrête une campagne démarrée
    Soit la campagne "Relance" est démarrée
    Quand j'arrête la campagne "Relance"
    Alors elle est arrêtée le 16/11/2026 à 17h00
    Et elle est toujours dans la liste des campagnes

  Scénario: Une campagne arrêtée ne redémarre pas
    Soit la campagne "Relance" est arrêtée
    Quand je démarre la campagne "Relance"
    Alors c'est refusé : "Only a frozen campaign can start"

  Scénario: Un contact effacé reste dans une campagne démarrée, sans son nom
    Soit la campagne "Relance" est démarrée
    Quand j'efface le contact "Ada Lovelace"
    Alors son destinataire est « Contact effacé », plus à traiter

  Scénario: L'exploitation efface une campagne, même démarrée
    Soit la campagne "Relance" est démarrée
    Quand l'exploitation efface la campagne "Relance"
    Alors je n'ai plus de campagne

  Scénario: Une campagne figée vidée par l'effacement de ses contacts ne démarre plus
    Soit j'efface le contact "Ada Lovelace"
    Quand je démarre la campagne "Relance"
    Alors c'est refusé : "A campaign needs at least one recipient"

  Scénario: Une campagne figée vidée par l'effacement de ses contacts se supprime encore
    Soit j'efface le contact "Ada Lovelace"
    Quand je supprime la campagne "Relance"
    Alors je n'ai plus de campagne

  Scénario: Une campagne figée vidée par l'effacement de ses contacts dit qu'elle n'a plus de destinataire
    Soit j'efface le contact "Ada Lovelace"
    Quand je liste les campagnes
    Alors les campagnes listées sont :
      | nom     | canal  | statut | contactés | ciblés |
      | Relance | E-mail | figée  | 0         | 0      |
