# language: fr
Fonctionnalité: Corriger le lieu d'un rendez-vous ou le lien d'une visio
  En tant que prospecteur
  Je veux ajouter ou corriger le lieu d'un rendez-vous ou le lien d'une visio après l'avoir planifié
  Afin de rejoindre l'échange ou de m'y rendre sans chercher l'information ailleurs

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et nous sommes le 13/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le lien d'une visio s'ajoute une fois planifiée
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je corrige le lien de cet échange en "https://meet.google.com/abc-defg-hij"
    Alors le détail de la visio propose de la rejoindre à "https://meet.google.com/abc-defg-hij"

  Scénario: Le lieu d'un rendez-vous se corrige
    Soit j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00 au lieu "Café de Flore"
    Quand je corrige le lieu de cet échange en "Les Deux Magots"
    Alors le détail du rendez-vous propose l'itinéraire vers "Les Deux Magots"

  Scénario: Le lien corrigé reste une adresse web
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je corrige le lien de cet échange en "réunion de mardi"
    Alors c'est refusé : "A video call link is a web address"

  Scénario: Seuls un rendez-vous et une visio ont un lieu ou un lien à corriger
    Soit j'ai planifié un appel avec "Ada Lovelace" le 17/11/2026 à 14h00
    Quand je consulte cet échange
    Alors je peux sur l'échange :
      | corriger le texte        |
      | supprimer l'échange      |
      | joindre un fichier       |
      | retirer une pièce jointe |
      | déplacer                 |
      | annuler                  |
    Quand je corrige le lieu de cet échange en "Café de Flore"
    Alors c'est refusé : "Only a meeting or a video call takes place somewhere"

  Scénario: Un collègue sans le droit aux données personnelles corrige le lien aussi
    Soit j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je corrige le lien de cet échange en "https://meet.google.com/abc-defg-hij"
    Alors le détail de la visio propose de la rejoindre à "https://meet.google.com/abc-defg-hij"

  Scénario: Une correction entière d'un rendez-vous fait garde son lieu
    Soit "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Michel" a noté le 12/11/2026 à 10h00 un rendez-vous envoyé à "Ada Lovelace" à 9h00 : "Café"
    Et je suis connecté en tant que "Michel"
    Quand je corrige le lieu de cet échange en "Café de Flore"
    Et je corrige cet échange :
      | texte | Café, puis déjeuner |
    Alors le détail du rendez-vous propose l'itinéraire vers "Café de Flore"
