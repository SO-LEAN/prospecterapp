# language: fr
Fonctionnalité: Retirer le logo d'une organisation
  En tant que prospecteur
  Je veux retirer un logo qui n'est plus le bon
  Afin que la fiche n'affiche pas une image erronée

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le logo est retiré de la fiche et du stockage
    Soit l'organisation "ACME" fait partie de mes organisations avec :
      | logo | acme.png |
    Quand je retire le logo de "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | logo | aucun |
    Et le stockage ne contient plus "acme.png"

  Scénario: Sans logo, il n'y a rien à retirer
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand je retire le logo de "ACME"
    Alors c'est refusé : "There is no logo to remove"

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent" avec :
      | logo | initech.png |
    Quand je retire le logo de "Initech"
    Alors c'est introuvable
    Et le stockage contient "initech.png"
