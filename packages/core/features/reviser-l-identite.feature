# language: fr
Fonctionnalité: Réviser l'identité légale d'une organisation
  En tant que prospecteur
  Je veux corriger la raison sociale, la forme juridique et le type d'une organisation
  Sans toucher au reste de sa fiche

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |

  Scénario: Seule l'identité change
    Quand je révise l'identité de "ACME" :
      | raison sociale  | ACME Corp |
      | forme juridique | SA        |
      | type            | Éditeur   |
    Alors l'organisation "ACME Corp" est enregistrée avec :
      | forme juridique | SA                |
      | type            | Éditeur           |
      | e-mail          | contact@acme.test |

  Scénario: La raison sociale peut être effacée tant qu'il reste un e-mail
    Quand je révise l'identité de "ACME" :
      | raison sociale | |
    Alors ce n'est pas refusé

  Scénario: Sans e-mail, la raison sociale ne peut pas être effacée
    Soit l'organisation "Globex" fait partie de mes organisations
    Quand je révise l'identité de "Globex" :
      | raison sociale | |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"
    Et l'organisation "Globex" est enregistrée avec :
      | raison sociale | Globex |

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je révise l'identité de "Initech" :
      | raison sociale | Initech rachetée |
    Alors c'est introuvable
