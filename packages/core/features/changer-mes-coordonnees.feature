# language: fr
Fonctionnalité: Changer mes coordonnées
  En tant qu'utilisateur
  Je veux tenir à jour mon e-mail, mon téléphone et ma langue

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Seules mes coordonnées changent
    Quand je change mes coordonnées :
      | e-mail    | ada@lovelace.test |
      | téléphone | 06 00 00 00 00    |
      | langue    | EN                |
    Alors mon compte est enregistré avec :
      | e-mail                     | ada@lovelace.test |
      | téléphone                  | 06 00 00 00 00    |
      | langue                     | EN                |
      | nom                        | Lovelace          |
      | langue de mon organisation |                   |

  Scénario: L'e-mail ne peut pas être celui d'une organisation ou d'un autre utilisateur
    Soit l'organisation "Globex" a l'adresse e-mail "contact@globex.test"
    Quand je change mes coordonnées :
      | e-mail | contact@globex.test |
    Alors c'est refusé : "Email already used"
    Et mon compte est enregistré avec :
      | e-mail | ada@solean-it.test |
