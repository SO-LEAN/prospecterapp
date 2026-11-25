# language: fr
@ChangeCivility
Fonctionnalité: Noter la civilité d'un contact
  En tant que prospecteur
  Je veux noter si je m'adresse à Madame ou à Monsieur
  Afin de le savoir d'un coup d'œil, quel que soit le prénom

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Camille Martin" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Un contact n'a pas de civilité tant qu'on ne l'a pas notée
    Quand je consulte la fiche de "Camille Martin"
    Alors sa civilité n'est pas renseignée

  Scénario: J'enregistre un contact avec sa civilité
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | Civilité | Madame |
    Alors je vois la fiche de "Grace Hopper" avec :
      | Civilité | Madame |

  Scénario: Je note la civilité d'un contact
    Quand je change la civilité de "Camille Martin" en "Monsieur"
    Alors je vois la fiche de "Camille Martin" avec :
      | Civilité | Monsieur |

  Scénario: Je retire la civilité d'un contact
    Soit "Camille Martin" a la civilité "Monsieur"
    Quand je retire la civilité de "Camille Martin"
    Et je consulte la fiche de "Camille Martin"
    Alors sa civilité n'est pas renseignée

  Scénario: Une civilité inconnue est refusée
    Quand je change la civilité de "Camille Martin" en "Docteur"
    Alors c'est refusé : "Title "Docteur" is not valid"

  Scénario: Sans le droit, je lis la civilité en clair, comme le nom
    Soit "Camille Martin" a la civilité "Madame"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la fiche de "Camille Martin"
    Alors je vois la fiche :
      | Civilité | Madame |
      | masquée  | oui    |

  Scénario: Sans le droit, je ne change pas la civilité
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je change la civilité de "Camille Martin" en "Madame"
    Alors c'est refusé : "Only a member allowed to manage personal data can change a contact's title"

  Scénario: La civilité est celle d'un contact, pas du profil d'un membre
    Quand je consulte la fiche de "moi"
    Alors je ne peux pas sur sa fiche "changer la civilité"
