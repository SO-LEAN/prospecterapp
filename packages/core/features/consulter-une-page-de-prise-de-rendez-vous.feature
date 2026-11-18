# language: fr
Fonctionnalité: Consulter une page de prise de rendez-vous
  En tant que personne à qui un prospecteur a donné son lien public
  Je veux voir ses créneaux libres de 15 minutes, à partir de demain et sur deux semaines, sans me connecter
  Afin d'en choisir un, jamais sur un créneau où il a déjà un échange planifié

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour  | de    | à     |
      | lundi | 14:00 | 15:00 |
      | mardi | 09:00 | 09:30 |

  Scénario: Les créneaux libres commencent demain, par 15 minutes
    Soit ma prise de rendez-vous est active
    Quand on ouvre mon lien public
    Alors ses premiers créneaux sont :
      | mardi 24/11/2026 09:00 |
      | mardi 24/11/2026 09:15 |
      | lundi 30/11/2026 14:00 |
      | lundi 30/11/2026 14:15 |
      | lundi 30/11/2026 14:30 |
      | lundi 30/11/2026 14:45 |

  Scénario: La page dit qui reçoit, de quelle organisation, et à l'heure de quel fuseau
    Soit mon profil est :
      | prénom | Grace                |
      | nom    | Hopper               |
      | e-mail | grace@solean-it.test |
    Et j'ai une image "grace.jpg" de 4096 octets
    Et je change ma photo avec "grace.jpg"
    Et ma prise de rendez-vous est active
    Quand on ouvre mon lien public
    Alors il présente "Grace Hopper" de "SOLEAN IT", avec la photo "https://storage.test/grace.jpg"
    Et ses créneaux sont à l'heure de "Europe/Paris"

  Scénario: Pas de créneau le jour même
    Soit ma prise de rendez-vous est active
    Quand on ouvre mon lien public
    Alors aucun créneau n'est le 23/11/2026

  Scénario: Les créneaux vont jusqu'à 14 jours
    Soit ma prise de rendez-vous est active
    Quand on ouvre mon lien public
    Alors le dernier créneau est le lundi 07/12/2026 à 14h45

  Scénario: Un créneau où j'ai un échange planifié n'est pas libre
    Soit ma prise de rendez-vous est active
    Et j'ai planifié un appel avec "Ada Lovelace" le 24/11/2026 à 9h10 pour 10 minutes
    Quand on ouvre mon lien public
    Alors le créneau du 24/11/2026 à 9h00 n'est pas libre
    Et le créneau du 24/11/2026 à 9h15 n'est pas libre

  Scénario: Une période inactive n'a pas de créneau
    Soit ma prise de rendez-vous est active
    Et j'ai ajouté une période inactive du 30/11/2026 au 30/11/2026
    Quand on ouvre mon lien public
    Alors aucun créneau n'est le 30/11/2026

  Scénario: Une page active propose les canaux de son prospecteur
    Soit ma prise de rendez-vous est active, en proposant la visio
    Quand on ouvre mon lien public
    Alors il propose mes créneaux
    Et il propose la visio seulement

  Scénario: Un lien coupé ne montre rien, sans dire pourquoi
    Soit ma prise de rendez-vous est active
    Et je l'ai coupée
    Quand on ouvre mon lien public
    Alors il ne montre rien, sans dire pourquoi

  Scénario: Un lien changé ne montre rien, sans dire pourquoi
    Soit ma prise de rendez-vous est active
    Et je change de lien
    Quand on ouvre l'ancien lien
    Alors il ne montre rien, sans dire pourquoi

  Scénario: Un lien inconnu ne montre rien, sans dire pourquoi
    Quand on ouvre le lien public "AbCdEfGhIjKlMnOpQrStUv"
    Alors il ne montre rien, sans dire pourquoi

  Scénario: Sans Google connecté, aucun créneau n'est proposé
    Soit ma prise de rendez-vous est active
    Et Google refuse mon jeton
    Quand on ouvre mon lien public
    Alors il ne montre rien, sans dire pourquoi
