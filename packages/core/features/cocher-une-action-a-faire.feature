# language: fr
Fonctionnalité: Cocher une action à faire
  En tant que prospecteur
  Je veux cocher une action à faire une fois faite, ou la décocher
  Afin de ne garder devant moi que ce qui reste à faire

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026

  Scénario: Une action faite est cochée
    Quand je la coche
    Alors elle est faite, le 13/11/2026 à 10h00

  Scénario: Une action faite n'est plus dans mes actions à faire
    Quand je la coche
    Et je consulte mes actions à faire
    Alors elle n'y est pas

  Scénario: Une action cochée par erreur est décochée
    Soit je l'ai cochée
    Quand je la décoche
    Alors les actions à faire de "Ada Lovelace" sont :
      | texte          | faite |
      | Envoyer mon CV | non   |

  Scénario: Une action faite ne se coche pas deux fois
    Soit je l'ai cochée
    Quand je la coche
    Alors c'est refusé : "A to-do done is not ticked off again"

  Scénario: Une action à faire ne se décoche pas
    Quand je la décoche
    Alors c'est refusé : "Only a to-do done is unticked"
