# language: fr
Fonctionnalité: Compléter une organisation sans rien écraser
  En tant que prospecteur
  Je veux qu'une IA complète ce qui manque à une organisation que Prospecter connaît déjà
  Afin de ne jamais perdre ce que j'ai saisi

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations

  Scénario: Ce qui est vide est complété
    Quand je complète l'organisation "ACME" :
      | SIRET     | 55210055400013                         |
      | liens     | https://www.linkedin.com/company/acme  |
      | site web  | https://www.acme.fr                    |
      | e-mail    | contact@acme.fr                        |
      | téléphone | 03 88 11 22 33                         |
    Alors ce n'est pas refusé
    Et l'organisation "ACME" est enregistrée avec :
      | SIRET     | 55210055400013                        |
      | liens     | https://www.linkedin.com/company/acme |
      | site web  | https://www.acme.fr                   |
      | e-mail    | contact@acme.fr                       |
      | téléphone | +33 3 88 11 22 33                     |

  Scénario: Ce qui est noté reste, un lien de plus s'ajoute aux autres
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | SIRET     | 35600000049837                          |
      | liens     | https://www.welcometothejungle.com/fr/companies/initech |
      | site web  | https://initech.fr                      |
      | e-mail    | contact@initech.fr                      |
      | téléphone | 03 88 00 00 00                          |
    Quand je complète l'organisation "Initech" :
      | SIRET     | 55210055400013                          |
      | liens     | https://www.linkedin.com/company/initech |
      | site web  | https://www.initech.com                 |
      | e-mail    | hello@initech.fr                        |
      | téléphone | 03 88 11 22 33                          |
    Alors l'organisation "Initech" est enregistrée avec :
      | SIRET     | 35600000049837                                                                                   |
      | liens     | https://www.welcometothejungle.com/fr/companies/initech https://www.linkedin.com/company/initech |
      | site web  | https://initech.fr                                                                               |
      | e-mail    | contact@initech.fr                                                                               |
      | téléphone | +33 3 88 00 00 00                                                                                |

  Scénario: Le SIRET d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | SIRET | 55210055400013 |
    Quand je complète l'organisation "ACME" :
      | SIRET | 55210055400013 |
    Alors c'est refusé : "SIRET already the one of ACME Lyon"

  Scénario: Un lien d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | liens | https://www.linkedin.com/company/acme |
    Quand je complète l'organisation "ACME" :
      | liens | https://www.linkedin.com/company/acme |
    Alors c'est refusé : "Link already the one of ACME Lyon"

  Scénario: Le site d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je complète l'organisation "ACME" :
      | site web | https://acme.fr/contact |
    Alors c'est refusé : "Website already the one of ACME Lyon"

  Scénario: L'adresse e-mail d'une autre organisation est refusée
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | e-mail | contact@acme.fr |
    Quand je complète l'organisation "ACME" :
      | e-mail | contact@acme.fr |
    Alors c'est refusé : "Email already used"

  Scénario: Rien à compléter, rien ne change
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | e-mail | contact@initech.fr |
    Quand je complète l'organisation "Initech" :
      | e-mail | hello@initech.fr |
    Alors ce n'est pas refusé
    Et aucun fait d'organisation n'est publié

  Scénario: L'organisation complétée est publiée
    Quand je complète l'organisation "ACME" :
      | e-mail | contact@acme.fr |
    Alors le fait publié de "ACME" est présente, non géolocalisée, révision 2

  Scénario: Au-delà de cinq liens, les autres ne sont pas notés
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | liens | https://a.test/initech https://b.test/initech https://c.test/initech https://d.test/initech |
    Quand je complète l'organisation "Initech" :
      | liens | https://a.test/initech https://e.test/initech https://f.test/initech |
    Alors ce n'est pas refusé
    Et l'organisation "Initech" est enregistrée avec :
      | liens | https://a.test/initech https://b.test/initech https://c.test/initech https://d.test/initech https://e.test/initech |

  Scénario: Avant de compléter, je sais ce qui serait complété et ce qui diffère
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | site web | https://initech.fr |
    Quand je compare l'organisation "Initech" à :
      | SIRET    | 55210055400013          |
      | site web | https://www.initech.com |
      | e-mail   | contact@initech.fr      |
    Alors serait complété : "SIRET, e-mail"
    Et diffère : "site web"
