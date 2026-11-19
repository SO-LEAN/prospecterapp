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

  Scénario: La page LinkedIn est gardée sous une seule forme
    Quand je change les coordonnées de "ACME" :
      | page LinkedIn | http://fr.linkedin.com/company/ACME-Corp/about/ |
    Alors l'organisation "ACME" est enregistrée avec :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |

  Scénario: Ce qui n'est pas une page entreprise est refusé
    Quand je change les coordonnées de "ACME" :
      | page LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : ""https://www.linkedin.com/in/ada-lovelace" is not the LinkedIn page of a company"

  Scénario: Une page LinkedIn ne désigne qu'une organisation du compte
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
    Quand je change les coordonnées de "ACME" :
      | page LinkedIn | linkedin.com/company/acme-corp/ |
    Alors c'est refusé : "LinkedIn page already the one of ACME Lyon"

  Scénario: Un domaine ne désigne qu'une organisation du compte
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je change les coordonnées de "ACME" :
      | site web | https://acme.fr/blog |
    Alors c'est refusé : "Website already the one of ACME Lyon"

  Scénario: Une organisation garde sa page LinkedIn et son site en changeant ses coordonnées
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
      | site web      | https://www.acme.fr                        |
    Quand je change les coordonnées de "ACME Lyon" :
      | téléphone     | 03 88 11 22 33                             |
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
      | site web      | https://acme.fr                            |
    Alors ce n'est pas refusé

  Scénario: Un autre compte note la même page LinkedIn et le même domaine
    Soit l'organisation "Globex" qui utilise Prospecter
    Et l'organisation "ACME Lyon" appartient à "Globex" avec :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
      | site web      | https://www.acme.fr                        |
    Quand je change les coordonnées de "ACME" :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
      | site web      | https://acme.fr                            |
    Alors l'organisation "ACME" est enregistrée avec :
      | page LinkedIn | https://www.linkedin.com/company/acme-corp |
      | site web      | https://acme.fr                            |

  Scénario: Le site web est reconnu par son domaine
    Quand je change les coordonnées de "ACME" :
      | site web | HTTPS://www.Acme.fr/contact |
    Alors l'organisation "ACME" est enregistrée avec :
      | site web        | https://www.acme.fr |
      | domaine du site | acme.fr             |
