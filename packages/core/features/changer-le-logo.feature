# language: fr
Fonctionnalité: Changer le logo d'une organisation
  En tant que prospecteur
  Je veux afficher le logo d'une organisation
  Afin de la reconnaître d'un coup d'œil, sur la liste comme sur la carte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations

  Scénario: Le logo envoyé est enregistré dans le stockage
    Soit j'ai une image "acme.png" de 2048 octets
    Quand je change le logo de "ACME" avec "acme.png"
    Alors l'organisation "ACME" est enregistrée avec :
      | logo | acme.png (png, 2048 octets) |
    Et le stockage contient "acme.png"

  Scénario: Un nouveau logo remplace l'ancien
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | logo | globex.png |
    Et j'ai une image "globex-2026.webp" de 512 octets
    Quand je change le logo de "Globex" avec "globex-2026.webp"
    Alors l'organisation "Globex" est enregistrée avec :
      | logo | globex-2026.webp (webp, 512 octets) |

  Scénario: L'organisation d'une autre organisation est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et j'ai une image "initech.png" de 1024 octets
    Quand je change le logo de "Initech" avec "initech.png"
    Alors c'est introuvable
