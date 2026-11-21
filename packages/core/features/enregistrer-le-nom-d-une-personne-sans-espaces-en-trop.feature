# language: fr
Fonctionnalité: Le nom d'une personne est enregistré sans espaces en trop
  En tant que prospecteur
  Je veux que le nom d'un contact saisi avec des espaces en trop soit enregistré propre
  Afin qu'il s'affiche, se trie et se cherche comme il s'écrit

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Acme" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Les espaces au début et à la fin sont retirés
    Quand j'enregistre le contact "Ada" pour "Acme" en le nommant "  Ada  " "  Lovelace  "
    Alors le contact "Ada" s'appelle "Ada" "Lovelace"

  Scénario: Les espaces répétés entre les mots n'en font plus qu'un
    Quand j'enregistre le contact "Jean Pierre" pour "Acme" en le nommant "Jean   Pierre" "De    la Tour"
    Alors le contact "Jean Pierre" s'appelle "Jean Pierre" "De la Tour"

  Scénario: Renommer un contact nettoie aussi son nom
    Soit le contact "Ada Lovelace" de "Acme"
    Quand je renomme le contact "Ada Lovelace" en "  Ada " "  Byron   King "
    Alors le contact "Ada Lovelace" s'appelle "Ada" "Byron King"

  Scénario: Un nom fait seulement d'espaces vaut un nom absent
    Quand j'enregistre le contact "Lovelace" pour "Acme" en le nommant "   " "Lovelace"
    Alors le contact "Lovelace" n'a pas de prénom et s'appelle "Lovelace"
