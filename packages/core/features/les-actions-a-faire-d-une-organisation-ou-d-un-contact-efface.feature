# language: fr
Fonctionnalité: Les actions à faire d'une organisation qu'on ne prospecte plus, ou d'un contact effacé
  En tant que prospecteur
  Je veux que les actions à faire partent avec l'organisation ou le contact qu'elles concernent
  Afin de ne pas garder de travail sans objet, ni les données personnelles que leur texte peut contenir

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Une organisation qu'on arrête de prospecter emporte ses actions à faire
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand j'arrête de prospecter "ACME"
    Alors elle n'existe plus

  Scénario: Une organisation qu'on arrête de prospecter emporte ses propres actions à faire
    Soit j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Quand j'arrête de prospecter "ACME"
    Alors elle n'existe plus

  Scénario: L'action à faire d'un contact reste pour son autre organisation
    Soit l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand j'arrête de prospecter "ACME"
    Alors elle est dans les actions à faire de "Groupe ACME"

  Scénario: Une action à faire déjà supprimée ne fait pas échouer la réaction
    Soit j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Et j'arrête de prospecter "ACME"
    Quand l'action à faire à effacer est publiée de nouveau
    Alors elle n'existe plus

  Scénario: Les actions à faire d'un contact effacé sont effacées
    Soit l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et "Ada Lovelace" est un contact de "Groupe ACME"
    Et j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Et j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Quand j'efface la fiche de "Ada Lovelace"
    Alors "Groupe ACME" n'a plus d'action à faire
    Et les actions à faire de "ACME" sont :
      | texte                   |
      | Préparer la proposition |
