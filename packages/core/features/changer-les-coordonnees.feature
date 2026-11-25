# language: fr
@ChangeContactDetails
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
    Alors c'est refusé : "This e-mail address is already in use"

  Scénario: L'e-mail d'une autre organisation l'est quelle que soit sa casse
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je change les coordonnées de "ACME" :
      | e-mail | Contact@GLOBEX.test |
    Alors c'est refusé : "This e-mail address is already in use"

  Scénario: Sans raison sociale, l'e-mail ne peut pas être effacé
    Soit l'organisation sans raison sociale "contact@globex.test" fait partie de mes organisations
    Quand je change les coordonnées de "contact@globex.test" :
      | e-mail | |
    Alors c'est refusé : "An organization needs a corporate name or an e-mail address"

  Scénario: Une adresse d'une source connue est gardée sous une seule forme
    Quand j'ajoute à "ACME" le lien "HTTPS://www.Welcometothejungle.com/fr/companies/ACME/jobs?page=2"
    Alors "ACME" a la référence Welcome to the Jungle "acme"
    Et "ACME" a le lien "HTTPS://www.Welcometothejungle.com/fr/companies/ACME/jobs?page=2"

  Scénario: La page LinkedIn d'une entreprise est une référence comme une autre
    Quand j'ajoute à "ACME" le lien "http://fr.linkedin.com/company/ACME-Corp/about/"
    Alors "ACME" a la référence LinkedIn entreprise "acme-corp"

  Scénario: Une adresse d'un outil inconnu est acceptée
    Quand j'ajoute à "ACME" le lien "https://www.annuaire-xyz.com/fiche/Acme-SAS?ref=mail#top"
    Alors "ACME" a la référence de source "annuaire-xyz.com" et d'identifiant "annuaire-xyz.com/fiche/acme-sas"

  Scénario: Ce qui n'est pas une adresse est refusé
    Quand j'ajoute à "ACME" le lien "ACME sur LinkedIn"
    Alors c'est refusé : ""ACME sur LinkedIn" is not the address of a web page"

  Scénario: Une source connue ne désigne qu'un genre
    Quand j'ajoute à "ACME" le lien "https://www.malt.fr/profile/adalovelace"
    Alors c'est refusé : "A malt page designates a person"

  Scénario: Une page d'une source connue qu'elle ne reconnaît pas est refusée
    Quand j'ajoute à "ACME" le lien "https://www.linkedin.com/school/acme"
    Alors c'est refusé : "Prospecter does not recognize this linkedin.com page as a person's or an organization's page"

  Scénario: Une référence ne désigne qu'une organisation du compte
    Soit "ACME Lyon" fait partie de mes organisations avec le lien "https://www.welcometothejungle.com/fr/companies/acme"
    Quand j'ajoute à "ACME" le lien "https://www.welcometothejungle.com/en/companies/acme"
    Alors c'est refusé : "ACME Lyon already has this link"

  Scénario: Une organisation garde ses liens en changeant ses coordonnées
    Soit "ACME Lyon" fait partie de mes organisations avec le lien "https://www.welcometothejungle.com/fr/companies/acme"
    Quand je change les coordonnées de "ACME Lyon" :
      | téléphone | 03 88 11 22 33                                       |
      | liens     | https://www.welcometothejungle.com/fr/companies/acme |
    Alors ce n'est pas refusé

  Scénario: Un lien retiré d'une organisation est libre pour une autre
    Soit "ACME Lyon" fait partie de mes organisations avec le lien "https://www.welcometothejungle.com/fr/companies/acme"
    Et je retire de "ACME Lyon" le lien "https://www.welcometothejungle.com/fr/companies/acme"
    Quand j'ajoute à "ACME" le lien "https://www.welcometothejungle.com/fr/companies/acme"
    Alors "ACME" a la référence Welcome to the Jungle "acme"

  Scénario: Une organisation a cinq liens au plus
    Quand je change les coordonnées de "ACME" :
      | liens | https://a.test/acme https://b.test/acme https://c.test/acme https://d.test/acme https://e.test/acme https://f.test/acme |
    Alors c'est refusé : "There can be at most 5 links"

  Scénario: Un domaine ne désigne qu'une organisation du compte
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je change les coordonnées de "ACME" :
      | site web | https://acme.fr/blog |
    Alors c'est refusé : "ACME Lyon already has this website"

  Scénario: Une organisation garde son site en changeant ses coordonnées
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je change les coordonnées de "ACME Lyon" :
      | téléphone | 03 88 11 22 33  |
      | site web  | https://acme.fr |
    Alors ce n'est pas refusé

  Scénario: Un autre compte note le même lien et le même domaine
    Soit l'organisation "Globex" qui utilise Prospecter
    Et l'organisation "ACME Lyon" appartient à "Globex" avec :
      | liens    | https://www.linkedin.com/company/acme-corp |
      | site web | https://www.acme.fr                        |
    Quand je change les coordonnées de "ACME" :
      | liens    | https://www.linkedin.com/company/acme-corp |
      | site web | https://acme.fr                            |
    Alors l'organisation "ACME" est enregistrée avec :
      | liens    | https://www.linkedin.com/company/acme-corp |
      | site web | https://acme.fr                            |

  Scénario: Le site web est reconnu par son domaine
    Quand je change les coordonnées de "ACME" :
      | site web | HTTPS://www.Acme.fr/contact |
    Alors l'organisation "ACME" est enregistrée avec :
      | site web        | https://www.acme.fr |
      | domaine du site | acme.fr             |
