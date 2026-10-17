# language: fr
Fonctionnalité: Ouvrir un compte
  En tant qu'organisation qui découvre Prospecter
  Je veux ouvrir mon compte, dont je suis le fondateur
  Afin d'y prospecter et d'y gérer les données personnelles de mes contacts

  Scénario: J'ouvre le compte de mon organisation, avec tous les droits
    Quand j'ouvre le compte de mon organisation :
      | raison sociale  | SOLEAN IT |
      | forme juridique | SARL      |
    Alors je vois mon compte :
      | raison sociale de mon organisation  | SOLEAN IT                                                  |
      | forme juridique de mon organisation | SARL                                                       |
      | mes rôles                           | ROLE_USER, ROLE_PROSPECTOR, ROLE_PERSONAL_DATA, ROLE_ADMIN |

  Scénario: Mon profil est recueilli, vide, pour que je le complète
    Quand j'ouvre le compte de mon organisation :
      | raison sociale | SOLEAN IT |
    Alors je vois mon compte :
      | prénom |  |
      | nom    |  |
      | e-mail |  |
    Et je peux sur mon profil :
      | changer le nom          |
      | changer la fonction     |
      | changer les coordonnées |
      | changer la photo        |

  Scénario: Une organisation sans raison sociale n'ouvre pas de compte
    Quand j'ouvre le compte de mon organisation :
      | forme juridique | SARL |
    Alors c'est refusé : "An account needs the corporate name of its organization"
