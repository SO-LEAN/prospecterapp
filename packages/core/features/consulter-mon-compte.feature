# language: fr
Fonctionnalité: Consulter mon compte
  En tant qu'utilisateur
  Je veux voir mon profil et celui de mon organisation

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Je consulte mon compte
    Quand je consulte mon compte
    Alors je vois mon compte :
      | identifiant                        | prospector         |
      | prénom                             | Ada                |
      | nom                                | Lovelace           |
      | e-mail                             | ada@solean-it.test |
      | raison sociale de mon organisation | SOLEAN IT          |
