# language: fr
Fonctionnalité: Un contact porte les tags de ses organisations et ceux de ses coordonnées
  En tant que prospecteur
  Je veux qu'un contact porte les tags de ses organisations, et #EMAIL, #PHONE, #SMS, #LINKEDIN tout seuls
  Afin de taguer une organisation une seule fois et de savoir d'un coup d'œil comment joindre chacun

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits

  Scénario: Un contact porte les tags de son organisation
    Quand je tague "ACME" avec "#ESN"
    Alors "Ada Lovelace" porte "#ESN", hérité de "ACME"

  Scénario: Retirer le tag de l'organisation le retire de ses contacts
    Soit "ACME" est tagué "#ESN"
    Quand je retire le tag "#ESN" de "ACME"
    Alors "Ada Lovelace" ne porte plus "#ESN"

  Scénario: Un contact rattaché à une organisation prend ses tags, retiré il les perd
    Soit "Globex" est tagué "#EDITEUR"
    Quand je rattache le contact "Ada Lovelace" à "Globex"
    Alors "Ada Lovelace" porte "#EDITEUR", hérité de "Globex"
    Quand je retire le contact "Ada Lovelace" de "Globex"
    Alors "Ada Lovelace" ne porte plus "#EDITEUR"

  Scénario: Un tag hérité de deux organisations les nomme toutes deux
    Soit "ACME" est tagué "#ESN"
    Et "Globex" est tagué "#ESN"
    Quand je rattache le contact "Ada Lovelace" à "Globex"
    Alors "Ada Lovelace" porte "#ESN", hérité de "ACME" et "Globex"

  Scénario: Un tag porté par le contact lui-même reste quand l'organisation le perd
    Soit "ACME" est tagué "#PHP"
    Et "Ada Lovelace" est tagué "#PHP"
    Quand je retire le tag "#PHP" de "ACME"
    Alors "Ada Lovelace" porte "#PHP", le sien

  Scénario: Un tag porté par le contact et hérité n'est montré qu'une fois, comme le sien
    Soit "ACME" est tagué "#PHP"
    Quand je tague "Ada Lovelace" avec "#PHP"
    Alors "Ada Lovelace" porte "#PHP", le sien

  Plan du scénario: Les tags automatiques suivent les coordonnées
    Quand je change les coordonnées de "Ada Lovelace" :
      | <coordonnée> | <valeur> |
    Alors les tags automatiques de "Ada Lovelace" sont "<tags>"

    Exemples:
      | coordonnée | valeur                                   | tags        |
      | e-mail     | ada@acme.test                            | #EMAIL      |
      | téléphone  | 03 88 11 22 33                           | #PHONE      |
      | téléphone  | 06 12 34 56 78                           | #PHONE #SMS |
      | liens      | https://www.linkedin.com/in/ada-lovelace | #LINKEDIN   |

  Scénario: Effacer l'e-mail retire #EMAIL
    Soit je change les coordonnées de "Ada Lovelace" :
      | e-mail | ada@acme.test |
    Quand je change les coordonnées de "Ada Lovelace" :
      | e-mail | |
    Alors "Ada Lovelace" ne porte plus "#EMAIL"

  Scénario: Les tags se reconstruisent depuis l'état des contextes
    Soit "ACME" est tagué "#ESN"
    Et je change les coordonnées de "Ada Lovelace" :
      | e-mail | ada@acme.test |
    Et les tags calculés de "SOLEAN IT" ont été perdus
    Quand les tags calculés sont reconstruits
    Alors "Ada Lovelace" porte "#ESN", hérité de "ACME"
    Et les tags automatiques de "Ada Lovelace" sont "#EMAIL"

  Scénario: Les tags d'un contact d'un autre compte sont introuvables
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech"
    Quand je consulte les tags de "Linus Torvalds"
    Alors c'est introuvable

  Scénario: Sans le droit aux données personnelles, je vois les tags de mes contacts
    Soit "ACME" est tagué "#ESN"
    Et je change les coordonnées de "Ada Lovelace" :
      | e-mail | ada@acme.test |
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Alors "Ada Lovelace" porte "#ESN", hérité de "ACME"
    Et les tags automatiques de "Ada Lovelace" sont "#EMAIL"
