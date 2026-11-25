# language: fr
@IdentifyOrganizations
Fonctionnalité: Identifier des organisations
  En tant que prospecteur, ou l'IA qui me sert
  Je veux savoir, pour chaque organisation telle que je la vois, si Prospecter la connaît déjà
  Afin de ne rien créer en double

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | SIRET | 55210055400013                             |
      | liens | https://www.linkedin.com/company/acme-corp |

  Scénario: Reconnue par son SIRET
    Quand j'identifie les organisations :
      | SIRET          |
      | 552 100 554 00013 |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | ACME       |

  Scénario: Reconnue par sa page LinkedIn
    Quand j'identifie les organisations :
      | liens                                       |
      | https://www.linkedin.com/company/acme-corp/ |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | ACME       |

  Scénario: Reconnue par le lien d'un outil que Prospecter ne connaît pas, quelle que soit sa saisie
    Soit "Initech" fait partie de mes organisations avec le lien "annuaire-xyz.com/fiche/initech-sas"
    Quand j'identifie les organisations :
      | liens                                     |
      | http://annuaire-xyz.com/fiche/INITECH-SAS/ |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | Initech    |

  Scénario: Reconnue par son site
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | site web | https://www.globex.fr |
    Quand j'identifie les organisations :
      | site web                 |
      | http://globex.fr/contact |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | Globex     |

  Scénario: Reconnue par son e-mail, quelle que soit sa casse
    Soit l'organisation "Hooli" d'e-mail "contact@hooli.test" appartient à "SOLEAN IT"
    Quand j'identifie les organisations :
      | e-mails            |
      | CONTACT@hooli.test |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | Hooli      |

  Scénario: Reconnue par le domaine de l'e-mail d'une personne qui y travaille
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | site web | https://www.globex.fr |
    Quand j'identifie les organisations :
      | e-mails              |
      | jean.dupont@globex.fr |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | Globex     |

  Scénario: Une messagerie publique ne désigne aucune organisation
    Soit l'organisation "Google" fait partie de mes organisations avec :
      | site web | https://www.gmail.com |
    Quand j'identifie les organisations :
      | e-mails               |
      | jean.dupont@gmail.com |
    Alors les organisations sont identifiées :
      | statut   | candidates |
      | nouvelle |            |

  Scénario: Des clés qui désignent deux organisations sont à confirmer, jamais connues
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | site web | https://www.globex.fr |
    Quand j'identifie les organisations :
      | SIRET          | site web         |
      | 55210055400013 | https://globex.fr |
    Alors les organisations sont identifiées :
      | statut      | candidates   |
      | à confirmer | ACME, Globex |

  Scénario: Plusieurs clés qui désignent la même organisation la font connaître
    Quand j'identifie les organisations :
      | SIRET          | liens                                      |
      | 55210055400013 | https://www.linkedin.com/company/acme-corp |
    Alors les organisations sont identifiées :
      | statut | candidates |
      | connue | ACME       |

  Scénario: Une organisation au nom proche, sa forme juridique à part, est à confirmer
    Quand j'identifie les organisations :
      | nom      |
      | Acme SAS |
    Alors les organisations sont identifiées :
      | statut      | candidates |
      | à confirmer | ACME       |

  Scénario: Celles du même nom et de la même ville viennent en tête des candidates
    Soit l'organisation "Acme" fait partie de mes organisations avec :
      | forme juridique | SARL |
      | ville           | Lyon |
    Et l'organisation "Acmé" fait partie de mes organisations avec :
      | forme juridique | SA    |
      | ville           | Paris |
    Quand j'identifie les organisations :
      | nom        | ville |
      | ACME s.a.s | lyon  |
    Alors les organisations sont identifiées :
      | statut      | candidates               |
      | à confirmer | Acme SARL, Acmé SA, ACME |

  Scénario: Un indice qui n'est pas une clé de son genre n'identifie rien, sans refuser la liste
    Quand j'identifie les organisations :
      | SIRET | liens                                                      | site web     | e-mails      |
      | 123   | pas un lien, https://www.linkedin.com/in/ada-lovelace      | nimportequoi | pas-un-email |
      |       | https://www.linkedin.com/company/acme-corp                 |              |              |
    Alors les organisations sont identifiées :
      | statut   | candidates |
      | nouvelle |            |
      | connue   | ACME       |

  Scénario: Une organisation d'un autre compte n'est pas reconnue
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent" avec :
      | SIRET | 35600000049837 |
    Quand j'identifie les organisations :
      | nom     | SIRET          |
      | Initech | 35600000049837 |
    Alors les organisations sont identifiées :
      | statut   | candidates |
      | nouvelle |            |

  Scénario: Une liste à la fois, chacune dans l'ordre donné
    Soit 30 organisations font partie de mes organisations
    Quand j'identifie les organisations :
      | nom             | SIRET          |
      | Organisation 07 |                |
      | Hooli           |                |
      |                 | 55210055400013 |
      | Organisation 23 |                |
    Alors les organisations sont identifiées :
      | statut      | candidates      |
      | à confirmer | Organisation 07 |
      | nouvelle    |                 |
      | connue      | ACME            |
      | à confirmer | Organisation 23 |

  Scénario: Une inconnue est nouvelle
    Quand j'identifie les organisations :
      | nom     | SIRET          |
      | Globex  | 35600000049837 |
      | Initech |                |
    Alors les organisations sont identifiées :
      | statut   | candidates |
      | nouvelle |            |
      | nouvelle |            |
