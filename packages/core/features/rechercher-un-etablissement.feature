# language: fr
Fonctionnalité: Rechercher l'établissement d'une organisation dans l'annuaire des entreprises
  En tant que prospecteur
  Je veux retrouver le SIRET d'une organisation sans le taper
  Afin de la désigner sans erreur, quel que soit l'orthographe de son nom

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le géocodeur trouve "Lyon" à Lyon 3e (69383)
    Et l'annuaire des entreprises connaît les établissements :
      | SIRET          | dénomination | adresse                            | activité | commune | état   |
      | 55210055400013 | ACME         | 1 RUE DE LA PAIX 75002 PARIS       | 62.02A   | 75102   | ouvert |
      | 55210055400021 | ACME         | 17 RUE DOCTEUR BOUCHUT 69003 LYON  | 62.02A   | 69383   | fermé  |
      | 55210055400039 | GLOBEX       | 3 RUE SERVIENT 69003 LYON          | 47.64Z   | 69383   | ouvert |

  Scénario: Les établissements de sa ville d'abord
    Soit l'organisation "ACME" fait partie de mes organisations avec :
      | ville | Lyon |
    Quand je cherche le SIRET de "ACME"
    Alors l'annuaire me propose :
      | SIRET          | dénomination | adresse                           | activité | fermé |
      | 55210055400021 | ACME         | 17 RUE DOCTEUR BOUCHUT 69003 LYON | 62.02A   | oui   |
      | 55210055400013 | ACME         | 1 RUE DE LA PAIX 75002 PARIS      | 62.02A   | non   |
    Quand je note le SIRET "55210055400021" sur "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | SIRET | 55210055400021 |

  Scénario: Sans commune, les établissements de partout, dans l'ordre de l'annuaire
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand je cherche le SIRET de "ACME"
    Alors l'annuaire me propose :
      | SIRET          | dénomination | adresse                           | activité | fermé |
      | 55210055400013 | ACME         | 1 RUE DE LA PAIX 75002 PARIS      | 62.02A   | non   |
      | 55210055400021 | ACME         | 17 RUE DOCTEUR BOUCHUT 69003 LYON | 62.02A   | oui   |

  Scénario: L'annuaire injoignable n'empêche pas de taper le SIRET
    Soit l'organisation "ACME" fait partie de mes organisations
    Et l'annuaire des entreprises ne répond pas
    Quand je cherche le SIRET de "ACME"
    Alors l'annuaire ne me propose rien, faute de réponse
    Quand je note le SIRET "552 100 554 00013" sur "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | SIRET | 55210055400013 |

  Scénario: Sans raison sociale, rien à chercher
    Soit l'organisation sans raison sociale "contact@acme.test" fait partie de mes organisations
    Quand je cherche le SIRET de "contact@acme.test"
    Alors l'annuaire ne me propose rien
