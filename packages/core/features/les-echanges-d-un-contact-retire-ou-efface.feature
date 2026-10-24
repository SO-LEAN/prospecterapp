# language: fr
Fonctionnalité: Les échanges d'un contact retiré ou effacé
  En tant que prospecteur
  Je veux garder les échanges d'une organisation quand un contact la quitte ou que sa fiche est effacée
  Afin de ne pas perdre son historique commercial, en rendant conforme au RGPD ce qui peut encore contenir des données personnelles

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Groupe ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" est aussi contact de "Groupe ACME"
    Et "Michel" a noté le 20/10/2026 à 10h05 un e-mail reçu de "Ada Lovelace" à 10h00 : "Ada, 06 12 34 56 78, attend notre devis"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le contact retiré reste dans la timeline de l'organisation, son nom barré
    Quand je retire le contact "Ada Lovelace" de "ACME"
    Alors la timeline de "ACME" est, du plus récent au plus ancien :
      | interlocuteur | contact | canal  | à rendre conforme |
      | Ada Lovelace  | retiré  | E-mail | non               |
    Et la timeline de "Groupe ACME" est, du plus récent au plus ancien :
      | interlocuteur | contact | canal  |
      | Ada Lovelace  | présent | E-mail |
    Et la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | canal  | texte                                   |
      | E-mail | Ada, 06 12 34 56 78, attend notre devis |

  Scénario: Le contact effacé devient « Contact effacé », le texte est gardé et à rendre conforme
    Quand j'efface la fiche de "Ada Lovelace"
    Alors la timeline de "ACME" est, du plus récent au plus ancien :
      | interlocuteur | contact | texte                                   | texte à rendre conforme | à rendre conforme |
      |               | effacé  | Ada, 06 12 34 56 78, attend notre devis | oui                     | oui               |
    Et la timeline de "Groupe ACME" est, du plus récent au plus ancien :
      | contact | à rendre conforme |
      | effacé  | oui               |
    Et je peux sur l'échange :
      | corriger l'échange       |
      | corriger le texte        |
      | rendre conforme          |
      | supprimer l'échange      |
      | joindre un fichier       |
      | retirer une pièce jointe |

  Scénario: Je rends conforme un échange en retouchant son texte
    Soit j'efface la fiche de "Ada Lovelace"
    Et nous sommes le 21/10/2026 à 11h00
    Quand je corrige le texte de cet échange en "La directrice commerciale attend notre devis" et le déclare rendu conforme
    Alors je vois l'échange :
      | contact                 | effacé                                       |
      | texte                   | La directrice commerciale attend notre devis |
      | texte à rendre conforme | non                                          |
      | à rendre conforme       | non                                          |
      | rendu conforme          | le 21/10/2026 à 11:00 par moi                |
    Et la timeline de "ACME" est, du plus récent au plus ancien :
      | contact | à rendre conforme |
      | effacé  | non               |

  Scénario: Je rends conforme un échange en le corrigeant tout entier
    Soit j'efface la fiche de "Ada Lovelace"
    Quand je corrige cet échange et le déclare rendu conforme :
      | texte | La directrice commerciale attend notre devis |
    Alors je vois l'échange :
      | texte             | La directrice commerciale attend notre devis |
      | à rendre conforme | non                                          |

  Scénario: Corriger le texte sans le déclarer rendu conforme le laisse à rendre conforme
    Soit j'efface la fiche de "Ada Lovelace"
    Quand je corrige le texte de cet échange en "La directrice commerciale attend notre devis"
    Alors je vois l'échange :
      | texte à rendre conforme | oui |
      | à rendre conforme       | oui |

  Scénario: Sans le droit aux données personnelles, je rends conforme le texte aussi
    Soit j'efface la fiche de "Ada Lovelace"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je corrige le texte de cet échange en "La directrice commerciale attend notre devis" et le déclare rendu conforme
    Alors je vois l'échange :
      | à rendre conforme | non |

  Scénario: Un échange dont le contact n'est pas effacé n'est pas à rendre conforme
    Quand je corrige le texte de cet échange en "Elle attend notre devis" et le déclare rendu conforme
    Alors c'est refusé : "Only an exchange with a forgotten contact is to make conform"

  Scénario: Un échange déjà rendu conforme ne l'est pas deux fois
    Soit j'efface la fiche de "Ada Lovelace"
    Et je corrige le texte de cet échange en "La directrice commerciale attend notre devis" et le déclare rendu conforme
    Quand je corrige le texte de cet échange en "La directrice attend notre devis" et le déclare rendu conforme
    Alors c'est refusé : "Only an exchange with a forgotten contact is to make conform"

  Scénario: Les pièces jointes d'un échange avec un contact effacé sont aussi à rendre conformes
    Soit "cv-ada.pdf" est joint à cet échange
    Quand j'efface la fiche de "Ada Lovelace"
    Alors "cv-ada.pdf" est à rendre conforme
    Et je peux sur l'échange :
      | corriger l'échange       |
      | corriger le texte        |
      | rendre conforme          |
      | supprimer l'échange      |
      | joindre un fichier       |
      | retirer une pièce jointe |

  Scénario: L'échange reste à rendre conforme tant qu'une pièce jointe n'est pas retirée
    Soit "cv-ada.pdf" est joint à cet échange
    Et j'efface la fiche de "Ada Lovelace"
    Et je corrige le texte de cet échange en "La directrice commerciale attend notre devis" et le déclare rendu conforme
    Alors je vois l'échange :
      | texte à rendre conforme | non |
      | à rendre conforme       | oui |
    Quand je retire "cv-ada.pdf"
    Alors je vois l'échange :
      | à rendre conforme | non |

  Scénario: L'organisation signale combien d'échanges sont à rendre conformes
    Soit nous sommes le 20/10/2026 à 11h00
    Et j'ai noté un appel reçu de "Ada Lovelace" le 20/10/2026 à 10h30
    Et j'ai noté un appel reçu de "ACME" elle-même le 20/10/2026 à 10h45
    Quand j'efface la fiche de "Ada Lovelace"
    Alors la timeline de "ACME" signale 2 échanges à rendre conformes
    Et les échanges à rendre conformes de "ACME" sont, du plus récent au plus ancien :
      | moment           | canal  |
      | 20/10/2026 10:30 | Appel  |
      | 20/10/2026 10:00 | E-mail |

  Scénario: Un échange rendu conforme n'est plus signalé par l'organisation
    Soit j'efface la fiche de "Ada Lovelace"
    Quand je corrige le texte de cet échange en "La directrice commerciale attend notre devis" et le déclare rendu conforme
    Alors la timeline de "ACME" signale 0 échanges à rendre conformes
    Et les échanges à rendre conformes de "ACME" sont, du plus récent au plus ancien :
      | moment |
