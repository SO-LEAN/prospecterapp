# language: fr
Fonctionnalité: Noter une action à faire
  En tant que prospecteur
  Je veux noter une action à faire pour un contact ou une organisation, avec une échéance
  Afin que l'application me souvienne du travail à préparer, sans le contact en face

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Une action à faire apparaît sur la fiche du contact
    Quand je note à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Alors les actions à faire de "Ada Lovelace" sont :
      | échéance   | texte          | faite |
      | 14/11/2026 | Envoyer mon CV | non   |

  Scénario: Une action à faire peut avoir une heure
    Quand je note à faire pour "Ada Lovelace" "Rappeler avant sa réunion" pour le 14/11/2026 à 8h30
    Alors son échéance est le 14/11/2026 à 8h30

  Scénario: Une action à faire a un texte
    Quand je note à faire pour "Ada Lovelace" "" pour le 14/11/2026
    Alors c'est refusé : "A to-do has a text"

  Scénario: Une action à faire pour une organisation
    Quand je note à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Alors elle est dans les actions à faire de "ACME"

  Scénario: L'action à faire d'un contact est gardée pour ses organisations
    Soit l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Quand je note à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Alors elle est dans les actions à faire de "ACME"
    Et elle est dans les actions à faire de "Groupe ACME"

  Scénario: Une action à faire est à moi
    Soit "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Michel" a noté à faire pour "Ada Lovelace" "Appeler" pour le 14/11/2026
    Quand je consulte mes actions à faire
    Alors elle n'y est pas
    Mais elle est sur la fiche de "Ada Lovelace", au nom de "Michel"

  Scénario: Mes actions à faire, la plus proche d'abord
    Soit j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand je consulte mes actions à faire
    Alors mes actions à faire sont :
      | échéance   | texte                   |
      | 14/11/2026 | Envoyer mon CV          |
      | 20/11/2026 | Préparer la proposition |

  Scénario: Les actions à faire se lisent la plus proche d'abord, sans heure avant une heure, les faites ensuite
    Soit j'ai noté à faire pour "Ada Lovelace" "Relancer pour le devis" pour le 20/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Rappeler avant sa réunion" pour le 14/11/2026 à 8h30
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Préparer la démo" pour le 15/11/2026
    Et j'ai coché "Préparer la démo"
    Alors les actions à faire de "Ada Lovelace" sont :
      | échéance         | texte                     | faite |
      | 14/11/2026       | Envoyer mon CV            | non   |
      | 14/11/2026 08:30 | Rappeler avant sa réunion | non   |
      | 20/11/2026       | Relancer pour le devis    | non   |
      | 15/11/2026       | Préparer la démo          | oui   |

  Scénario: Les actions faites se lisent la dernière faite d'abord
    Soit j'ai noté à faire pour "Ada Lovelace" "Préparer la démo" pour le 14/11/2026
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 15/11/2026
    Et j'ai coché "Préparer la démo"
    Et nous sommes le 13/11/2026 à 11h00
    Et j'ai coché "Envoyer mon CV"
    Alors les actions à faire de "Ada Lovelace" sont :
      | texte            | faite |
      | Envoyer mon CV   | oui   |
      | Préparer la démo | oui   |
