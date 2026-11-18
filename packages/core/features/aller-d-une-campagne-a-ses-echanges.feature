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
