# language: fr
Fonctionnalité: Identifier des personnes
  En tant que prospecteur, ou l'IA qui me sert
  Je veux savoir, pour chaque personne telle que je la vois, si elle est déjà un de mes contacts
  Afin de ne rien créer en double

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"

  Scénario: Reconnue par son profil LinkedIn, quelle que soit sa saisie
    Quand j'identifie les personnes :
      | liens                        |
      | linkedin.com/in/Ada-Lovelace |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Ada Lovelace |

  Scénario: Reconnue par le lien d'un autre outil
    Soit "Ada Lovelace" a noté le lien "https://www.malt.fr/profile/adalovelace"
    Quand j'identifie les personnes :
      | liens                                          |
      | https://www.malt.fr/profile/AdaLovelace?ref=x |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Ada Lovelace |

  Scénario: Reconnue par son e-mail, sans profil, quelle que soit sa casse
    Quand j'identifie les personnes :
      | prénom | nom      | e-mails     |
      | A.     | Lovelace | ADA@acme.fr |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Ada Lovelace |

  Scénario: Reconnue par son mobile, quelle que soit sa saisie
    Soit le contact "Grace Hopper" de "ACME" a le téléphone "06 12 34 56 78"
    Quand j'identifie les personnes :
      | téléphones   |
      | +33612345678 |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Grace Hopper |

  Scénario: Un fixe, que toute une organisation partage, ne désigne personne
    Soit le contact "Grace Hopper" de "ACME" a le téléphone "01 23 45 67 89"
    Quand j'identifie les personnes :
      | téléphones     |
      | 01 23 45 67 89 |
    Alors les personnes sont identifiées :
      | statut   | candidates |
      | nouvelle |            |

  Scénario: Des clés qui désignent deux contacts sont à confirmer, jamais connues
    Soit le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@globex.fr"
    Quand j'identifie les personnes :
      | liens                                    | e-mails         |
      | https://www.linkedin.com/in/ada-lovelace | grace@globex.fr |
    Alors les personnes sont identifiées :
      | statut      | candidates                 |
      | à confirmer | Ada Lovelace, Grace Hopper |

  Scénario: Plusieurs clés qui désignent le même contact le font connaître
    Quand j'identifie les personnes :
      | liens                                    | e-mails     |
      | https://www.linkedin.com/in/ada-lovelace | ada@acme.fr |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Ada Lovelace |

  Scénario: Un mobile que deux contacts partagent est à confirmer
    Soit le contact "Grace Hopper" de "ACME" a le téléphone "06 12 34 56 78"
    Et le contact "Alan Turing" de "ACME" a le téléphone "06 12 34 56 78"
    Quand j'identifie les personnes :
      | téléphones     |
      | 06 12 34 56 78 |
    Alors les personnes sont identifiées :
      | statut      | candidates                |
      | à confirmer | Grace Hopper, Alan Turing |

  Scénario: Un homonyme, sans clé, est à confirmer
    Quand j'identifie les personnes :
      | prénom | nom      |
      | Ada    | LOVELACE |
    Alors les personnes sont identifiées :
      | statut      | candidates   |
      | à confirmer | Ada Lovelace |

  Scénario: Les homonymes de l'organisation où elle travaille viennent en tête des candidates
    Soit l'organisation "Globex" fait partie de mes organisations
    Et le contact "Adà Lovelace" de "Globex"
    Quand j'identifie les personnes :
      | prénom | nom      | organisations |
      | Ada    | Lovelace | Globex        |
    Alors les personnes sont identifiées :
      | statut      | candidates                 |
      | à confirmer | Adà Lovelace, Ada Lovelace |

  Scénario: Sans le droit aux données personnelles, une personne n'est identifiée que par son nom
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'identifie les personnes :
      | prénom | nom      | liens                                    | e-mails     |
      |        |          |                                          | ada@acme.fr |
      | Ada    | Lovelace | https://www.linkedin.com/in/ada-lovelace |             |
    Alors les personnes sont identifiées :
      | statut      | candidates   |
      | nouvelle    |              |
      | à confirmer | Ada Lovelace |

  Scénario: Un indice qui n'est pas une clé de son genre n'identifie rien, sans refuser la liste
    Quand j'identifie les personnes :
      | liens                                                   | e-mails      | téléphones |
      | pas un lien, https://www.linkedin.com/company/acme-corp | pas-un-email | 12         |
      | https://www.linkedin.com/in/ada-lovelace                |              |            |
    Alors les personnes sont identifiées :
      | statut   | candidates   |
      | nouvelle |              |
      | connue   | Ada Lovelace |

  Scénario: Une inconnue est nouvelle
    Quand j'identifie les personnes :
      | prénom | nom    | liens                                    |
      | Grace  | Hopper | https://www.linkedin.com/in/grace-hopper |
    Alors les personnes sont identifiées :
      | statut   | candidates |
      | nouvelle |            |

  Scénario: Ni un contact d'un autre compte, ni le profil d'un membre ne sont reconnus
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech" a l'adresse e-mail "linus@initech.test"
    Et "Bob Martin" est membre de "SOLEAN IT" avec le profil :
      | prénom | Bob             |
      | nom    | Martin          |
      | e-mail | bob@solean.test |
    Quand j'identifie les personnes :
      | prénom | nom      | e-mails            |
      | Linus  | Torvalds | linus@initech.test |
      | Bob    | Martin   | bob@solean.test    |
    Alors les personnes sont identifiées :
      | statut   | candidates |
      | nouvelle |            |
      | nouvelle |            |

  Scénario: Une liste à la fois, chacune dans l'ordre donné
    Soit le contact "Grace Hopper" de "ACME" a le téléphone "06 12 34 56 78"
    Quand j'identifie les personnes :
      | prénom | nom      | e-mails     | téléphones     |
      | Alan   | Turing   |             |                |
      |        |          |             | 06 12 34 56 78 |
      | Ada    | Lovelace |             |                |
      |        |          | ada@acme.fr |                |
    Alors les personnes sont identifiées :
      | statut      | candidates   |
      | nouvelle    |              |
      | connue      | Grace Hopper |
      | à confirmer | Ada Lovelace |
      | connue      | Ada Lovelace |

  Scénario: Reconnue par un lien qu'elle n'a plus
    Soit je retire de "Ada Lovelace" le lien "https://www.linkedin.com/in/ada-lovelace"
    Quand j'identifie les personnes :
      | liens                                    |
      | https://www.linkedin.com/in/ada-lovelace |
    Alors les personnes sont identifiées :
      | statut | candidates   |
      | connue | Ada Lovelace |
