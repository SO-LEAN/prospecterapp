# language: fr
@SearchContacts
Fonctionnalité: Rechercher mes contacts
  En tant que prospecteur
  Je veux retrouver les personnes que mon compte prospecte
  Afin de les rattacher à d'autres organisations et de les contacter

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@navy.test"
    Et le contact "Alan Turing" de "ACME"
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech" a l'adresse e-mail "linus@kernel.test"

  Scénario: Je liste les contacts de mon compte, les plus récents d'abord
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je liste mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom      | masquée |
      | Alan   | Turing   | non     |
      | Grace  | Hopper   | non     |
      | Ada    | Lovelace | non     |
    Et je peux sur mes contacts :
      | chercher par texte     |
      | enregistrer un contact |

  Scénario: Je cherche par le début du nom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "HOP" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |

  Scénario: Je cherche par le début du prénom, que le contact ait ou non un e-mail
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "Ala" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Alan   | Turing |

  Scénario: Un contact sans e-mail se retrouve toujours par le début de son nom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "Tur" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Alan   | Turing |

  Scénario: Je cherche par le début de l'e-mail
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "grace@navy" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |

  Scénario: Le prénom suivi du nom ne trouve pas un contact, seul le début du prénom ou du nom le trouve
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je cherche "grace hop" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom |

  Scénario: Sans le droit, je vois le nom de mes contacts, leurs coordonnées masquées
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je liste mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom      | masquée |
      | Alan   | Turing   | oui     |
      | Grace  | Hopper   | oui     |
      | Ada    | Lovelace | oui     |
    Et je peux sur mes contacts :
      | chercher par texte |

  Scénario: Sans le droit, je cherche par le début du nom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je cherche "hop" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |

  Scénario: Sans le droit, je cherche par le début du prénom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je cherche "ala" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Alan   | Turing |

  Scénario: Sans le droit, je ne cherche pas dans les e-mails, qui me sont masqués
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je cherche "grace@navy" dans mes contacts
    Alors la liste des contacts affiche :
      | prénom | nom |
