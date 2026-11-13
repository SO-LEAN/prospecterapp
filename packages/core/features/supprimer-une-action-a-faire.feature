# language: fr
Fonctionnalité: Supprimer une action à faire
  En tant que prospecteur
  Je veux supprimer une action à faire notée par erreur, ou devenue sans objet
  Afin qu'elle ne soit plus nulle part

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Je supprime une action à faire
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer mon CV" pour le 14/11/2026
    Quand je la supprime
    Alors "Ada Lovelace" n'a plus d'action à faire
    Et "ACME" n'a plus d'action à faire

  Scénario: Une action à faire supprimée est introuvable
    Soit j'ai noté à faire pour "ACME" "Préparer la proposition" pour le 20/11/2026
    Et je la supprime
    Quand je la consulte
    Alors c'est introuvable
