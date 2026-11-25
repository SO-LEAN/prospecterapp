# language: fr
@ReviseLegalIdentity
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
    Alors c'est refusé : "An organization needs a corporate name or an e-mail address"
    Et l'organisation "Globex" est enregistrée avec :
      | raison sociale | Globex |

  Scénario: Noter un SIRET, quel que soit l'espacement de sa saisie
    Quand je note le SIRET "552 100 554 00013" sur "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | SIRET           | 55210055400013    |
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |

  Scénario: Un SIRET dont la clé est fausse est refusé
    Quand je note le SIRET "55210055400014" sur "ACME"
    Alors c'est refusé : "SIRET "55210055400014" is not valid"
    Et l'organisation "ACME" est enregistrée avec :
      | SIRET | |

  Scénario: Un établissement de La Poste a pour clé la somme de ses chiffres
    Quand je note le SIRET "356 000 000 49837" sur "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | SIRET | 35600000049837 |

  Scénario: Un SIRET ne désigne qu'une organisation du compte
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | SIRET | 55210055400013 |
    Quand je note le SIRET "552 100 554 00013" sur "ACME"
    Alors c'est refusé : "ACME Lyon already has this SIRET"
    Et l'organisation "ACME" est enregistrée avec :
      | SIRET | |

  Scénario: Une organisation garde son SIRET en révisant son identité
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | SIRET | 55210055400013 |
    Quand je note le SIRET "55210055400013" sur "ACME Lyon"
    Alors ce n'est pas refusé

  Scénario: Un autre compte prospecte le même établissement
    Soit l'organisation "Globex" qui utilise Prospecter
    Et l'organisation "ACME Lyon" appartient à "Globex" avec :
      | SIRET | 55210055400013 |
    Quand je note le SIRET "55210055400013" sur "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | SIRET | 55210055400013 |
