# language: fr
Fonctionnalité: Changer les coordonnées d'une organisation
  En tant que prospecteur
  Je veux tenir à jour l'e-mail, le téléphone et la langue d'une organisation
  Sans toucher au reste de sa fiche

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |

  Scénario: Seules les coordonnées changent
    Quand je change les coordonnées de "ACME" :
      | e-mail    | hello@acme.test |
      | téléphone | 03 88 11 22 33  |
      | langue    | DE              |
    Alors l'organisation "ACME" est enregistrée avec :
      | e-mail          | hello@acme.test   |
      | téléphone       | +33 3 88 11 22 33 |
      | langue          | DE                |
      | forme juridique | SAS               |

  Scénario: Le téléphone est gardé tel que le monde le compose, quelle que soit sa saisie
    Quand je change les coordonnées de "ACME" :
      | téléphone | 0033.3.88.11.22.33 |
    Alors l'organisation "ACME" est enregistrée avec :
      | téléphone | +33 3 88 11 22 33 |

  Scénario: Un numéro de téléphone invalide est refusé
    Quand je change les coordonnées de "ACME" :
      | téléphone | 12 |
    Alors c'est refusé : "Phone number "12" is not valid"

  Scénario: L'e-mail ne peut pas être celui d'une autre organisation de ma base
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je change les coordonnées de "ACME" :
      | e-mail | contact@globex.test |
    Alors c'est refusé : "Email already used"

  Scénario: L'e-mail d'une autre organisation l'est quelle que soit sa casse
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je change les coordonnées de "ACME" :
      | e-mail | Contact@GLOBEX.test |
    Alors c'est refusé : "Email already used"

  Scénario: Sans raison sociale, l'e-mail ne peut pas être effacé
    Soit l'organisation sans raison sociale "contact@globex.test" fait partie de mes organisations
    Quand je change les coordonnées de "contact@globex.test" :
      | e-mail | |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"
