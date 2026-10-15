# language: fr
Fonctionnalité: Réviser l'identité de mon organisation
  En tant qu'utilisateur
  Je veux corriger la raison sociale et la forme juridique de mon organisation

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Ada                |
      | nom    | Lovelace           |
      | e-mail | ada@solean-it.test |

  Scénario: L'identité de mon organisation change
    Quand je révise l'identité de mon organisation :
      | raison sociale  | SO-LEAN |
      | forme juridique | SARL    |
    Alors mon compte est enregistré avec :
      | raison sociale de mon organisation  | SO-LEAN |
      | forme juridique de mon organisation | SARL    |

  Scénario: La raison sociale de mon organisation peut être effacée
    Quand je révise l'identité de mon organisation :
      | raison sociale | |
    Alors mon compte est enregistré avec :
      | raison sociale de mon organisation | |
    Quand je consulte mon compte
    Alors je vois mon compte :
      | raison sociale de mon organisation | |
