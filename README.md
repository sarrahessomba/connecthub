# 🎨 ConnectHub - Réseau social pour artistes

ConnectHub est une plateforme sociale moderne dédiée aux artistes. Elle permet de partager des œuvres (images, vidéos, liens, sondages), de créer des communautés, d'échanger en privé, de recevoir des notifications et de modérer les contenus.

## ✨ Fonctionnalités

### 👤 Utilisateurs
- Inscription / Connexion / Déconnexion (mot de passe hashé)
- Modification du profil : pseudo, bio, avatar, centres d’intérêt
- Consultation du profil d’un autre utilisateur
- Suivre / Ne plus suivre un utilisateur

### 📝 Publications
- Créer une publication : texte + optionnellement image, vidéo, lien ou sondage
- Feed personnel (uniquement les publications hors communauté)
- Feed spécifique à une communauté
- Modification et suppression d’une publication (uniquement par l’auteur)

### ❤️ Interactions
- Liker / Unliker une publication (compteur mis à jour)
- Commenter une publication (affichage en temps réel)
- Partager une publication (copie du lien)
- Bookmark (enregistrer) une publication

### 👥 Communautés
- Créer une communauté (nom, description, thème de couleur)
- Rejoindre / Quitter une communauté
- Publier dans une communauté (texte, image, vidéo, lien, sondage)
- Un administrateur peut modifier les informations de la communauté et la supprimer
- Si l’admin est le seul membre, il peut supprimer la communauté (pas quitter)

### 💬 Messagerie
- Envoyer un message privé à un autre utilisateur (recherche par pseudo)
- Consulter la liste des conversations
- Afficher l’historique des messages
- Créer des conversations de groupe
- Mise à jour automatique des messages

### 🔔 Notifications
- Notification lorsqu’un utilisateur like ou commente votre publication
- Page dédiée avec liste des notifications
- Marquer une notification comme lue

### 🛡️ Modération (minimale)
- Un utilisateur peut signaler une publication ou un commentaire (motif)
- Un administrateur peut consulter les signalements et supprimer le contenu signalé

### 🔍 Recherche
- Barre de recherche : trouver des utilisateurs (par pseudo) et des communautés (par nom)

### 🎨 Design
- Interface moderne inspirée d’Instagram (fond clair, cartes arrondies, sidebar rétrécissable)
- Navigation en haut avec icônes
- Responsive (mobile friendly)

## 🛠️ Technologies utilisées

- **Frontend** : HTML5 / CSS3 / JavaScript (React 18 sans JSX précompilé – Babel in-browser)
- **Backend** : PHP 7+ (API REST avec `router.php`)
- **Base de données** : MySQL (MySQLi)
- **Serveur local** : MAMP / XAMPP
- **Bibliothèques** : Font Awesome 6, React Router DOM (pour la navigation, optionnel)

## 📦 Installation

### Prérequis
- MAMP ou XAMPP (Apache + MySQL + PHP 7+)
- Navigateur web moderne (Chrome, Firefox, Edge)

### Étapes

1. **Clone ou télécharge** le projet dans le dossier `htdocs` (MAMP) ou `www` (XAMPP).# connecthub
Projet connecthub REM
