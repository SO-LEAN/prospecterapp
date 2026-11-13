# language: fr
Fonctionnalité: Modifier une action à faire
  En tant que prospecteur
  Je veux changer le texte ou l'échéance d'une action à faire
  Afin qu'elle dise ce qui reste à faire, et pour quand

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026

  Scénario: Je change le texte et l'échéance
    Quand je la modifie en "Préparer la proposition chiffrée" pour le 21/11/2026 à 9h00
    Alors les actions à faire de "ACME" sont :
      | échéance         | texte                            | faite |
      | 21/11/2026 09:00 | Préparer la proposition chiffrée | non   |

  Scénario: Une action modifiée garde un texte
    Quand je la modifie en "" pour le 21/11/2026
    Alors c'est refusé : "A to-do has a text"
