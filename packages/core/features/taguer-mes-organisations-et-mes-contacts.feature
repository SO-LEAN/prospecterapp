# language: fr
Fonctionnalité: Taguer mes organisations et mes contacts
  En tant que prospecteur
  Je veux poser des tags sur mes organisations et sur mes contacts, prédéfinis ou les miens
  Afin de retrouver ensuite les contacts qui les portent et préparer des campagnes

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Je tague une organisation
    Quand je tague "ACME" avec "#ESN" et "#APPORTEUR"
    Alors les tags de "ACME" sont "#APPORTEUR #ESN"

  Scénario: Je tague un contact
    Quand je tague "Ada Lovelace" avec "#CLIENT"
    Alors les tags de "Ada Lovelace" sont "#CLIENT"

  Scénario: Un tag déjà porté ne compte qu'une fois
    Soit "ACME" est tagué "#ESN"
    Quand je tague "ACME" avec "#ESN" et "esn"
    Alors les tags de "ACME" sont "#ESN"

  Scénario: Un tag est écrit sous une seule forme
    Quand je tague "ACME" avec "éditeur logiciel"
    Alors les tags de "ACME" sont "#EDITEUR_LOGICIEL"

  Scénario: Un tag sans lettre ni chiffre est refusé
    Quand je tague "ACME" avec "#!?"
    Alors c'est refusé : "A tag holds 1 to 30 capital letters, digits or underscores"

  Scénario: Un tag de plus de 30 caractères est refusé
    Quand je tague "ACME" avec "#UN_TAG_BIEN_TROP_LONG_POUR_ETRE_LU"
    Alors c'est refusé : "A tag holds 1 to 30 capital letters, digits or underscores"

  Scénario: Je retire un tag
    Soit "ACME" est tagué "#ESN"
    Et "ACME" est tagué "#PHP"
    Quand je retire le tag "#PHP" de "ACME"
    Alors les tags de "ACME" sont "#ESN"

  Scénario: Un tag que l'organisation ne porte pas ne se retire pas
    Soit "ACME" est tagué "#ESN"
    Quand je retire le tag "#PHP" de "ACME"
    Alors c'est introuvable

  Scénario: Rien ne se retire de ce qui n'a jamais été tagué
    Quand je retire le tag "#CLIENT" de "Ada Lovelace"
    Alors c'est introuvable

  Scénario: Je crée mon propre tag, il m'est ensuite proposé
    Quand je tague "ACME" avec "#SALON_2026"
    Alors "#SALON_2026" fait partie des tags proposés de mon compte

  Scénario: Un tag perso que plus rien ne porte n'est plus proposé
    Soit "ACME" est tagué "#SALON_2026"
    Quand je retire le tag "#SALON_2026" de "ACME"
    Alors "#SALON_2026" ne fait plus partie des tags proposés de mon compte

  Scénario: Les tags prédéfinis sont toujours proposés, par famille
    Alors les tags proposés de mon compte sont :
      | famille                | tags                                                                                                                                              |
      | type d'organisation    | #APPORTEUR #ESN #EDITEUR #FINAL #CABINET_RECRUTEMENT #PORTAGE #PARTENAIRE                                                                         |
      | relation avec le contact | #CLIENT #DECIDEUR #TECH #RH #ACHATS #ANCIEN_COLLEGUE #RECOMMANDE                                                                                |
      | technos                | #PHP #SYMFONY #LARAVEL #JAVASCRIPT #TYPESCRIPT #REACT #VUE #ANGULAR #NODE #JAVA #DOTNET #PYTHON #GO #AWS #AZURE #GCP #DOCKER #KUBERNETES #DEVOPS #DATA #IA |
      | mes tags               |                                                                                                                                                   |

  Scénario: Un tag prédéfini posé n'est pas proposé une seconde fois parmi les miens
    Soit "ACME" est tagué "#ESN"
    Et "Ada Lovelace" est tagué "#SALON_2026"
    Alors les tags proposés de mon compte sont, parmi les miens, "#SALON_2026"

  Plan du scénario: Un tag automatique ne se pose pas à la main
    Quand je tague "Ada Lovelace" avec "<tag>"
    Alors c'est refusé : "The tag <tag> is set automatically"

    Exemples:
      | tag          |
      | #EMAIL       |
      | #PHONE       |
      | #SMS         |
      | #LINKEDIN    |
      | #67          |
      | #2A          |
      | #971         |
      | #GRAND_EST   |

  Scénario: Une organisation qu'on arrête de prospecter emporte ses tags
    Soit "ACME" est tagué "#ESN"
    Et "Ada Lovelace" est tagué "#CLIENT"
    Quand j'arrête de prospecter "ACME"
    Alors aucun tag n'est gardé pour "ACME" ni pour "Ada Lovelace"

  Scénario: Un contact effacé emporte ses tags
    Soit "Ada Lovelace" est tagué "#CLIENT"
    Quand j'efface la fiche de "Ada Lovelace"
    Alors aucun tag n'est gardé pour "Ada Lovelace"

  Scénario: Les tags d'un autre compte ne me sont pas proposés
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et un prospecteur de "Concurrent" a créé le tag "#SECRET"
    Alors "#SECRET" ne fait pas partie des tags proposés de mon compte

  Scénario: Une organisation d'un autre compte ne se tague pas
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Quand je tague "Initech" avec "#ESN"
    Alors c'est introuvable

  Scénario: Un contact d'un autre compte ne se tague pas
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech"
    Quand je tague "Linus Torvalds" avec "#CLIENT"
    Alors c'est introuvable

  Scénario: Sans le droit aux données personnelles, je tague mes contacts
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je tague "Ada Lovelace" avec "#DECIDEUR"
    Alors les tags de "Ada Lovelace" sont "#DECIDEUR"
