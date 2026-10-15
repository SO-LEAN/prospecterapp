# language: fr
Fonctionnalité: Rechercher mes contacts
  En tant que prospecteur
  Je veux retrouver les personnes que mon compte prospecte
  Afin de les rattacher aux organisations et de les contacter

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le contact "Ada Lovelace" de "SOLEAN IT" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "SOLEAN IT" a l'adresse e-mail "grace@navy.test"
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et le contact "Linus Torvalds" de "Concurrent" a l'adresse e-mail "linus@kernel.test"

  Scénario: Je liste les contacts de mon compte, les plus récents d'abord
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je liste mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom      | masquée |
      | Grace  | Hopper   | non     |
      | Ada    | Lovelace | non     |
    Et je peux sur mes contacts :
      | enregistrer un contact |
      | chercher par texte     |

  Scénario: Je cherche dans le nom ou l'e-mail
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "navy" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |

  Scénario: Sans le droit, je vois mes contacts masqués
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je liste mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    | masquée |
      | G***e  | H***r  | oui     |
      | A***a  | L***e  | oui     |
    Et je ne peux rien faire sur mes contacts

  Scénario: Sans le droit, je ne peux pas chercher dans ce qui m'est masqué
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je cherche "navy" dans mes contacts
    Alors c'est refusé : "Only a member allowed to manage personal data may search persons by text"
