# language: fr
@ConsultMembers @ListAccounts @ConsultAppointments @NameCampaigns @WhichRecipientsArePending
@WhichHaveAConnectedCalendar @ListContactFacts @DescribePersons @NameContacts @PresentMembers @WhichAreContacts
@WhichContactsHaveTheseEmails @WhichEmailsHaveTheseContacts @ConsultHeldExchanges @ConsultPlannedExchanges
@ListExchangeFacts @ListOrganizationFacts @NameOrganizationsOfContacts @WhichAreProspected
@WhichContactsHaveExchangesOfCampaign @WhichEmailsHaveTheseOrganizations @WhichOrganizationsHaveTheseEmails
@WhichOrganizationsNameTheseContacts
Fonctionnalité: Le langage publié des contextes
  En tant que contexte de Prospecter
  Je veux poser à un autre contexte les seules questions qu'il publie, sur plusieurs éléments à la fois
  Afin que chacun change son modèle sans casser les autres, et qu'une question coûte un seul appel

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"

  Scénario: Accounts dit qui sont des membres et s'ils gèrent les données personnelles
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Grace Hopper |
      | Alan Turing  |
      | Personne     |
    Alors Accounts répond que "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et Accounts répond que "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et Accounts ne répond rien de "Personne"

  Scénario: Accounts dit le nom du compte des membres
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Grace Hopper |
    Alors Accounts répond que "Grace Hopper" est membre du compte nommé "SOLEAN IT"

  Scénario: Accounts dit si un membre administre son compte
    Soit "Ada Byron" est membre de "SOLEAN IT" qui l'administre
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Ada Byron   |
      | Alan Turing |
    Alors Accounts répond que "Ada Byron" administre son compte
    Et Accounts répond que "Alan Turing" n'administre pas son compte

  Scénario: Accounts dit combien de temps avant ses échanges un membre veut être rappelé
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et "Ada Byron" est membre de "SOLEAN IT" qui l'administre
    Et "Grace Hopper" a réglé ses rappels à 15 minutes avant
    Et "Alan Turing" a désactivé ses rappels
    Quand un autre contexte demande à Accounts qui sont les membres :
      | Grace Hopper |
      | Alan Turing  |
      | Ada Byron    |
    Alors Accounts répond que "Grace Hopper" veut être rappelée 15 minutes avant
    Et Accounts répond que "Alan Turing" ne veut pas être rappelé
    Et Accounts répond que "Ada Byron" veut être rappelée 10 minutes avant

  Scénario: PersonalData nomme des contacts comme un membre les lit
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Quand un autre contexte demande à PersonalData les noms, comme "Grace Hopper" les lit, de :
      | Ada Lovelace |
      | Personne     |
    Alors PersonalData répond les noms :
      | Ada Lovelace |
    Quand un autre contexte demande à PersonalData les noms, comme "Alan Turing" les lit, de :
      | Ada Lovelace |
    Alors PersonalData répond les noms :
      | Ada Lovelace |
    Quand un autre contexte demande à PersonalData les noms, comme "Personne" les lit, de :
      | Ada Lovelace |
    Alors PersonalData ne répond aucun nom

  Scénario: PersonalData dit l'e-mail et le téléphone d'un contact à qui les lit en clair, et les tait à qui les lit masqués
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et le contact "Charles Babbage" de "ACME" a le téléphone "06 12 34 56 78"
    Quand un autre contexte demande à PersonalData les noms, comme "Grace Hopper" les lit, de :
      | Ada Lovelace    |
      | Charles Babbage |
    Alors PersonalData répond que "Ada Lovelace" est joignable à "ada@acme.test"
    Et PersonalData répond que "Charles Babbage" est joignable au "+33612345678"
    Quand un autre contexte demande à PersonalData les noms, comme "Alan Turing" les lit, de :
      | Ada Lovelace    |
      | Charles Babbage |
    Alors PersonalData ne répond ni l'e-mail ni le téléphone de "Ada Lovelace"
    Et PersonalData ne répond ni l'e-mail ni le téléphone de "Charles Babbage"

  Scénario: PersonalData décrit des personnes comme un membre les lit, en clair ou masquées
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et la fonction de "Ada Lovelace" est "CTO"
    Et "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Charles Babbage" de "ACME" a le téléphone "06 12 34 56 78" et parle "EN"
    Quand un autre contexte demande à PersonalData de décrire, comme "Grace Hopper" les lit :
      | Ada Lovelace    |
      | Charles Babbage |
      | Personne        |
    Alors PersonalData décrit :
      | personne        | masquée | prénom  | nom      | civilité | fonction | e-mail        | téléphone        | langue | liens                                                      |
      | Ada Lovelace    | non     | Ada     | Lovelace | x        | CTO      | ada@acme.test |                  |        | linkedInProfile ada-lovelace https://www.linkedin.com/in/ada-lovelace |
      | Charles Babbage | non     | Charles | Babbage  | x        |          |               | +33 6 12 34 56 78 | EN     |                                                            |
    Quand un autre contexte demande à PersonalData de décrire, comme "Alan Turing" les lit :
      | Ada Lovelace    |
      | Charles Babbage |
    Alors PersonalData décrit :
      | personne        | masquée | prénom  | nom      | civilité | fonction | e-mail   | téléphone | langue | liens       |
      | Ada Lovelace    | oui     | Ada     | Lovelace | x        | C***O    | a***@*** |           |        | *** *** *** |
      | Charles Babbage | oui     | Charles | Babbage  | x        |          |          | ******78  | EN     |             |
    Quand un autre contexte demande à PersonalData de décrire, comme "Personne" les lit :
      | Ada Lovelace |
    Alors PersonalData ne décrit personne

  Scénario: PersonalData présente des membres, leur nom et leur photo, jamais une personne prospectée
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon profil est :
      | prénom | Grace                |
      | nom    | Hopper               |
      | e-mail | grace@solean-it.test |
    Et j'ai une image "grace.jpg" de 4096 octets
    Et je change ma photo avec "grace.jpg"
    Quand un autre contexte demande à PersonalData de présenter :
      | moi          |
      | Ada Lovelace |
    Alors PersonalData présente seulement "Grace Hopper", avec la photo "https://storage.test/grace.jpg"

  Scénario: PersonalData dit la fonction des contacts qu'il nomme
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et la fonction de "Ada Lovelace" est "CTO"
    Quand un autre contexte demande à PersonalData les noms, comme "Grace Hopper" les lit, de :
      | Ada Lovelace |
    Alors PersonalData répond que "Ada Lovelace" est "CTO"

  Scénario: PersonalData dit en clair la civilité des contacts qu'il nomme
    Soit "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et "Ada Lovelace" a la civilité "Madame"
    Quand un autre contexte demande à PersonalData les noms, comme "Alan Turing" les lit, de :
      | Ada Lovelace |
    Alors PersonalData répond que "Ada Lovelace" a la civilité "ms"

  Scénario: PersonalData dit lesquelles de plusieurs personnes sont des contacts d'un compte
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à PersonalData lesquelles sont des contacts de "SOLEAN IT" :
      | Ada Lovelace |
      | Grace Hopper |
      | Personne     |
    Alors PersonalData répond :
      | Ada Lovelace |
    Quand un autre contexte demande à PersonalData lesquelles sont des contacts de "Concurrent" :
      | Ada Lovelace |
    Alors PersonalData ne répond aucune

  Scénario: PersonalData dit quels contacts d'un compte ont des adresses e-mail, sans tenir compte de la casse
    Quand un autre contexte demande à PersonalData quels contacts de "SOLEAN IT" ont les adresses :
      | Ada@ACME.test     |
      | inconnu@acme.test |
    Alors PersonalData répond les contacts :
      | adresse       | contact      |
      | ada@acme.test | Ada Lovelace |
    Quand un autre contexte demande à PersonalData quels contacts de "Concurrent" ont les adresses :
      | ada@acme.test |
    Alors PersonalData ne répond aucun contact

  Scénario: Prospecting dit quelles organisations d'un compte ont des adresses e-mail, sans tenir compte de la casse
    Soit l'organisation "Globex" d'e-mail "contact@globex.test" appartient à "SOLEAN IT"
    Quand un autre contexte demande à Prospecting quelles organisations de "SOLEAN IT" ont les adresses :
      | Contact@GLOBEX.test |
      | inconnu@acme.test   |
    Alors Prospecting répond les organisations ayant ces adresses :
      | adresse             | organisation |
      | contact@globex.test | Globex       |
    Quand un autre contexte demande à Prospecting quelles organisations de "Concurrent" ont les adresses :
      | contact@globex.test |
    Alors Prospecting ne répond aucune organisation

  Scénario: PersonalData dit les adresses e-mail de contacts au seul membre qui les lit en clair
    Soit "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et le contact "Charles Babbage" de "ACME"
    Quand un autre contexte demande à PersonalData les adresses e-mail, comme "Grace Hopper" les lit, de :
      | Ada Lovelace    |
      | Charles Babbage |
      | Personne        |
    Alors PersonalData répond les adresses e-mail :
      | contact         | adresse       |
      | Ada Lovelace    | ada@acme.test |
      | Charles Babbage |               |
    Quand un autre contexte demande à PersonalData les adresses e-mail, comme "Alan Turing" les lit, de :
      | Ada Lovelace |
    Alors PersonalData ne répond aucune adresse e-mail

  Scénario: Prospecting dit les adresses e-mail d'organisations qu'un compte prospecte
    Soit l'organisation "Globex" d'e-mail "contact@globex.test" appartient à "SOLEAN IT"
    Et l'organisation "Initech" appartient à "SOLEAN IT"
    Quand un autre contexte demande à Prospecting les adresses e-mail, pour "SOLEAN IT", de :
      | Globex  |
      | Initech |
    Alors Prospecting répond les adresses e-mail :
      | organisation | adresse             |
      | Globex       | contact@globex.test |
      | Initech      |                     |
    Quand un autre contexte demande à Prospecting les adresses e-mail, pour "Concurrent", de :
      | Globex |
    Alors Prospecting ne répond aucune adresse e-mail

  Scénario: Prospecting dit lesquelles de plusieurs organisations sont prospectées par un compte
    Quand un autre contexte demande à Prospecting lesquelles sont prospectées par "SOLEAN IT" :
      | ACME     |
      | Inconnue |
    Alors Prospecting répond :
      | ACME |
    Quand un autre contexte demande à Prospecting lesquelles sont prospectées par "Concurrent" :
      | ACME |
    Alors Prospecting ne répond aucune

  Scénario: Prospecting nomme les organisations de contacts d'un compte, de la première à la dernière
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Alan Turing" de "ACME" est aussi contact de "Globex"
    Quand un autre contexte demande à Prospecting les organisations, pour "SOLEAN IT", de :
      | Ada Lovelace |
      | Alan Turing  |
      | Personne     |
    Alors Prospecting répond les organisations :
      | contact      | organisations |
      | Ada Lovelace | ACME          |
      | Alan Turing  | ACME, Globex  |
    Quand un autre contexte demande à Prospecting les organisations, pour "Concurrent", de :
      | Ada Lovelace |
    Alors Prospecting ne répond aucune organisation

  Scénario: Prospecting dit quelles organisations nomment des contacts d'un compte
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Alan Turing" de "ACME" est aussi contact de "Globex"
    Quand un autre contexte demande à Prospecting quelles organisations, pour "SOLEAN IT", nomment :
      | Ada Lovelace |
      | Alan Turing  |
      | Personne     |
    Alors Prospecting répond les organisations :
      | contact      | organisations |
      | Ada Lovelace | ACME          |
      | Alan Turing  | ACME, Globex  |
    Quand un autre contexte demande à Prospecting quelles organisations, pour "Concurrent", nomment :
      | Ada Lovelace |
    Alors Prospecting ne répond aucune organisation

  Scénario: Prospecting liste les faits des organisations d'un compte, par tranche
    Soit l'organisation "Globex" appartient à "SOLEAN IT"
    Et l'organisation "Initech" appartient à "SOLEAN IT"
    Quand un autre contexte demande à Prospecting 2 faits des organisations de "SOLEAN IT"
    Alors Prospecting répond les faits des organisations :
      | organisation | révision | présente | géolocalisée | contacts |
      | Initech      | 1        | oui      | non          |          |
      | Globex       | 1        | oui      | non          |          |
    Quand il demande la suite
    Alors Prospecting répond les faits des organisations :
      | organisation | révision | présente | géolocalisée | contacts     |
      | ACME         | 2        | oui      | non          | Ada Lovelace |
    Et Prospecting ne donne pas de suite

  Scénario: Prospecting liste les faits des échanges d'un compte, par tranche
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté 3 échanges avec "ACME" elle-même
    Quand un autre contexte demande à Prospecting 2 faits des échanges de "SOLEAN IT"
    Alors Prospecting répond les faits de 2 échanges, présents, faits, révision 1
    Quand il demande la suite
    Alors Prospecting répond les faits de 1 échange, présent, fait, révision 1
    Et Prospecting ne donne pas de suite

  Scénario: Prospecting dit qui tient des échanges, quand et comment
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 17/11/2026 à 14h00 pour 1 heure au lieu "Café de Flore"
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal       | moment           | durée  | lieu          | lien | contact      | organisation |
      | moi       | À venir | Rendez-vous | 17/11/2026 14:00 | 60 min | Café de Flore |      | Ada Lovelace | ACME         |
    Quand un autre contexte demande à Prospecting, de "Concurrent", cet échange et un échange inconnu
    Alors Prospecting ne répond aucun échange

  Scénario: Prospecting dit un échange avec l'organisation elle-même, sans contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié une visio avec "ACME" le 17/11/2026 à 9h00 au lien "https://meet.example/acme"
    Et j'annule cette visio
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal | moment           | durée  | lieu | lien                      | contact | organisation |
      | moi       | Annulé  | Visio | 17/11/2026 09:00 | 30 min |      | https://meet.example/acme |         | ACME         |

  Scénario: Prospecting dit qu'une visio demande son lien au calendrier de son titulaire
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00, sans lien, en demandant un lien Meet
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond que cet échange demande son lien

  Scénario: Prospecting dit l'échange d'un contact à rattacher, sans organisation, et qu'on l'a réservé par le lien public
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour  | de    | à     |
      | mardi | 09:00 | 09:30 |
    Et ma prise de rendez-vous est active
    Et on a réservé un appel par mon lien public le 24/11/2026 à 9h00
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal | moment           | durée  | lieu | lien | contact      | organisation |
      | moi       | À venir | Appel | 24/11/2026 09:00 | 15 min |      |      | Ada Lovelace |              |
    Et Prospecting répond que cet échange a été réservé par le lien public

  Scénario: Prospecting nomme un échange par la première organisation qui le garde encore, et tait celui qu'aucune ne garde plus
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" est aussi contact de "Globex"
    Et j'ai planifié un appel avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et "ACME" n'est plus prospectée, l'échange encore gardé pour elle
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting répond l'échange :
      | titulaire | statut  | canal | moment           | durée  | lieu | lien | contact      | organisation |
      | moi       | À venir | Appel | 17/11/2026 14:00 | 30 min |      |      | Ada Lovelace | Globex       |
    Quand "Globex" n'est plus prospectée, l'échange encore gardé pour elle
    Et un autre contexte demande à Prospecting, de "SOLEAN IT", cet échange et un échange inconnu
    Alors Prospecting ne répond aucun échange

  Scénario: Prospecting dit les échanges planifiés à venir de leur titulaire, du plus proche au plus lointain
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 13/11/2026 à 10h00
    Et j'ai planifié un appel avec "Ada Lovelace" le 13/11/2026 à 11h00
    Et j'ai planifié un rendez-vous avec "Ada Lovelace" le 18/11/2026 à 9h00 pour 1 heure au lieu "Café de Flore"
    Et j'ai planifié une visio avec "Ada Lovelace" le 19/11/2026 à 9h00
    Et j'annule cette visio
    Et j'ai planifié un appel avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et un autre prospecteur de "SOLEAN IT" a planifié une visio avec "Ada Lovelace" le 17/11/2026 à 15h00
    Et nous sommes le 13/11/2026 à 15h00
    Quand un autre contexte demande à Prospecting, de "SOLEAN IT", les échanges que je tiens à venir
    Alors Prospecting répond les échanges :
      | titulaire | statut  | canal       | moment           | durée  | lieu          | lien | contact      | organisation |
      | moi       | À venir | Appel       | 17/11/2026 14:00 | 30 min |               |      | Ada Lovelace | ACME         |
      | moi       | À venir | Rendez-vous | 18/11/2026 09:00 | 60 min | Café de Flore |      | Ada Lovelace | ACME         |
    Quand un autre contexte demande à Prospecting, de "Concurrent", les échanges que je tiens à venir
    Alors Prospecting ne répond aucun échange

  Scénario: Campaigns dit lesquels de plusieurs contacts sont des destinataires d'une campagne, et lesquels sont à traiter
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Alan Turing" de "ACME" a l'adresse e-mail "alan@acme.test"
    Et le contact "Grace Hopper" de "ACME"
    Et "ACME" est tagué "#ESN"
    Et la campagne "Relance" démarrée, canal "E-mail", avec "Ada Lovelace" et "Alan Turing"
    Et "Alan Turing" est écarté de la campagne "Relance"
    Quand un autre contexte demande à Campaigns, de "SOLEAN IT", lesquels sont des destinataires à traiter de "Relance" :
      | Ada Lovelace |
      | Alan Turing  |
      | Grace Hopper |
    Alors Campaigns répond que "Relance" est démarrée, par "email", avec les destinataires "Ada Lovelace" et "Alan Turing", "Ada Lovelace" à traiter
    Quand un autre contexte demande à Campaigns, de "Concurrent", lesquels sont des destinataires à traiter de "Relance" :
      | Ada Lovelace |
    Alors Campaigns ne répond aucune campagne

  Scénario: Campaigns nomme des campagnes d'un compte, et tait une campagne inconnue
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "ACME" est tagué "#ESN"
    Et la campagne "Relance" figée, canal "E-mail", avec "Ada Lovelace"
    Quand un autre contexte demande à Campaigns, de "SOLEAN IT", le nom de "Relance" et d'une campagne inconnue
    Alors Campaigns nomme seulement "Relance"
    Quand un autre contexte demande à Campaigns, de "Concurrent", le nom de "Relance" et d'une campagne inconnue
    Alors Campaigns ne nomme aucune campagne

  Scénario: PersonalData liste les faits des contacts d'un compte, par tranche
    Soit le contact "Alan Turing" de "ACME"
    Et le contact "Linus Torvalds" de "ACME"
    Et "Grace Hopper" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Quand un autre contexte demande à PersonalData 2 faits des contacts de "SOLEAN IT"
    Alors PersonalData répond les faits des contacts :
      | contact        | révision | présent | joignable par |
      | Linus Torvalds | 1        | oui     |               |
      | Alan Turing    | 1        | oui     |               |
    Quand il demande la suite
    Alors PersonalData répond les faits des contacts :
      | contact      | révision | présent | joignable par |
      | Ada Lovelace | 1        | oui     | e-mail        |
    Et PersonalData ne donne pas de suite

  Scénario: Accounts liste les comptes, par tranche
    Quand un autre contexte demande à Accounts 1 compte
    Alors Accounts répond les comptes :
      | Concurrent |
    Quand il demande la suite
    Alors Accounts répond les comptes :
      | SOLEAN IT |
    Et Accounts ne donne pas de suite

  Scénario: Connectors dit lesquels de plusieurs membres ont un agenda connecté
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et "Alan Turing" est membre de "SOLEAN IT" sans le droit aux données personnelles
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Quand un autre contexte demande à Connectors lesquels ont un agenda connecté :
      | moi         |
      | Alan Turing |
      | Personne    |
    Alors Connectors répond :
      | moi |

  Scénario: Un agenda à reconnecter n'est pas un agenda connecté
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et Google refuse mon jeton
    Quand un autre contexte demande à Connectors lesquels ont un agenda connecté :
      | moi |
    Alors Connectors ne répond aucun

  Scénario: Un compte inconnu n'a aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des organisations d'un compte inconnu
    Alors Prospecting ne répond aucun fait
    Quand un autre contexte demande à Prospecting 10 faits des échanges d'un compte inconnu
    Alors Prospecting ne répond aucun fait
    Quand un autre contexte demande à PersonalData 10 faits des contacts d'un compte inconnu
    Alors PersonalData ne répond aucun fait

  Scénario: Booking dit ce qu'on a réservé et qui l'a réservé, et tait un rendez-vous inconnu
    Soit le client Google "client-solean" de secret "secret-solean" sert "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et mon compte Google est connecté
    Et nous sommes le 23/11/2026 à 10h00
    Et je règle mes plages :
      | jour  | de    | à     |
      | mardi | 09:00 | 09:30 |
    Et ma prise de rendez-vous est active
    Et quelqu'un vient de réserver par mon lien public le 24/11/2026 à 9h00
    Quand un autre contexte demande à Booking ce rendez-vous et un rendez-vous inconnu
    Alors Booking répond un appel avec moi le 24/11/2026 à 9h00 pour 15 minutes, réservé par "Ada Lovelace" au +33612345678 et à quelqu.un@exemple.fr
