# language: fr
Fonctionnalité: Inscrire un membre
  En tant qu'administrateur d'un compte
  Je veux inscrire un collègue à mon compte, avec son propre profil, qu'il complète lui-même, et seulement les rôles dont il a besoin
  Afin qu'il prospecte sans voir ce qui ne le regarde pas

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est administrateur de "SOLEAN IT"

  Scénario: Un administrateur inscrit un prospecteur sans le droit aux données personnelles
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Alan" à mon compte :
      | rôles | prospecteur |
    Alors "Alan" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR"
    Et "Alan" n'a pas le droit aux données personnelles

  Scénario: Le profil du membre inscrit est recueilli, vide, pour qu'il le complète
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Alan" à mon compte :
      | rôles | prospecteur |
    Alors le profil de "Alan" est :
      | prénom |  |
      | nom    |  |
      | e-mail |  |

  Scénario: Un administrateur donne le droit aux données personnelles
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Ada" à mon compte :
      | rôles | prospecteur, données personnelles |
    Alors "Ada" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR, ROLE_PERSONAL_DATA"
    Et "Ada" a le droit aux données personnelles

  Scénario: Un administrateur inscrit un autre administrateur
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Grace" à mon compte :
      | rôles | prospecteur, administrateur |
    Alors "Grace" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR, ROLE_ADMIN"
    Et "Grace" a le droit aux données personnelles

  Scénario: Un membre inscrit a son propre profil, jamais celui d'un autre membre
    Soit je suis connecté en tant que "Michel"
    Et mon profil est :
      | prénom | Michel |
      | nom    | Maier  |
    Quand j'inscris "Alan" à mon compte :
      | rôles | prospecteur |
    Alors le profil de "Alan" n'est pas celui de "Michel"
    Et le profil de "Michel" est :
      | prénom | Michel |
      | nom    | Maier  |

  Scénario: Seul un administrateur inscrit un membre
    Soit "Grace" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Grace"
    Quand j'inscris "Alan" à mon compte :
      | rôles | prospecteur |
    Alors c'est refusé : "Only an administrator of the account may register a member"

  Scénario: Un membre a au moins un rôle de travail
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Alan" à mon compte :
      | rôles |  |
    Alors c'est refusé : "A member needs a role among prospector, personal data and administrator"
