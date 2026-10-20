# language: fr
Fonctionnalité: Inscrire un membre
  En tant qu'administrateur d'un compte
  Je veux inscrire un collègue à mon compte, avec son propre profil et seulement les rôles dont il a besoin
  Afin qu'il prospecte sans voir ce qui ne le regarde pas

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est administrateur de "SOLEAN IT"

  Scénario: Un administrateur inscrit un prospecteur sans le droit aux données personnelles
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Alan" à mon compte :
      | prénom | Alan                |
      | nom    | Turing              |
      | e-mail | alan@solean-it.test |
      | rôles  | prospecteur         |
    Alors "Alan" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR"
    Et "Alan" n'a pas le droit aux données personnelles
    Et le profil de "Alan" est :
      | prénom | Alan                |
      | nom    | Turing              |
      | e-mail | alan@solean-it.test |

  Scénario: Un administrateur donne le droit aux données personnelles
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Ada" à mon compte :
      | prénom | Ada                               |
      | rôles  | prospecteur, données personnelles |
    Alors "Ada" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR, ROLE_PERSONAL_DATA"
    Et "Ada" a le droit aux données personnelles

  Scénario: Un administrateur inscrit un autre administrateur
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Grace" à mon compte :
      | prénom | Grace                       |
      | rôles  | prospecteur, administrateur |
    Alors "Grace" est membre de "SOLEAN IT" avec les rôles "ROLE_USER, ROLE_PROSPECTOR, ROLE_ADMIN"
    Et "Grace" a le droit aux données personnelles

  Scénario: Un membre inscrit a son propre profil, jamais celui d'un autre membre
    Soit je suis connecté en tant que "Michel"
    Et mon profil est :
      | prénom | Michel |
      | nom    | Maier  |
    Quand j'inscris "Alan" à mon compte :
      | prénom | Alan        |
      | nom    | Turing      |
      | rôles  | prospecteur |
    Alors le profil de "Alan" n'est pas celui de "Michel"
    Et le profil de "Michel" est :
      | prénom | Michel |
      | nom    | Maier  |

  Scénario: Seul un administrateur inscrit un membre
    Soit "Grace" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Grace"
    Quand j'inscris "Alan" à mon compte :
      | prénom | Alan        |
      | rôles  | prospecteur |
    Alors c'est refusé : "Only an administrator of the account may register a member"

  Scénario: Un membre a au moins un rôle de travail
    Soit je suis connecté en tant que "Michel"
    Quand j'inscris "Alan" à mon compte :
      | prénom | Alan |
      | rôles  |      |
    Alors c'est refusé : "A member needs a role among prospector, personal data and administrator"
