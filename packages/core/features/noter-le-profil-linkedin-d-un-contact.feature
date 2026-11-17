# language: fr
Fonctionnalité: Noter le profil LinkedIn d'un contact
  En tant que prospecteur
  Je veux noter l'adresse du profil LinkedIn d'un contact
  Afin de l'ouvrir d'un clic depuis sa fiche

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Je note le profil LinkedIn d'un contact
    Quand je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace/ |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Le profil est gardé sous une seule forme, quelle que soit sa saisie
    Quand je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | linkedin.com/in/ada-lovelace?utm_source=share |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Une adresse qui n'est pas un profil LinkedIn est refusée
    Quand je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | https://example.test/ada |
    Alors c'est refusé : "LinkedIn profile "https://example.test/ada" is not valid"

  Scénario: J'enregistre un contact avec son profil LinkedIn
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | LinkedIn | https://www.linkedin.com/in/grace-hopper |
    Alors je vois la fiche de "Grace Hopper" avec :
      | LinkedIn | https://www.linkedin.com/in/grace-hopper |

  Scénario: Sans le droit, le profil LinkedIn est masqué
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la fiche de "Ada Lovelace"
    Alors son profil LinkedIn est masqué
