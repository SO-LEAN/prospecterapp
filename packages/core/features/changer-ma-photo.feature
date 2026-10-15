# language: fr
Fonctionnalité: Changer ma photo
  En tant qu'utilisateur
  Je veux une photo de profil

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: Ma photo est enregistrée dans le stockage
    Soit j'ai une image "ada.jpg" de 4096 octets
    Quand je change ma photo avec "ada.jpg"
    Alors mon compte est enregistré avec :
      | photo | ada.jpg (jpg, 4096 octets) |
    Et le stockage contient "ada.jpg"
