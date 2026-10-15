# language: fr
Fonctionnalité: Rattacher une organisation à sa holding
  En tant que prospecteur
  Je veux savoir à quel groupe appartient une organisation
  Afin de prospecter le groupe dans son ensemble

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations
    Et l'organisation "Groupe ACME" fait partie de mes organisations

  Scénario: L'organisation est rattachée à une holding que je prospecte
    Quand je rattache "ACME" à la holding "Groupe ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | Groupe ACME |

  Scénario: L'organisation est détachée de sa holding
    Soit "ACME" est rattachée à la holding "Groupe ACME"
    Quand je détache "ACME" de sa holding
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | |

  Scénario: Sans holding, il n'y a rien à détacher
    Quand je détache "ACME" de sa holding
    Alors c'est refusé : "There is no holding to detach from"

  Scénario: La holding doit exister
    Quand je rattache "ACME" à la holding "Inconnue"
    Alors c'est introuvable

  Scénario: La holding doit être une de mes organisations
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je rattache "ACME" à la holding "Initech"
    Alors c'est introuvable

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je rattache "Initech" à la holding "Groupe ACME"
    Alors c'est introuvable
