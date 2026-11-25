# language: fr
@NoteMailboxNotification @ReadMailboxHistory @ImportMail @NoteImportedEmail
Fonctionnalité: Importer un e-mail reçu d'un contact ou d'une organisation connus
  En tant que prospecteur qui a activé l'import de ses e-mails Gmail
  Je veux qu'un e-mail reçu d'un contact, ou de l'adresse d'une organisation que je prospecte, soit noté tout seul
  Afin de retrouver dans la timeline ce qu'on m'a écrit, sans rien noter moi-même

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.fr"
    Et l'organisation "Globex" d'e-mail "contact@globex.fr" appartient à "SOLEAN IT"
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et le client Google de "SOLEAN IT" a le topic Pub/Sub "projects/solean/topics/gmail"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google "michel@exemple.fr" est connecté
    Et j'ai activé l'import de mes e-mails Gmail
    Et nous sommes le "2026-11-18 10:00"

  Scénario: Un e-mail d'un contact devient un échange reçu
    Quand je reçois le "2026-11-18 09:12" un e-mail de "Ada Lovelace <Ada@ACME.fr>" ayant pour objet "Votre devis" et pour texte "Bonjour Michel, c'est validé."
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | moment           | canal  | sens | statut |
      | 18/11/2026 09:12 | E-mail | reçu | Fait   |
    Et le texte de l'échange importé est "Votre devis" suivi de "Bonjour Michel, c'est validé."
    Et j'en suis l'auteur
    Et l'échange importé est gardé pour "ACME"

  Scénario: Un échange importé compte dans les chiffres comme un échange noté
    Soit les chiffres ont été recalculés
    Quand je reçois un e-mail de "ada@acme.fr"
    Alors mes chiffres sont :
      | échanges | 1 |

  Scénario: Un e-mail de l'adresse d'une organisation devient un échange avec l'organisation elle-même
    Quand je reçois un e-mail de "contact@globex.fr"
    Alors la timeline de "Globex" est, du plus récent au plus ancien :
      | canal  | sens | interlocuteur |
      | E-mail | reçu | Globex        |

  Scénario: Un contact dont une organisation a aussi l'adresse est l'interlocuteur de l'échange
    Soit l'organisation "Initech" d'e-mail "ada@acme.fr" appartient à "SOLEAN IT"
    Quand je reçois un e-mail de "ada@acme.fr"
    Alors la timeline de "Ada Lovelace" montre un e-mail reçu
    Et la timeline de "Initech" est vide

  Scénario: Un expéditeur inconnu est ignoré
    Quand je reçois un e-mail de "inconnu@exemple.fr"
    Alors aucun échange n'est importé
    Et "SOLEAN IT" n'a aucun autre contact que "Ada Lovelace"

  Scénario: Les personnes en copie sont ignorées
    Quand je reçois un e-mail de "inconnu@exemple.fr" avec "ada@acme.fr" en copie
    Alors aucun échange n'est importé

  Plan du scénario: Le spam, la corbeille, les brouillons et les e-mails envoyés ne sont pas des e-mails reçus
    Quand je reçois un e-mail de "ada@acme.fr" rangé dans "<libellé>"
    Alors aucun échange n'est importé

    Exemples:
      | libellé |
      | SPAM    |
      | TRASH   |
      | DRAFT   |
      | SENT    |

  Plan du scénario: Les onglets Promotions et Réseaux sociaux comptent
    Quand je reçois un e-mail de "ada@acme.fr" rangé dans "<libellé>"
    Alors la timeline de "Ada Lovelace" montre un e-mail reçu

    Exemples:
      | libellé             |
      | CATEGORY_PROMOTIONS |
      | CATEGORY_SOCIAL     |

  Scénario: Un même e-mail n'est importé qu'une fois
    Soit j'ai reçu un e-mail de "ada@acme.fr"
    Quand Gmail prévient Prospecter une seconde fois de cet e-mail
    Alors un seul échange est importé

  Scénario: Un e-mail reçu par deux membres ne fait qu'un échange, par le premier qui l'a importé
    Soit "Bob" est membre de "SOLEAN IT" et a activé l'import de sa boîte Gmail "bob@exemple.fr"
    Quand "ada@acme.fr" écrit à "michel@exemple.fr" et à "bob@exemple.fr" dans un même e-mail
    Alors un seul échange est importé
    Et j'en suis l'auteur

  Scénario: Un échange importé puis supprimé ne revient pas
    Soit j'ai reçu un e-mail de "ada@acme.fr"
    Et je supprime l'échange importé
    Quand Gmail prévient Prospecter une seconde fois de cet e-mail
    Alors aucun échange n'est importé

  Scénario: Un e-mail noté par une IA n'est pas importé
    Soit l'IA a noté l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Quand je reçois un e-mail de "ada@acme.fr" de Message-ID "<ABC@acme.fr>"
    Alors aucun échange n'est importé

  Scénario: Un e-mail importé n'est pas renoté par une IA
    Soit j'ai reçu un e-mail de "ada@acme.fr" de Message-ID "<abc@acme.fr>"
    Quand l'IA note l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Alors c'est déjà noté, avec l'échange existant

  Scénario: Un e-mail d'un contact oublié n'est plus importé
    Soit j'efface le contact "Ada Lovelace"
    Quand je reçois un e-mail de "ada@acme.fr"
    Alors aucun échange n'est importé

  Scénario: Un e-mail d'un contact à rattacher d'un autre membre n'est pas importé
    Soit "Grace Hopper" est un contact à rattacher d'un autre membre, d'adresse "grace@exemple.fr"
    Quand je reçois un e-mail de "grace@exemple.fr"
    Alors aucun échange n'est importé

  Scénario: Une notification perdue est rattrapée par la suivante
    Soit Prospecter n'a pas reçu la notification d'un e-mail de "ada@acme.fr"
    Quand je reçois un e-mail de "contact@globex.fr"
    Alors 2 échanges sont importés

  Scénario: Une notification perdue est rattrapée par le renouvellement quotidien
    Soit Prospecter n'a pas reçu la notification d'un e-mail de "ada@acme.fr"
    Quand le renouvellement quotidien passe
    Alors 1 échange est importé

  Scénario: Un historique Gmail expiré est repris par la date de sa dernière lecture complète
    Soit j'ai reçu un e-mail de "contact@globex.fr"
    Et Prospecter n'a pas reçu la notification d'un e-mail de "ada@acme.fr"
    Et Gmail a oublié l'historique de "michel@exemple.fr"
    Quand je reçois un e-mail de "ada@acme.fr"
    Alors 3 échanges sont importés

  Scénario: Une lecture de l'historique s'arrête à 500 e-mails, la suivante reprend où elle s'est arrêtée
    Soit Prospecter n'a pas reçu la notification de 501 e-mails de "ada@acme.fr"
    Quand le renouvellement quotidien passe
    Alors 500 échanges sont importés
    Quand le renouvellement quotidien passe
    Alors 501 échanges sont importés

  Scénario: Un historique expiré après une lecture coupée est repris d'avant le premier e-mail non lu
    Soit Prospecter n'a pas reçu la notification de 501 e-mails de "ada@acme.fr"
    Et le renouvellement quotidien passe
    Et Gmail a oublié l'historique de "michel@exemple.fr"
    Quand le renouvellement quotidien passe
    Alors 501 échanges sont importés

  Scénario: Un historique expiré est repris pour les 1 000 e-mails les plus récents
    Soit Prospecter n'a pas reçu la notification de 1001 e-mails de "ada@acme.fr"
    Et Gmail a oublié l'historique de "michel@exemple.fr"
    Quand le renouvellement quotidien passe
    Alors 1000 échanges sont importés
    Et le plus ancien e-mail n'est pas importé

  Scénario: Un objet et un texte trop longs sont coupés
    Quand je reçois un e-mail de "ada@acme.fr" dont l'objet fait 300 caractères et le texte 12000
    Alors le texte de l'échange importé commence par 250 caractères de l'objet et finit par 10000 caractères du texte

  Scénario: Un import coupé ne lit plus la boîte
    Soit je coupe l'import de mes e-mails Gmail
    Quand le renouvellement quotidien passe
    Alors aucun échange n'est importé

  Scénario: Un accès retiré à la lecture rend l'import à reconnecter
    Soit Prospecter n'a pas reçu la notification d'un e-mail de "ada@acme.fr"
    Et je retire l'accès de Prospecter dans mon compte Google
    Quand Gmail prévient Prospecter d'un changement de ma boîte
    Alors l'import de mes e-mails Gmail est "à reconnecter"
    Et aucun échange n'est importé

  Scénario: Un accès retiré avant la lecture d'un e-mail n'importe rien, sans échec
    Soit Gmail refuse la lecture des messages de "michel@exemple.fr"
    Quand je reçois un e-mail de "ada@acme.fr"
    Alors aucun échange n'est importé

  Scénario: Perdre le droit aux données personnelles coupe l'import
    Soit Prospecter n'a pas reçu la notification d'un e-mail de "ada@acme.fr"
    Et je perds le droit aux données personnelles
    Quand Gmail prévient Prospecter d'un changement de ma boîte
    Alors l'import de mes e-mails Gmail est "coupé"
    Et Gmail ne prévient plus Prospecter des nouveaux e-mails de "michel@exemple.fr"
    Et aucun échange n'est importé
