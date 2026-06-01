-- --------------------------------------------------------
-- Base de données : `connecthub`
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `connecthub`;
USE `connecthub`;

-- --------------------------------------------------------
-- Table `utilisateurs`
-- --------------------------------------------------------
CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pseudo` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `role` enum('user','admin') DEFAULT 'user',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pseudo` (`pseudo`),
  UNIQUE KEY `email` (`email`)
);

-- --------------------------------------------------------
-- Table `interets`
-- --------------------------------------------------------
CREATE TABLE `interets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
);

-- --------------------------------------------------------
-- Table `user_interets`
-- --------------------------------------------------------
CREATE TABLE `user_interets` (
  `user_id` int(11) NOT NULL,
  `interet_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`interet_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interet_id`) REFERENCES `interets`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `communautes`
-- --------------------------------------------------------
CREATE TABLE `communautes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `couleur_theme` varchar(7) DEFAULT '#4a90e2',
  `admin_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`admin_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `membres_communautes`
-- --------------------------------------------------------
CREATE TABLE `membres_communautes` (
  `user_id` int(11) NOT NULL,
  `communauté_id` int(11) NOT NULL,
  `role` enum('membre','moderateur','admin') DEFAULT 'membre',
  `date_adhésion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`communauté_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`communauté_id`) REFERENCES `communautes`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `publications`
-- --------------------------------------------------------
CREATE TABLE `publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contenu_texte` text DEFAULT NULL,
  `fichier_media` varchar(255) DEFAULT NULL,
  `type_media` enum('image','video','lien','sondage','texte') DEFAULT 'texte',
  `lien` varchar(255) DEFAULT NULL,
  `communauté_id` int(11) DEFAULT NULL,
  `date_publication` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`communauté_id`) REFERENCES `communautes`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `sondages`
-- --------------------------------------------------------
CREATE TABLE `sondages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `options_sondage`
-- --------------------------------------------------------
CREATE TABLE `options_sondage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sondage_id` int(11) NOT NULL,
  `option_texte` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`sondage_id`) REFERENCES `sondages`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `votes_sondage`
-- --------------------------------------------------------
CREATE TABLE `votes_sondage` (
  `user_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`option_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`option_id`) REFERENCES `options_sondage`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `likes`
-- --------------------------------------------------------
CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `date_like` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_publication` (`user_id`,`publication_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `commentaires`
-- --------------------------------------------------------
CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_commentaire` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `bookmarks`
-- --------------------------------------------------------
CREATE TABLE `bookmarks` (
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) NOT NULL,
  `date_bookmark` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`publication_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `conversations`
-- --------------------------------------------------------
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT 'private',
  `nom_groupe` varchar(100) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- --------------------------------------------------------
-- Table `participants_conversation`
-- --------------------------------------------------------
CREATE TABLE `participants_conversation` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`conversation_id`,`user_id`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `messages`
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  `lu` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `notifications`
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('like','comment','follow','message') NOT NULL,
  `contenu` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `lu` tinyint(1) DEFAULT '0',
  `date_notification` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `signalements`
-- --------------------------------------------------------
CREATE TABLE `signalements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `publication_id` int(11) DEFAULT NULL,
  `commentaire_id` int(11) DEFAULT NULL,
  `motif` varchar(255) NOT NULL,
  `date_signalement` datetime DEFAULT CURRENT_TIMESTAMP,
  `traite` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`publication_id`) REFERENCES `publications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`commentaire_id`) REFERENCES `commentaires`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `abonnements`
-- --------------------------------------------------------
CREATE TABLE `abonnements` (
  `abonne_id` int(11) NOT NULL,
  `abonné_id` int(11) NOT NULL,
  `date_abonnement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`abonne_id`,`abonné_id`),
  FOREIGN KEY (`abonne_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`abonné_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Table `blocages`
-- --------------------------------------------------------
CREATE TABLE `blocages` (
  `user_id` int(11) NOT NULL,
  `blocked_user_id` int(11) NOT NULL,
  `date_blocage` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`blocked_user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`blocked_user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Insertion des centres d'intérêt par défaut
-- --------------------------------------------------------
INSERT INTO `interets` (`nom`) VALUES
('Peinture'), ('Photographie'), ('Musique'), ('Danse'), ('Théâtre'),
('Écriture'), ('Sculpture'), ('Gravure'), ('Calligraphie'), ('Art numérique');

-- --------------------------------------------------------
-- Insertion d'un utilisateur admin par défaut (pseudo: admin, email: admin@connecthub.fr, mot de passe: admin123)
-- Le mot de passe est hashé avec bcrypt (password_hash('admin123', PASSWORD_DEFAULT))
-- Pour réinitialiser, utilisez un hash généré par votre code PHP.
-- --------------------------------------------------------
INSERT INTO `utilisateurs` (`pseudo`, `email`, `mot_de_passe`, `role`) VALUES
('admin', 'admin@connecthub.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- --------------------------------------------------------
-- Fin du script
-- --------------------------------------------------------
