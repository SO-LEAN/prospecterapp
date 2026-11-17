# language: fr
Fonctionnalité: Les tags géographiques
  En tant que prospecteur
  Je veux que mes organisations et leurs contacts portent tout seuls la grande ville des alentours, le département et la région de leur adresse
  Afin de cibler par exemple « #LINKEDIN #STRASBOURG #ESN »

  Contexte:
    Soit le géocodeur connaît les communes :
      | lieu                             | commune |
      | Strasbourg                       | 67482   |
      | Illkirch-Graffenstaden           | 67218   |
      | Boulogne-Billancourt             | 92012   |
      | Paris                            | 75102   |
      | Toulouse                         | 31555   |
      | Wissembourg                      | 67544   |
      | Lyon                             | 69382   |
      | Rue de la Loi 16, 1000 Bruxelles | 44109   |
    Et l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits

  Scénario: Une organisation en banlieue prend le nom de la grande ville
    Quand je déménage "ACME" au "12 rue de la Haye, 67400 Illkirch-Graffenstaden"
    Alors "ACME" est située "#STRASBOURG #67 #GRAND_EST"
    Et "Ada Lovelace" porte "#STRASBOURG #67 #GRAND_EST"

  Scénario: La banlieue parisienne, c'est Paris
    Quand je déménage "ACME" au "1 place Bernard Palissy, 92100 Boulogne-Billancourt"
    Alors "ACME" est située "#PARIS #92 #ILE_DE_FRANCE"

  Scénario: Déménager change les tags
    Soit "ACME" est située à Illkirch-Graffenstaden
    Quand je déménage "ACME" au "1 place du Capitole, 31000 Toulouse"
    Alors "Ada Lovelace" porte "#TOULOUSE #31 #OCCITANIE"
    Et "Ada Lovelace" ne porte plus "#STRASBOURG"

  Scénario: Un contact de deux organisations porte les tags des deux
    Soit "ACME" est située à Strasbourg
    Et l'organisation "Globex" appartient à "SOLEAN IT", située à Paris
    Quand je rattache le contact "Ada Lovelace" à "Globex"
    Alors "Ada Lovelace" porte "#STRASBOURG" et "#PARIS"
    Et "Ada Lovelace" porte "#STRASBOURG", hérité de "ACME"

  Scénario: Une adresse que le géocodeur ne trouve pas ne donne aucun tag géographique
    Soit "ACME" est située à Strasbourg
    Quand je déménage "ACME" au "nulle part"
    Alors "ACME" n'a aucun tag géographique
    Et "Ada Lovelace" ne porte plus "#STRASBOURG"

  Scénario: Une adresse hors de France ne donne aucun tag géographique
    Quand je déménage "ACME" au "Grand-Place 1, 1000 Bruxelles, BE"
    Alors "ACME" n'a aucun tag géographique

  Scénario: Une adresse d'un autre pays ne donne aucun tag géographique, même si le géocodeur la trouve en France
    Quand je déménage "ACME" au "Rue de la Loi 16, 1000 Bruxelles, BE"
    Alors "ACME" n'a aucun tag géographique

  Scénario: Une adresse d'outre-mer est française
    Soit le géocodeur connaît les communes :
      | lieu           | commune |
      | Fort-de-France | 97209   |
    Quand je déménage "ACME" au "Rue Victor Hugo, 97200 Fort-de-France, MQ"
    Alors "ACME" est située "#FORT_DE_FRANCE #972 #MARTINIQUE"

  Scénario: Hors d'une grande aire, seuls le département et la région
    Quand je déménage "ACME" au "1 place du Marché aux Choux, 67160 Wissembourg"
    Alors "ACME" est située "#67 #GRAND_EST"

  Scénario: Un arrondissement de Paris, Lyon ou Marseille est sa ville
    Quand je déménage "ACME" au "1 place Bellecour, 69002 Lyon"
    Alors "ACME" est située "#LYON #69 #AUVERGNE_RHONE_ALPES"

  Scénario: Une commune fusionnée depuis garde ses tags
    Soit "ACME" a été géocodée à Saint-Thibaut (02695), commune fusionnée depuis 2020
    Quand les tags calculés sont reconstruits
    Alors "ACME" est située "#REIMS #02 #HAUTS_DE_FRANCE"

  Scénario: Les tags géographiques se reconstruisent
    Soit "ACME" est située à Strasbourg
    Et les tags calculés de "SOLEAN IT" ont été perdus
    Quand les tags calculés sont reconstruits
    Alors "Ada Lovelace" porte "#STRASBOURG #67 #GRAND_EST"

  Scénario: Une organisation située avant que son code commune soit gardé le reçoit, une fois
    Soit "ACME" a été située à Strasbourg sans son code commune
    Quand les organisations situées sans code commune sont géocodées à nouveau
    Alors "ACME" est située "#STRASBOURG #67 #GRAND_EST"
    Et "Ada Lovelace" porte "#STRASBOURG #67 #GRAND_EST"
    Quand les organisations situées sans code commune sont géocodées à nouveau
    Alors aucune organisation n'a été géocodée à nouveau

  Scénario: Le nom d'une grande aire est un tag automatique
    Quand je tague "ACME" avec "#STRASBOURG"
    Alors c'est refusé : "The tag #STRASBOURG is set automatically"
