# language: fr
Fonctionnalité: Changer mon identifiant
  En tant qu'utilisateur
  Je veux choisir l'identifiant avec lequel on me reconnaît

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Mon identifiant change
    Quand je change mon identifiant en "ada"
    Alors mon compte est enregistré avec :
      | identifiant | ada                |
      | e-mail      | ada@solean-it.test |

  Scénario: Un identifiant est obligatoire
    Quand je change mon identifiant en ""
    Alors c'est refusé : "A user name is required"
