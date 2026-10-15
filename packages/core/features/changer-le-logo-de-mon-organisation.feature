# language: fr
Fonctionnalité: Changer le logo de mon organisation
  En tant qu'utilisateur
  Je veux afficher le logo de mon organisation

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Le logo de mon organisation est enregistré dans le stockage
    Soit j'ai une image "solean.png" de 2048 octets
    Quand je change le logo de mon organisation avec "solean.png"
    Alors mon compte est enregistré avec :
      | logo de mon organisation | solean.png (png, 2048 octets) |
    Et le stockage contient "solean.png"
