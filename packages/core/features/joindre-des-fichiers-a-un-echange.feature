# language: fr
Fonctionnalité: Joindre des fichiers à un échange
  En tant que prospecteur
  Je veux joindre à un échange les fichiers que j'y ai reçus ou envoyés
  Afin de les retrouver depuis son détail, sans les chercher dans ma boîte mail

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un e-mail reçu de "Ada Lovelace" à 14h30 : "Voici notre cahier des charges"
    Et je suis connecté en tant que "Michel"

  Scénario: Les fichiers joints sont listés dans le détail et se téléchargent
    Quand je joins "cahier-des-charges.pdf" et "planning.xlsx" à cet échange
    Alors le détail de l'échange liste :
      | cahier-des-charges.pdf |
      | planning.xlsx          |
    Et je télécharge "cahier-des-charges.pdf" tel que je l'ai joint
    Et la timeline de "Ada Lovelace" montre que l'échange a 2 pièces jointes

  Scénario: Je joins des fichiers en notant l'échange
    Quand je note un e-mail reçu de "Ada Lovelace" avec "devis.pdf" joint
    Alors le détail de l'échange liste :
      | devis.pdf |

  Scénario: Un fichier trop lourd est refusé
    Quand je joins un fichier "video.mp4" de 11 Mo à cet échange
    Alors c'est refusé : "An attachment weighs 10 MB at most"

  Scénario: Un fichier de 10 Mo est accepté
    Quand je joins un fichier "photos.zip" de 10 Mo à cet échange
    Alors le détail de l'échange liste :
      | photos.zip |

  Scénario: Un échange a 10 pièces jointes au plus
    Soit 10 fichiers sont joints à cet échange
    Quand je joins "un-de-trop.pdf" à cet échange
    Alors c'est refusé : "An exchange holds 10 attachments at most"

  Scénario: Un envoi est joint une seule fois
    Soit "planning.xlsx" est joint à cet échange
    Quand je joins encore le même envoi à cet échange
    Alors c'est introuvable

  Scénario: Sans le droit aux données personnelles, je ne joins pas de fichier
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je joins "planning.xlsx" à cet échange
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: Une organisation qu'on arrête de prospecter emporte les pièces jointes de ses échanges
    Soit "planning.xlsx" est joint à cet échange
    Quand j'arrête de prospecter "ACME"
    Alors le fichier "planning.xlsx" n'est plus téléchargeable
