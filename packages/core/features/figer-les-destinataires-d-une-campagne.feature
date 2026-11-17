# language: fr
Fonctionnalité: Figer les destinataires d'une campagne
  En tant que prospecteur
  Je veux figer la liste des destinataires quand la recherche d'une campagne me convient
  Afin qu'elle ne suive plus les tags, puis d'y retirer ou d'y ajouter des contacts à la main

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et l'organisation "Globex" appartient à "SOLEAN IT", taguée "#EDITEUR"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Alan Turing" de "Globex" a l'adresse e-mail "alan@globex.test"
    Et le contact "Grace Hopper" de "Globex"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits
    Et la campagne "Relance" en brouillon, canal "E-mail", tags "#ESN"

  Scénario: Je fige la liste
    Quand je fige la campagne "Relance"
    Alors elle est figée
    Et ses destinataires sont "Ada Lovelace", à traiter

  Scénario: Figée, la liste ne suit plus les tags
    Soit la campagne "Relance" est figée
    Quand je tague "Globex" avec "#ESN"
    Alors ses destinataires sont toujours "Ada Lovelace"

  Scénario: Figée, la recherche ne change plus
    Soit la campagne "Relance" est figée
    Quand je change ses tags en "#EDITEUR"
    Alors c'est refusé : "The search of a frozen campaign does not change"

  Scénario: Figée, son nom et son message changent encore
    Soit la campagne "Relance" est figée
    Quand je la renomme "Relance de l'hiver" et change son message
    Alors la campagne "Relance de l'hiver" est enregistrée ainsi

  Scénario: J'ajoute les résultats d'une autre recherche, sans doublon
    Soit la campagne "Relance" est figée
    Quand j'y ajoute les contacts tagués "#EMAIL"
    Alors ses destinataires sont "Ada Lovelace" et "Alan Turing"

  Scénario: J'ajoute un contact choisi
    Soit la campagne "Relance" est figée
    Quand j'y ajoute "Alan Turing"
    Alors "Alan Turing" est un destinataire

  Scénario: Un contact sans le moyen du canal n'est pas ajouté
    Soit la campagne "Relance" est figée
    Quand j'y ajoute "Grace Hopper"
    Alors c'est refusé : "Grace Hopper cannot be reached by email"

  Scénario: Je retire un destinataire
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Quand j'en retire "Alan Turing"
    Alors ses destinataires sont "Ada Lovelace"

  Scénario: On ne retire pas le dernier destinataire
    Soit la campagne "Relance" est figée
    Quand j'en retire "Ada Lovelace"
    Alors c'est refusé : "A campaign has at least one recipient"

  Scénario: On ne fige pas une liste vide
    Soit la campagne "Vide" en brouillon ne cible personne
    Quand je fige la campagne "Vide"
    Alors c'est refusé : "A campaign has at least one recipient"

  Scénario: Un contact effacé quitte une campagne figée
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Quand j'efface le contact "Alan Turing"
    Alors ses destinataires sont "Ada Lovelace"

  Scénario: Une campagne figée se supprime encore
    Soit la campagne "Relance" est figée
    Quand je supprime la campagne "Relance"
    Alors je n'ai plus de campagne

  Scénario: Figée, elle dit les destinataires comptés
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Quand je liste les campagnes
    Alors les campagnes listées sont :
      | nom     | canal  | statut | contactés | ciblés |
      | Relance | E-mail | figée  | 0         | 2      |
