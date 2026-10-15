# language: fr
Fonctionnalité: Modifier une organisation
  En tant que prospecteur
  Je veux mettre à jour la fiche d'une organisation
  Afin qu'elle reste juste
  La fiche envoyée remplace entièrement l'ancienne : ce qui n'est pas renseigné est effacé.

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | forme juridique | SAS               |
      | e-mail          | contact@acme.test |
      | ville           | Strasbourg        |
      | logo            | acme.png          |

  Scénario: La fiche envoyée remplace l'ancienne
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME Corp |
      | téléphone      | 0388000000 |
    Alors l'organisation "ACME Corp" est enregistrée avec :
      | téléphone       | 0388000000 |
      | forme juridique |            |
      | e-mail          |            |
      | ville           |            |
      | position        | aucune     |
    Et je suis informé que "Organization updated !"

  Scénario: Une nouvelle adresse est géolocalisée
    Soit le géocodeur situe "Colmar" en 7.36, 48.08
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME   |
      | ville          | Colmar |
    Alors l'organisation "ACME" est enregistrée avec :
      | ville    | Colmar     |
      | position | 7.36, 48.08 |

  Scénario: Le logo est conservé tant qu'aucun nouveau n'est envoyé
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | logo | acme.png (png, 1024 octets) |

  Scénario: Un nouveau logo remplace l'ancien
    Soit j'ai une image "acme-2026.webp" de 512 octets
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME           |
      | logo           | acme-2026.webp |
    Alors l'organisation "ACME" est enregistrée avec :
      | logo | acme-2026.webp (webp, 512 octets) |
    Et le stockage contient "acme-2026.webp"

  Scénario: La holding est rattachée, puis retirée si elle n'est plus renseignée
    Soit l'organisation "Groupe ACME" fait partie de mes organisations
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME        |
      | holding        | Groupe ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | Groupe ACME |
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME |
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | |

  Scénario: La holding doit exister
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME     |
      | holding        | Inconnue |
    Alors c'est introuvable

  Scénario: Il faut au moins une raison sociale ou un e-mail
    Quand je modifie l'organisation "ACME" avec :
      | ville | Strasbourg |
    Alors c'est refusé : "At least one is mandatory : corporate name or email"

  Scénario: L'e-mail ne peut pas être celui d'une autre personne
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | e-mail | contact@globex.test |
    Quand je modifie l'organisation "ACME" avec :
      | raison sociale | ACME                |
      | e-mail         | contact@globex.test |
    Alors c'est refusé : "Email already used"
