# language: fr
Fonctionnalité: Les contacts à rattacher
  En tant que prospecteur
  Je veux qu'une personne qui a pris rendez-vous avec moi existe sans organisation jusqu'à ce que je la rattache
  Afin de garder ce rendez-vous en attendant de savoir pour qui elle travaille

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et nous sommes le 18/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Un contact à rattacher existe sans organisation, avec son échange planifié
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Alors "Ada Lovelace" est un contact à rattacher, effacée le 20/12/2026 sans rattachement
    Et "ACME" n'a pas de contact
    Quand je consulte mon calendrier du 20/11/2026 au 20/11/2026
    Alors il montre la visio avec "Ada Lovelace" le 20/11/2026 à 14h00, pas en retard

  Scénario: Un nouveau rendez-vous repousse l'effacement
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je planifie un appel avec "Ada Lovelace" le 10/12/2026 à 9h00
    Alors "Ada Lovelace" est un contact à rattacher, effacée le 09/01/2027 sans rattachement

  Scénario: Un rendez-vous plus proche ne rapproche pas l'effacement
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je planifie un appel avec "Ada Lovelace" le 19/11/2026 à 9h00
    Alors "Ada Lovelace" est un contact à rattacher, effacée le 20/12/2026 sans rattachement

  Scénario: Une action à faire pour un contact à rattacher l'attend avec lui
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je note à faire pour "Ada Lovelace" "Envoyer la proposition" pour le 25/11/2026
    Alors les actions à faire de "Ada Lovelace" sont :
      | texte                   |
      | Envoyer la proposition  |

  Scénario: Seul le prospecteur qui a le rendez-vous planifie avec un contact à rattacher
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand je planifie un appel avec "Ada Lovelace" le 25/11/2026 à 9h00
    Alors c'est refusé : "Until a contact is attached, only the member who met them can deal with them"

  Scénario: Le prospecteur qui a le rendez-vous note et planifie depuis la fiche d'un contact à rattacher
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je regarde ce que je peux faire de la timeline de "Ada Lovelace"
    Alors je peux sur la timeline :
      | consulter la timeline |
      | noter un échange      |
      | planifier un échange  |

  Scénario: Un autre prospecteur lit la timeline d'un contact à rattacher, sans rien y noter ni planifier
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand je regarde ce que je peux faire de la timeline de "Ada Lovelace"
    Alors je peux sur la timeline :
      | consulter la timeline |

  Scénario: La timeline d'un contact à rattacher ne propose à un autre prospecteur ni de noter ni de planifier
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand je consulte la timeline de "Ada Lovelace"
    Alors je peux sur la timeline :
      | consulter la timeline |

  Scénario: Un autre prospecteur ne note rien avec un contact à rattacher
    Soit "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Et je suis connecté en tant qu'un autre prospecteur de "SOLEAN IT"
    Quand je note à faire pour "Ada Lovelace" "Envoyer la proposition" pour le 25/11/2026
    Alors c'est refusé : "Until a contact is attached, only the member who met them can deal with them"

