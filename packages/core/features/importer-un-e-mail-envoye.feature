# language: fr
Fonctionnalité: Importer un e-mail envoyé à un contact ou une organisation connus
  En tant que prospecteur qui a activé l'import de ses e-mails Gmail
  Je veux qu'un e-mail que j'envoie à un contact, ou à l'adresse d'une organisation que je prospecte, soit noté tout seul
  Afin de retrouver dans la timeline ce que j'ai écrit, sans rien noter moi-même

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et le contact "Alan Turing" de "ACME" a l'adresse e-mail "alan@acme.fr"
    Et l'organisation "Globex" d'e-mail "contact@globex.fr" appartient à "SOLEAN IT"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et j'ai activé l'import de mes e-mails Gmail
    Et nous sommes le "2026-11-18 11:00"

  Plan du scénario: Une réponse ou un nouvel e-mail à un contact devient un échange envoyé
    Quand j'envoie le "2026-11-18 10:05" à "Ada Lovelace <Ada@ACME.fr>" un e-mail ayant pour objet "<objet>" et pour texte "C'est noté, merci."
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens   | statut |
      | 18/11/2026 10:05 | E-mail | envoyé | Fait   |
    Et le texte de l'échange importé est "<objet>" suivi de "C'est noté, merci."
    Et j'en suis l'auteur
    Et l'échange importé est gardé pour "ACME"

    Exemples:
      | objet           |
      | RE: Votre devis |
      | Votre devis     |

  Scénario: Un échange par destinataire connu en « À »
    Quand j'envoie un e-mail à "ada@acme.fr", "alan@acme.fr" et "inconnu@exemple.fr"
    Alors la timeline de "Ada Lovelace" montre un e-mail envoyé
    Et la timeline de "Alan Turing" montre un e-mail envoyé
    Et 2 échanges sont importés
    Et "SOLEAN IT" n'a aucun autre contact que "Ada Lovelace" et "Alan Turing"

  Scénario: Les destinataires en copie sont ignorés
    Quand j'envoie un e-mail à "inconnu@exemple.fr" avec "ada@acme.fr" en copie
    Alors aucun échange n'est importé

  Scénario: Un e-mail à l'adresse d'une organisation devient un échange avec l'organisation elle-même
    Quand j'envoie un e-mail à "contact@globex.fr"
    Alors la timeline de "Globex" est, du plus récent au plus ancien :
      | canal  | sens   | interlocuteur |
      | E-mail | envoyé | Globex        |

  Scénario: Un brouillon n'est pas importé
    Quand j'enregistre un brouillon à "ada@acme.fr"
    Alors aucun échange n'est importé

  Scénario: Un e-mail envoyé n'est importé qu'une fois par destinataire
    Soit j'ai envoyé un e-mail à "ada@acme.fr"
    Quand Gmail prévient Prospecter une seconde fois de cet e-mail
    Alors un seul échange est importé

  Scénario: Un échange envoyé supprimé ne revient pas
    Soit j'ai envoyé un e-mail à "ada@acme.fr"
    Et je supprime l'échange importé
    Quand Gmail prévient Prospecter une seconde fois de cet e-mail
    Alors aucun échange n'est importé

  Scénario: Un historique Gmail expiré est repris avec les e-mails envoyés
    Soit Prospecter n'a pas reçu la notification d'un e-mail envoyé à "ada@acme.fr"
    Et Gmail a oublié l'historique de "michel@exemple.fr"
    Quand le renouvellement quotidien passe
    Alors 1 échange est importé
