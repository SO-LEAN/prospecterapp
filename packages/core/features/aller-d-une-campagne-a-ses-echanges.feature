# language: fr
Fonctionnalité: Le lien entre une campagne et ses échanges
  En tant que prospecteur
  Je veux aller d'un destinataire à l'échange noté pour lui, et d'un échange à la campagne dont il vient
  Afin de relire ce qui a été dit dans une campagne, et qu'un échange supprimé remette son destinataire à traiter

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Alan Turing" de "ACME" a l'adresse e-mail "alan@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et la campagne "Relance" démarrée, canal "E-mail", avec "Ada Lovelace" et "Alan Turing"
    Et j'ai noté pour "Ada Lovelace" dans la campagne "Relance" un e-mail émis

  Scénario: Le destinataire mène à son échange
    Quand je consulte la campagne "Relance"
    Alors "Ada Lovelace" est traitée, avec un lien vers son échange

  Scénario: L'échange mène à sa campagne
    Quand je consulte l'échange de "Ada Lovelace"
    Alors il vient de la campagne "Relance", avec un lien vers elle

  Scénario: Supprimer l'échange remet le destinataire à traiter
    Quand je supprime l'échange de "Ada Lovelace"
    Alors "Ada Lovelace" est à traiter dans la campagne "Relance"

  Scénario: Une campagne terminée reprend si un échange est supprimé
    Soit "Alan Turing" est écarté de la campagne "Relance"
    Et la campagne "Relance" est terminée
    Quand je supprime l'échange de "Ada Lovelace"
    Alors la campagne "Relance" est de nouveau démarrée
    Et "Ada Lovelace" est à traiter dans la campagne "Relance"

  Scénario: Une campagne arrêtée reste arrêtée
    Soit la campagne "Relance" est arrêtée
    Quand je supprime l'échange de "Ada Lovelace"
    Alors la campagne "Relance" est toujours arrêtée
    Et "Ada Lovelace" est à traiter dans la campagne "Relance"

  Scénario: Supprimer un autre échange du destinataire ne le remet pas à traiter
    Soit j'ai noté un autre e-mail envoyé à "Ada Lovelace", hors de la campagne
    Quand je supprime cet autre échange
    Alors "Ada Lovelace" est traitée, avec un lien vers son échange

  Scénario: Arrêter de prospecter la seule organisation du contact l'efface de la campagne, ce qui a été fait gardé
    Quand j'arrête de prospecter "ACME"
    Alors dans la campagne "Relance", les contacts effacés sont « traité » et « plus à traiter »

  Scénario: Un échange effacé avec son organisation ne remet pas le destinataire à traiter
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et je rattache le contact "Ada Lovelace" à "Globex"
    Quand j'arrête de prospecter "ACME"
    Alors "Ada Lovelace" est traitée dans la campagne "Relance"

  Scénario: Une campagne effacée par l'exploitation laisse ses échanges, sans lien
    Quand l'exploitation efface la campagne "Relance"
    Alors l'échange de "Ada Lovelace" reste dans sa timeline, sans campagne

  Scénario: Le fait publié d'un échange effacé nomme encore son contact et sa campagne
    Quand je supprime l'échange de "Ada Lovelace"
    Alors le fait publié de cet échange, absent, nomme la campagne "Relance" et le contact "Ada Lovelace"
