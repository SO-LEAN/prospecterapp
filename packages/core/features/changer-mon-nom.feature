# language: fr
Fonctionnalité: Changer mon nom
  En tant qu'utilisateur
  Je veux corriger mon prénom et mon nom
  Sans toucher au reste de mon profil

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Seul mon nom change
    Quand je change mon nom :
      | prénom | Ada  |
      | nom    | King |
    Alors mon compte est enregistré avec :
      | prénom | Ada                |
      | nom    | King               |
      | e-mail | ada@solean-it.test |

  Scénario: Mon profil garde le moment de sa dernière modification
    Soit nous sommes le "2026-10-20 14:00"
    Quand je change mon nom :
      | prénom | Ada |
    Alors mon compte est enregistré avec :
      | mon profil modifié le | 2026-10-20 14:00 |
