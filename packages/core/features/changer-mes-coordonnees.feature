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

  Scénario: L'e-mail ne peut pas être celui d'un collègue
    Soit "Grace" est membre de "SOLEAN IT" avec le profil :
      | e-mail | grace@solean-it.test |
    Quand je change mes coordonnées :
      | e-mail | grace@solean-it.test |
    Alors c'est refusé : "Email already used"
    Et mon compte est enregistré avec :
      | e-mail | ada@solean-it.test |

  Scénario: L'e-mail peut être celui d'une personne d'un autre compte
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et "Linus" est membre de "Concurrent" avec le profil :
      | e-mail | linus@concurrent.test |
    Quand je change mes coordonnées :
      | e-mail | linus@concurrent.test |
    Alors ce n'est pas refusé
