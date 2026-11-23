# language: fr
Fonctionnalité: Compléter une organisation sans rien écraser
  En tant que prospecteur
  Je veux qu'une IA complète ce qui manque à une organisation que Prospecter connaît déjà
  Afin de ne jamais perdre ce que j'ai saisi

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations

  Plan du scénario: Ce qui est vide est complété
    Quand je complète l'organisation "ACME" :
      | <champ> | <donné> |
    Alors ce n'est pas refusé
    Et l'organisation "ACME" est enregistrée avec :
      | <champ> | <noté> |

    Exemples:
      | champ     | donné                                 | noté                                  |
      | SIRET     | 55210055400013                        | 55210055400013                        |
      | liens     | https://www.linkedin.com/company/acme | https://www.linkedin.com/company/acme |
      | site web  | https://www.acme.fr                   | https://www.acme.fr                   |
      | e-mail    | contact@acme.fr                       | contact@acme.fr                       |
      | téléphone | 03 88 11 22 33                        | +33 3 88 11 22 33                     |

  Plan du scénario: Ce qui est noté reste
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | <champ> | <noté> |
    Quand je complète l'organisation "Initech" :
      | <champ> | <donné> |
    Alors l'organisation "Initech" est enregistrée avec :
      | <champ> | <gardé> |

    Exemples:
      | champ     | noté               | donné                   | gardé              |
      | SIRET     | 35600000049837     | 55210055400013          | 35600000049837     |
      | site web  | https://initech.fr | https://www.initech.com | https://initech.fr |
      | e-mail    | contact@initech.fr | hello@initech.fr        | contact@initech.fr |
      | téléphone | 03 88 00 00 00     | 03 88 11 22 33          | +33 3 88 00 00 00  |

  Scénario: Un lien de plus s'ajoute à ceux notés
    Soit l'organisation "Initech" fait partie de mes organisations avec :
      | liens | https://www.welcometothejungle.com/fr/companies/initech |
    Quand je complète l'organisation "Initech" :
      | liens | https://www.linkedin.com/company/initech |
    Alors l'organisation "Initech" est enregistrée avec :
      | liens | https://www.welcometothejungle.com/fr/companies/initech https://www.linkedin.com/company/initech |

  Scénario: Le SIRET d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | SIRET | 55210055400013 |
    Quand je complète l'organisation "ACME" :
      | SIRET | 55210055400013 |
    Alors c'est refusé : "ACME Lyon already has this SIRET"

  Scénario: Un lien d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | liens | https://www.linkedin.com/company/acme |
    Quand je complète l'organisation "ACME" :
      | liens | https://www.linkedin.com/company/acme |
    Alors c'est refusé : "ACME Lyon already has this link"

  Scénario: Le site d'une autre organisation est refusé
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je complète l'organisation "ACME" :
      | site web | https://acme.fr/contact |
    Alors c'est refusé : "ACME Lyon already has this website"

  Scénario: L'adresse e-mail d'une autre organisation est refusée
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | e-mail | contact@acme.fr |
    Quand je complète l'organisation "ACME" :
      | e-mail | contact@acme.fr |
    Alors c'est refusé : "This e-mail address is already in use"

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
