# language: fr
Fonctionnalité: Commencer à prospecter une organisation
  En tant que prospecteur
  Je veux enregistrer une organisation que je commence à prospecter
  Afin de la suivre et de la situer sur une carte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: L'organisation prospectée appartient à mon organisation
    Quand je commence à prospecter l'organisation :
      | raison sociale  | ACME               |
      | forme juridique | SAS                |
      | type            | Éditeur            |
      | e-mail          | contact@acme.test  |
      | téléphone       | 03 88 00 00 00     |
      | langue          | FR                 |
      | observations    | Rencontré au salon |
    Alors l'organisation "ACME" est enregistrée avec :
      | forme juridique | SAS                |
      | type            | Éditeur            |
      | e-mail          | contact@acme.test  |
      | téléphone       | +33 3 88 00 00 00  |
      | langue          | FR                 |
      | observations    | Rencontré au salon |
      | compte          | SOLEAN IT          |
      | saisie par      | moi                |
    Et l'organisation "SOLEAN IT" compte 1 organisation active

  Scénario: Une raison sociale suffit
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | e-mail | |

  Scénario: Un e-mail suffit
    Quand je commence à prospecter l'organisation :
      | e-mail | contact@acme.test |
    Et je liste mes organisations
    Alors je trouve 1 organisation

  Scénario: Il faut au moins une raison sociale ou un e-mail
    Quand je commence à prospecter l'organisation :
      | ville | Strasbourg |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"
    Et l'organisation "SOLEAN IT" compte 0 organisation active

  Scénario: L'e-mail ne peut pas être celui d'une autre organisation de ma base
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                |
      | e-mail         | contact@globex.test |
    Alors c'est refusé : "Email already used"

  Scénario: L'e-mail peut être celui d'une organisation prospectée par un autre compte
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Globex" appartient à "Concurrent" avec :
      | e-mail | contact@globex.test |
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                |
      | e-mail         | contact@globex.test |
    Alors ce n'est pas refusé

  Scénario: L'organisation est enregistrée avec ses liens et son site
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                                                                                     |
      | liens          | http://fr.linkedin.com/company/ACME-Corp/about/ https://www.annuaire-xyz.com/fiche/acme |
      | site web       | HTTPS://www.Acme.fr/contact                                                              |
    Alors "ACME" a la référence LinkedIn entreprise "acme-corp"
    Et "ACME" a la référence de source "annuaire-xyz.com" et d'identifiant "annuaire-xyz.com/fiche/acme"
    Et l'organisation "ACME" est enregistrée avec :
      | site web        | https://www.acme.fr |
      | domaine du site | acme.fr             |

  Scénario: Le lien d'une autre organisation de ma base est refusé, en la nommant
    Soit "ACME Lyon" fait partie de mes organisations avec le lien "https://www.linkedin.com/company/acme-corp"
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                            |
      | liens          | linkedin.com/company/acme-corp/ |
    Alors c'est refusé : "Link already the one of ACME Lyon"
    Et l'organisation "SOLEAN IT" compte 1 organisation active

  Scénario: Le domaine du site d'une autre organisation de ma base est refusé, en la nommant
    Soit l'organisation "ACME Lyon" fait partie de mes organisations avec :
      | site web | https://www.acme.fr |
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                 |
      | site web       | https://acme.fr/blog |
    Alors c'est refusé : "Website already the one of ACME Lyon"
    Et l'organisation "SOLEAN IT" compte 1 organisation active

  Scénario: Le lien d'une personne est refusé
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME                                     |
      | liens          | https://www.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : "A page of linkedInProfile designates a person"
    Et l'organisation "SOLEAN IT" compte 0 organisation active

  Scénario: L'organisation peut faire partie d'une holding que je prospecte
    Soit l'organisation "Groupe ACME" fait partie de mes organisations
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME        |
      | holding        | Groupe ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | Groupe ACME |

  Scénario: La holding doit exister
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME     |
      | holding        | Inconnue |
    Alors c'est introuvable
    Et l'organisation "SOLEAN IT" compte 0 organisation active

  Scénario: La holding doit être une de mes organisations
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME    |
      | holding        | Initech |
    Alors c'est introuvable

  Scénario: Une adresse connue du géocodeur place l'organisation sur la carte
    Soit le géocodeur situe "Strasbourg" en 7.75, 48.58
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME               |
      | rue            | 1 place de la Gare |
      | code postal    | 67000              |
      | ville          | Strasbourg         |
      | pays           | FR                 |
    Alors l'organisation "ACME" est enregistrée avec :
      | rue         | 1 place de la Gare |
      | code postal | 67000              |
      | ville       | Strasbourg         |
      | pays        | FR                 |
      | position    | 7.75, 48.58        |

  Scénario: Une adresse inconnue du géocodeur est gardée sans position
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Nulle-part |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    | Nulle-part |
      | position | aucune     |

  Scénario: Sans adresse, pas de position
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    |        |
      | position | aucune |

  Scénario: L'organisation garde le moment où je l'ai enregistrée
    Soit nous sommes le "2026-10-15 10:30"
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | créée le    | 2026-10-15 10:30 |
      | modifiée le | 2026-10-15 10:30 |
