# language: fr
Fonctionnalité: Préparer une campagne
  En tant que prospecteur
  Je veux préparer une campagne : un canal, des tags pour cibler mes contacts et un message type
  Afin de contacter moi-même chaque personne ciblée, l'app me rappelant le message

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits

  Scénario: Je prépare une campagne en brouillon
    Quand je prépare la campagne "Relance ESN" :
      | canal   | E-mail                                |
      | tags    | #ESN                                  |
      | message | Bonjour {prénom}, je suis disponible… |
    Alors la campagne "Relance ESN" est en brouillon
    Et elle cible :
      | prénom | nom      |
      | Ada    | Lovelace |

  Scénario: Le canal exige le moyen de joindre le contact
    Quand je prépare la campagne "Relance ESN" avec le canal "E-mail" et les tags "#ESN"
    Alors ses tags sont "#ESN #EMAIL"
    Et "Grace Hopper", sans e-mail, n'est pas ciblée

  Plan du scénario: Chaque canal a son tag
    Quand je prépare une campagne avec le canal "<canal>"
    Alors ses tags comprennent "<tag>"

    Exemples:
      | canal    | tag       |
      | E-mail   | #EMAIL    |
      | Appel    | #PHONE    |
      | SMS      | #SMS      |
      | LinkedIn | #LINKEDIN |

  Scénario: Le tag du canal choisi aussi n'est compté qu'une fois
    Quand je prépare la campagne "Relance ESN" avec le canal "E-mail" et les tags "#ESN #EMAIL"
    Alors ses tags sont "#ESN #EMAIL"

  Scénario: Le tag du canal compte parmi les 5 tags de la cible
    Quand je prépare la campagne "Relance ESN" avec le canal "E-mail" et les tags "#ESN #PHP #SYMFONY #DECIDEUR #CLIENT"
    Alors c'est refusé : "Choose from 1 to 5 tags"

  Scénario: En brouillon, la cible suit les tags des contacts
    Soit la campagne "Relance ESN" en brouillon cible les tags "#ESN"
    Quand "Grace Hopper" reçoit l'adresse e-mail "grace@acme.test"
    Alors la campagne "Relance ESN" cible "Ada Lovelace" et "Grace Hopper"

  Scénario: En brouillon, je modifie tout
    Soit la campagne "Relance ESN" en brouillon
    Quand je change ses tags en "#ESN #STRASBOURG", son canal en "LinkedIn" et son message
    Alors la campagne "Relance ESN" est enregistrée ainsi

  Scénario: Le message type est rendu pour chaque contact
    Soit la campagne "Relance ESN" a le message "Bonjour {prénom} {nom}, chez {organisation}…"
    Quand je lis le message pour "Ada Lovelace"
    Alors il vaut "Bonjour Ada Lovelace, chez ACME…"

  Scénario: Une variable sans valeur est rendue vide
    Soit la campagne "Relance ESN" a le message "Bonjour {prénom}, {fonction} chez {organisation}"
    Et la fonction de "Ada Lovelace" est "CTO"
    Quand je lis le message pour "Ada Lovelace"
    Alors il vaut "Bonjour Ada, CTO chez ACME"
    Quand je lis le message pour "Grace Hopper"
    Alors il vaut "Bonjour Grace,  chez ACME"

  Scénario: L'objet d'un e-mail est rendu comme son message
    Soit la campagne "Relance ESN" a l'objet "{prénom}, une question" et le message "Bonjour {prénom}"
    Quand je lis le message pour "Ada Lovelace"
    Alors son objet vaut "Ada, une question"

  Scénario: La civilité d'une contact est rendue
    Soit la campagne "Relance ESN" a le message "Bonjour {civilité} {nom},"
    Et "Grace Hopper" a la civilité "Madame"
    Quand je lis le message pour "Grace Hopper"
    Alors il vaut "Bonjour Madame Hopper,"

  Scénario: La civilité d'un contact est rendue
    Soit le contact "Alan Turing" de "ACME"
    Et la campagne "Relance ESN" a le message "Bonjour {civilité} {nom},"
    Et "Alan Turing" a la civilité "Monsieur"
    Quand je lis le message pour "Alan Turing"
    Alors il vaut "Bonjour Monsieur Turing,"

  Scénario: Sans civilité, la variable reste vide
    Soit la campagne "Relance ESN" a le message "Bonjour {civilité} {nom},"
    Quand je lis le message pour "Grace Hopper"
    Alors il vaut "Bonjour  Hopper,"

  Scénario: La civilité est rendue dans l'objet d'un e-mail aussi
    Soit la campagne "Relance ESN" a l'objet "{civilité} {nom}, une question" et le message "Bonjour"
    Et "Grace Hopper" a la civilité "Madame"
    Quand je lis le message pour "Grace Hopper"
    Alors son objet vaut "Madame Hopper, une question"

  Scénario: Une campagne a un nom, un canal et au moins un tag choisi
    Quand je prépare la campagne "" sans tag
    Alors c'est refusé : "A campaign has a name"
    Quand je prépare la campagne "Relance ESN" sans tag
    Alors c'est refusé : "Choose from 1 to 5 tags"
    Quand je prépare la campagne "Relance ESN" avec le canal "Fax" et les tags "#ESN"
    Alors c'est refusé : "A campaign is run by e-mail, call, SMS or LinkedIn"

  Scénario: Le nom fait 100 caractères au plus
    Quand je prépare une campagne dont le nom fait 101 caractères
    Alors c'est refusé : "A campaign name has 100 characters at most"

  Scénario: Le message fait 5 000 caractères au plus
    Quand je prépare une campagne dont le message fait 5001 caractères
    Alors c'est refusé : "A message has 5000 characters at most"

  Scénario: Seul un e-mail a un objet
    Quand je prépare la campagne "Relance ESN" par SMS avec l'objet "Une question"
    Alors c'est refusé : "Only an e-mail has a subject"

  Scénario: Je supprime un brouillon
    Soit la campagne "Relance ESN" en brouillon
    Quand je supprime ce brouillon
    Alors je n'ai plus de campagne

  Scénario: Les campagnes sont partagées par l'équipe
    Soit un autre prospecteur de "SOLEAN IT" a préparé la campagne "Éditeurs"
    Quand je liste les campagnes
    Alors j'y vois "Éditeurs", préparée par lui

  Scénario: Chaque campagne listée dit les contacts qu'elle cible et ceux déjà contactés, aucun en brouillon
    Soit la campagne "Relance ESN" en brouillon cible les tags "#ESN"
    Et la campagne "Appels ESN" en brouillon cible les tags "#ESN" par "Appel"
    Quand je liste les campagnes
    Alors les campagnes listées sont :
      | nom         | canal  | statut    | contactés | ciblés |
      | Appels ESN  | Appel  | brouillon | 0         | 0      |
      | Relance ESN | E-mail | brouillon | 0         | 1      |

  Scénario: La campagne d'un autre compte m'est inconnue
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et un prospecteur de "Concurrent" a préparé la campagne "Secrète"
    Quand je consulte la campagne "Secrète"
    Alors c'est introuvable
