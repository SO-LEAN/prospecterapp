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

  Scénario: Un profil déjà celui d'un autre contact est refusé
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Quand je change les coordonnées de "Grace Hopper" :
      | LinkedIn | linkedin.com/in/Ada-Lovelace/ |
    Alors c'est refusé : "LinkedIn profile already held by Ada Lovelace"

  Scénario: Sans le droit, je n'apprends pas qui a un profil
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je change les coordonnées de "Grace Hopper" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : "Only the person or a member allowed to manage personal data may change them"

  Scénario: J'enregistre un contact avec un profil déjà celui d'un autre contact
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | LinkedIn | https://fr.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : "LinkedIn profile already held by Ada Lovelace"

  Scénario: Un ancien profil reconnaît encore le contact
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Quand je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | https://www.linkedin.com/in/ada-l |
    Et je change les coordonnées de "Grace Hopper" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : "LinkedIn profile already held by Ada Lovelace"

  Scénario: Un contact reprend son ancien profil
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Quand je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | https://www.linkedin.com/in/ada-l |
    Et je change les coordonnées de "Ada Lovelace" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Effacer un contact libère ses profils, actuel et anciens
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-l"
    Et le contact "Grace Hopper" de "ACME"
    Et j'efface la fiche de "Ada Lovelace"
    Quand je change les coordonnées de "Grace Hopper" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors je vois la fiche de "Grace Hopper" avec :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Les doublons d'avant la règle ne bloquent rien
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et "Grace Hopper" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Quand je change les coordonnées de "Grace Hopper" :
      | téléphone | 03 88 00 00 42                           |
      | LinkedIn  | https://www.linkedin.com/in/ada-lovelace |
    Alors je vois la fiche de "Grace Hopper" avec :
      | téléphone | +33 3 88 00 00 42                        |
      | LinkedIn  | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Le profil LinkedIn d'un membre peut être celui d'un contact ou d'un collègue
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et "Grace" est membre de "SOLEAN IT" avec le profil :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Quand je change mes coordonnées :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors ce n'est pas refusé

  Scénario: Un autre compte peut noter le même profil
    Soit "Ada Lovelace" a le profil LinkedIn "https://www.linkedin.com/in/ada-lovelace"
    Et l'organisation "Initech" qui utilise Prospecter
    Et l'organisation "Globex" appartient à "Initech"
    Et le contact "Grace Hopper" de "Globex"
    Et je suis connecté en tant que prospecteur de "Initech"
    Quand je change les coordonnées de "Grace Hopper" :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
    Alors je vois la fiche de "Grace Hopper" avec :
      | LinkedIn | https://www.linkedin.com/in/ada-lovelace |
