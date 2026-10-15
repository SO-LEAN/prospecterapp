# language: fr
Fonctionnalité: Mon compte
  En tant qu'utilisateur
  Je veux consulter et tenir à jour mon profil et celui de mon organisation
  Afin que mes prospects et mes collègues aient les bonnes informations

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                  |
      | nom    | Lovelace             |
      | e-mail | ada@solean-it.test   |

  Scénario: Je consulte mon compte
    Quand je consulte mon compte
    Alors je vois mon compte :
      | identifiant                        | prospector         |
      | prénom                             | Ada                |
      | nom                                | Lovelace           |
      | e-mail                             | ada@solean-it.test |
      | raison sociale de mon organisation | SOLEAN IT          |

  Scénario: Je mets à jour mon profil et mon organisation
    Quand je mets à jour mon compte avec :
      | identifiant                         | ada                 |
      | prénom                              | Ada                 |
      | nom                                 | King                |
      | e-mail                              | ada@solean-it.test  |
      | téléphone                           | 06 00 00 00 00      |
      | langue                              | FR                  |
      | raison sociale de mon organisation  | SO-LEAN             |
      | forme juridique de mon organisation | SARL                |
    Alors mon compte est enregistré avec :
      | identifiant                         | ada                |
      | nom                                 | King               |
      | téléphone                           | 06 00 00 00 00     |
      | langue                              | FR                 |
      | raison sociale de mon organisation  | SO-LEAN            |
      | forme juridique de mon organisation | SARL               |
      | langue de mon organisation          | FR                 |
    Et je suis informé que "User account information updated !"

  Scénario: Ma photo et le logo de mon organisation sont enregistrés
    Soit j'ai une image "ada.jpg" de 4096 octets
    Et j'ai une image "solean.png" de 2048 octets
    Quand je mets à jour mon compte avec :
      | prénom                             | Ada        |
      | raison sociale de mon organisation | SOLEAN IT  |
      | photo                              | ada.jpg    |
      | logo de mon organisation           | solean.png |
    Alors mon compte est enregistré avec :
      | photo                    | ada.jpg (jpg, 4096 octets)    |
      | logo de mon organisation | solean.png (png, 2048 octets) |
    Et le stockage contient "ada.jpg"
    Et le stockage contient "solean.png"

  Scénario: Un e-mail déjà utilisé est refusé et rien n'est modifié
    Soit l'organisation "Globex" a l'adresse e-mail "contact@globex.test"
    Quand je mets à jour mon compte avec :
      | prénom                             | Grace               |
      | e-mail                             | contact@globex.test |
      | raison sociale de mon organisation | SO-LEAN             |
    Alors c'est refusé : "Email already used"
    Et mon compte est enregistré avec :
      | prénom                             | Ada                |
      | e-mail                             | ada@solean-it.test |
      | raison sociale de mon organisation | SOLEAN IT          |

  Scénario: La raison sociale de mon organisation peut être effacée
    Quand je mets à jour mon compte avec :
      | prénom | Ada |
    Alors mon compte est enregistré avec :
      | raison sociale de mon organisation | |
    Quand je consulte mon compte
    Alors je vois mon compte :
      | raison sociale de mon organisation | |
