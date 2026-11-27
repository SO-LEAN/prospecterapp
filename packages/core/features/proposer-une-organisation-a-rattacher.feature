# language: fr
@ProposeOrganizationToAttach
Fonctionnalité: Proposer l'organisation où rattacher un contact
  En tant que prospecteur
  Je veux qu'on me propose l'organisation d'un contact à rattacher d'après le domaine de son e-mail
  Afin de le rattacher sans la chercher

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" d'e-mail "contact@acme.fr" appartient à "SOLEAN IT"
    Et nous sommes le 18/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: L'organisation proposée est celle du domaine de son e-mail
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je regarde à quelle organisation la rattacher
    Alors "ACME" est proposée

  Scénario: Un domaine de messagerie grand public ne propose rien
    Soit l'organisation "Les amis de Gmail" d'e-mail "amis@gmail.com" appartient à "SOLEAN IT"
    Et "Bob Martin" (bob.martin@gmail.com) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je regarde à quelle organisation le rattacher
    Alors aucune organisation n'est proposée

  Scénario: Un domaine qu'aucune de mes organisations n'a ne propose rien
    Soit "Linus Torvalds" (linus@kernel.test) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je regarde à quelle organisation le rattacher
    Alors aucune organisation n'est proposée

  Scénario: L'organisation d'un autre compte n'est pas proposée
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" d'e-mail "contact@initech.test" appartient à "Concurrent"
    Et "Peter Gibbons" (peter@initech.test) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je regarde à quelle organisation le rattacher
    Alors aucune organisation n'est proposée

  Scénario: Un contact sans e-mail ne propose rien
    Soit le contact "Alan Turing" de "ACME"
    Quand je regarde à quelle organisation rattacher "Alan Turing"
    Alors aucune organisation n'est proposée

  Scénario: Sans le droit aux données personnelles, je ne lis pas l'e-mail d'un contact pour lui proposer une organisation
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je regarde à quelle organisation rattacher "Ada Lovelace"
    Alors c'est introuvable, pour la raison "unknownContact"

  Scénario: Un contact inconnu est introuvable
    Quand je regarde à quelle organisation rattacher "Personne"
    Alors c'est introuvable, pour la raison "unknownContact"
