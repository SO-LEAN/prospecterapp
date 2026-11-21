# language: fr
Fonctionnalité: Reconnaître un échange importé de Gmail
  En tant que prospecteur qui a activé l'import de ses e-mails Gmail
  Je veux distinguer un échange importé d'un échange que j'ai noté à la main, et ouvrir l'e-mail d'origine dans Gmail
  Afin de lire la conversation entière ou d'y répondre

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et j'ai activé l'import de mes e-mails Gmail
    Et nous sommes le "2026-11-18 10:00"
    Et j'ai importé l'e-mail "Votre devis" reçu de "ada@acme.fr"

  Scénario: L'échange importé porte sa provenance
    Soit j'ai noté un e-mail reçu de "Ada Lovelace" le 17/11/2026 à 10h00
    Quand je consulte la timeline de "Ada Lovelace"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | canal  | provenance |
      | E-mail | Gmail      |
      | E-mail |            |

  Scénario: Le détail de l'échange importé dit sa provenance
    Quand je consulte cet échange
    Alors je vois l'échange :
      | provenance | Gmail |

  Scénario: Sans le droit aux données personnelles, l'échange importé est marqué comme importé, son texte signalé
    Soit je perds le droit aux données personnelles
    Quand je consulte cet échange
    Alors je vois l'échange :
      | provenance | Gmail |
      | signalé    | oui   |

  Scénario: Un échange importé se corrige comme les autres et garde sa provenance
    Soit nous sommes le "2026-11-18 11:00"
    Quand je corrige le texte de cet échange en "Votre devis, validé"
    Alors je vois l'échange :
      | texte      | Votre devis, validé           |
      | provenance | Gmail                         |
      | modifié    | le 18/11/2026 à 11:00 par moi |
