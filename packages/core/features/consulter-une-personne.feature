# language: fr
Fonctionnalité: Consulter les données personnelles d'une personne
  En tant que membre d'un compte
  Je veux voir les personnes de mon compte
  Sans que leurs données personnelles soient lues par qui n'en a pas le droit

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Grace" est membre de "SOLEAN IT" avec le profil :
      | prénom    | Grace                |
      | nom       | Hopper               |
      | e-mail    | grace@solean-it.test |
      | téléphone | 06 12 34 56 78       |

  Scénario: Avec le droit aux données personnelles, je lis un collègue en clair
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je consulte la fiche de "Grace"
    Alors je vois la fiche :
      | prénom    | Grace                |
      | nom       | Hopper               |
      | e-mail    | grace@solean-it.test |
      | téléphone | +33 6 12 34 56 78    |
      | masquée   | non                  |

  Scénario: Sans le droit, je lis un collègue masqué, sans que la longueur de ses données ne fuie
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la fiche de "Grace"
    Alors je vois la fiche :
      | prénom    | G***e    |
      | nom       | H***r    |
      | e-mail    | g***@*** |
      | téléphone | ******78 |
      | photo     | aucune   |
      | masquée   | oui      |

  Scénario: Je me lis toujours en clair, même sans le droit
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Et mon profil est :
      | prénom | Ada      |
      | nom    | Lovelace |
    Quand je consulte la fiche de "moi"
    Alors je vois la fiche :
      | prénom  | Ada      |
      | nom     | Lovelace |
      | masquée | non      |
    Et je peux sur sa fiche :
      | changer le nom          |
      | changer la fonction     |
      | changer les coordonnées |
      | changer la photo        |

  Scénario: Une personne d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et "Linus" est membre de "Concurrent" avec le profil :
      | prénom | Linus |
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand je consulte la fiche de "Linus"
    Alors c'est introuvable

  Scénario: Je lis d'un seul appel les personnes d'une page, sans celles d'un autre compte
    Soit "Alan" est membre de "SOLEAN IT" avec le profil :
      | prénom | Alan   |
      | nom    | Turing |
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et "Linus" est membre de "Concurrent" avec le profil :
      | prénom | Linus |
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte ensemble les fiches de Grace, Alan et Linus
    Alors je vois les fiches :
      | prénom | masquée |
      | A***n  | oui     |
      | G***e  | oui     |
