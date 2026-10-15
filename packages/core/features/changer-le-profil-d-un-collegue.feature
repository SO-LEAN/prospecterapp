# language: fr
Fonctionnalité: Le profil d'un collègue n'appartient qu'à lui
  En tant que membre d'un compte
  Je ne change pas le nom, les coordonnées ou la photo d'un collègue
  Afin que chacun reste maître de son profil

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Grace" est membre de "SOLEAN IT" avec le profil :
      | prénom | Grace  |
      | nom    | Hopper |

  Scénario: Même avec le droit aux données personnelles, le profil d'un collègue est refusé
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je change le nom de "Grace" :
      | prénom | Amazing |
      | nom    | Grace   |
    Alors c'est refusé : "Only the person or a member allowed to manage personal data may change them"

  Scénario: Le profil d'une personne d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et "Linus" est membre de "Concurrent" avec le profil :
      | prénom | Linus |
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je change le nom de "Linus" :
      | prénom | Linux |
    Alors c'est introuvable
