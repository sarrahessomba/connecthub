<?php
header('Content-Type: application/json');
session_start();
require_once '../../config/db.php';

$public_actions = ['login', 'register', 'forgot_password'];
if (!isset($_SESSION['user_id']) && !in_array($_GET['action'] ?? '', $public_actions)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

try {
    switch ($action) {
        case 'register':
            $pseudo = $_POST['pseudo'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $pseudo, $email, $password);
            if ($stmt->execute()) echo json_encode(['success' => true]);
            else echo json_encode(['error' => 'Pseudo ou email déjà utilisé']);
            break;
        case 'login':
            $identifiant = $_POST['identifiant'] ?? '';
            $password = $_POST['password'] ?? '';
            $stmt = $conn->prepare("SELECT id, pseudo, mot_de_passe, role FROM utilisateurs WHERE pseudo=? OR email=?");
            $stmt->bind_param("ss", $identifiant, $identifiant);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['pseudo'] = $user['pseudo'];
                $_SESSION['role'] = $user['role'];
                echo json_encode(['success' => true, 'role' => $user['role']]);
            } else echo json_encode(['error' => 'Identifiant ou mot de passe incorrect']);
            break;
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
        case 'forgot_password':
            $email = $_POST['email'] ?? '';
            $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) echo json_encode(['success' => true, 'message' => 'Email de réinitialisation envoyé']);
            else echo json_encode(['error' => 'Email non trouvé']);
            break;
        case 'get_profile':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : $userId;
            $stmt = $conn->prepare("SELECT id, pseudo, bio, avatar, role FROM utilisateurs WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            if ($userId && $id != $userId) {
                $check = $conn->query("SELECT 1 FROM abonnements WHERE abonne_id=$userId AND abonné_id=$id");
                $profile['is_following'] = $check->num_rows > 0;
            } else $profile['is_following'] = false;
            echo json_encode($profile);
            break;
        case 'update_profile':
            $pseudo = $_POST['pseudo'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $interets = isset($_POST['interets']) ? json_decode($_POST['interets'], true) : [];
            $avatar = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], "../../uploads/avatars/$filename");
                $avatar = $filename;
            }
            if ($avatar) {
                $stmt = $conn->prepare("UPDATE utilisateurs SET pseudo=?, bio=?, avatar=? WHERE id=?");
                $stmt->bind_param("sssi", $pseudo, $bio, $avatar, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE utilisateurs SET pseudo=?, bio=? WHERE id=?");
                $stmt->bind_param("ssi", $pseudo, $bio, $userId);
            }
            $stmt->execute();
            $conn->query("DELETE FROM user_interets WHERE user_id=$userId");
            if (!empty($interets)) {
                $stmt2 = $conn->prepare("INSERT INTO user_interets (user_id, interet_id) VALUES (?, ?)");
                foreach ($interets as $intId) {
                    $stmt2->bind_param("ii", $userId, $intId);
                    $stmt2->execute();
                }
            }
            echo json_encode(['success' => true]);
            break;
        case 'follow':
            $followId = (int)$_POST['follow_id'];
            $conn->query("INSERT IGNORE INTO abonnements (abonne_id, abonné_id) VALUES ($userId, $followId)");
            echo json_encode(['success' => true]);
            break;
        case 'unfollow':
            $followId = (int)$_POST['follow_id'];
            $conn->query("DELETE FROM abonnements WHERE abonne_id=$userId AND abonné_id=$followId");
            echo json_encode(['success' => true]);
            break;
        case 'get_following':
            $result = $conn->query("SELECT u.id, u.pseudo, u.avatar FROM abonnements a JOIN utilisateurs u ON a.abonné_id = u.id WHERE a.abonne_id = $userId");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_interets':
            $result = $conn->query("SELECT id, nom FROM interets ORDER BY nom");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_user_interets':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : $userId;
            $result = $conn->query("SELECT interet_id FROM user_interets WHERE user_id=$id");
            $interets = [];
            while ($row = $result->fetch_assoc()) $interets[] = $row['interet_id'];
            echo json_encode($interets);
            break;
        case 'create_post':
            $contenu = $_POST['contenu'] ?? '';
            $type = $_POST['type'] ?? 'texte';
            $lien = $_POST['lien'] ?? null;
            $communauteId = !empty($_POST['communaute_id']) ? (int)$_POST['communaute_id'] : null;
            $fichier = null;
            if (($type == 'image' || $type == 'video') && isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
                $fichier = 'post_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['media']['tmp_name'], "../../uploads/medias/$fichier");
            }
            $stmt = $conn->prepare("INSERT INTO publications (user_id, contenu_texte, fichier_media, type_media, lien, communauté_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $userId, $contenu, $fichier, $type, $lien, $communauteId);
            $stmt->execute();
            $pubId = $stmt->insert_id;
            if ($type == 'sondage') {
                $question = $_POST['sondage_question'] ?? '';
                $options = json_decode($_POST['sondage_options'] ?? '[]', true);
                $conn->query("INSERT INTO sondages (publication_id, question) VALUES ($pubId, '$question')");
                $sondageId = $conn->insert_id;
                foreach ($options as $opt) $conn->query("INSERT INTO options_sondage (sondage_id, option_texte) VALUES ($sondageId, '$opt')");
            }
            echo json_encode(['success' => true, 'post_id' => $pubId]);
            break;
        case 'get_feed':
            $type = $_GET['type'] ?? 'personnel';
            $communauteId = isset($_GET['communaute_id']) ? (int)$_GET['communaute_id'] : 0;
            $bloqueSql = "AND NOT EXISTS (SELECT 1 FROM blocages WHERE user_id = $userId AND blocked_user_id = p.user_id)";
            if ($type == 'personnel') {
                $sql = "SELECT p.*, u.pseudo, u.avatar,
                        (SELECT COUNT(*) FROM likes WHERE publication_id=p.id) as likes,
                        (SELECT COUNT(*) FROM commentaires WHERE publication_id=p.id) as commentaires,
                        (SELECT COUNT(*) FROM bookmarks WHERE publication_id=p.id AND user_id=$userId) as bookmarked
                        FROM publications p JOIN utilisateurs u ON p.user_id = u.id
                        WHERE p.communauté_id IS NULL $bloqueSql
                        ORDER BY p.date_publication DESC";
            } else {
                $sql = "SELECT p.*, u.pseudo, u.avatar,
                        (SELECT COUNT(*) FROM likes WHERE publication_id=p.id) as likes,
                        (SELECT COUNT(*) FROM commentaires WHERE publication_id=p.id) as commentaires,
                        (SELECT COUNT(*) FROM bookmarks WHERE publication_id=p.id AND user_id=$userId) as bookmarked
                        FROM publications p JOIN utilisateurs u ON p.user_id = u.id
                        WHERE p.communauté_id = $communauteId $bloqueSql
                        ORDER BY p.date_publication DESC";
            }
            $result = $conn->query($sql);
            $posts = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['type_media'] == 'sondage') {
                    $sondage = $conn->query("SELECT s.question, o.id as opt_id, o.option_texte,
                        (SELECT COUNT(*) FROM votes_sondage WHERE option_id=o.id) as votes
                        FROM sondages s JOIN options_sondage o ON s.id=o.sondage_id WHERE s.publication_id={$row['id']}");
                    $row['sondage'] = $sondage->fetch_all(MYSQLI_ASSOC);
                }
                $posts[] = $row;
            }
            echo json_encode($posts);
            break;
        case 'get_user_posts':
            $userIdParam = (int)$_GET['user_id'];
            $sql = "SELECT p.*, u.pseudo, u.avatar,
                    (SELECT COUNT(*) FROM likes WHERE publication_id=p.id) as likes,
                    (SELECT COUNT(*) FROM commentaires WHERE publication_id=p.id) as commentaires,
                    (SELECT COUNT(*) FROM bookmarks WHERE publication_id=p.id AND user_id=$userId) as bookmarked
                    FROM publications p JOIN utilisateurs u ON p.user_id = u.id
                    WHERE p.user_id = $userIdParam
                    ORDER BY p.date_publication DESC";
            $result = $conn->query($sql);
            $posts = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['type_media'] == 'sondage') {
                    $sondage = $conn->query("SELECT s.question, o.id as opt_id, o.option_texte,
                        (SELECT COUNT(*) FROM votes_sondage WHERE option_id=o.id) as votes
                        FROM sondages s JOIN options_sondage o ON s.id=o.sondage_id WHERE s.publication_id={$row['id']}");
                    $row['sondage'] = $sondage->fetch_all(MYSQLI_ASSOC);
                }
                $posts[] = $row;
            }
            echo json_encode($posts);
            break;
        case 'like_post':
            $postId = (int)$_POST['post_id'];
            $check = $conn->query("SELECT id FROM likes WHERE user_id=$userId AND publication_id=$postId");
            if ($check->num_rows == 0) {
                $conn->query("INSERT INTO likes (user_id, publication_id) VALUES ($userId, $postId)");
                $pubOwner = $conn->query("SELECT user_id FROM publications WHERE id=$postId")->fetch_assoc()['user_id'];
                if ($pubOwner != $userId) {
                    $conn->query("INSERT INTO notifications (user_id, type, contenu, lien) VALUES ($pubOwner, 'like', '{$_SESSION['pseudo']} a aimé votre publication', '#/accueil')");
                }
                echo json_encode(['liked' => true]);
            } else {
                $conn->query("DELETE FROM likes WHERE user_id=$userId AND publication_id=$postId");
                echo json_encode(['liked' => false]);
            }
            break;
        case 'comment':
            $postId = (int)$_POST['post_id'];
            $contenu = $_POST['contenu'] ?? '';
            $conn->query("INSERT INTO commentaires (user_id, publication_id, contenu) VALUES ($userId, $postId, '$contenu')");
            $pubOwner = $conn->query("SELECT user_id FROM publications WHERE id=$postId")->fetch_assoc()['user_id'];
            if ($pubOwner != $userId) {
                $conn->query("INSERT INTO notifications (user_id, type, contenu, lien) VALUES ($pubOwner, 'comment', '{$_SESSION['pseudo']} a commenté votre publication', '#/accueil')");
            }
            echo json_encode(['success' => true]);
            break;
        case 'get_comments':
            $postId = (int)$_GET['post_id'];
            $result = $conn->query("SELECT c.*, u.pseudo, u.avatar FROM commentaires c JOIN utilisateurs u ON c.user_id=u.id WHERE c.publication_id=$postId ORDER BY c.date_commentaire DESC");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'delete_post':
            $postId = (int)$_POST['post_id'];
            $owner = $conn->query("SELECT user_id FROM publications WHERE id=$postId")->fetch_assoc()['user_id'];
            if ($owner == $userId) {
                $conn->query("DELETE FROM publications WHERE id=$postId");
                echo json_encode(['success' => true]);
            } else echo json_encode(['error' => 'Non autorisé']);
            break;
        case 'bookmark':
        $postId = (int)$_POST['post_id'];
        $conn->query("INSERT IGNORE INTO bookmarks (user_id, publication_id) VALUES ($userId, $postId)");
        echo json_encode(['success' => true]);
        break;
    case 'unbookmark':
        $postId = (int)$_POST['post_id'];
        $conn->query("DELETE FROM bookmarks WHERE user_id=$userId AND publication_id=$postId");
        echo json_encode(['success' => true]);
        break;
        case 'vote_poll':
            $optionId = (int)$_POST['option_id'];
            $opt = $conn->query("SELECT sondage_id FROM options_sondage WHERE id=$optionId");
            if ($opt->num_rows == 0) { echo json_encode(['error' => 'Option invalide']); break; }
            $sondageId = $opt->fetch_assoc()['sondage_id'];
            $check = $conn->query("SELECT 1 FROM votes_sondage v JOIN options_sondage o ON v.option_id=o.id WHERE v.user_id=$userId AND o.sondage_id=$sondageId");
            if ($check->num_rows > 0) { echo json_encode(['error' => 'Vous avez déjà voté']); break; }
            $conn->query("INSERT INTO votes_sondage (user_id, option_id) VALUES ($userId, $optionId)");
            $count = $conn->query("SELECT COUNT(*) as cnt FROM votes_sondage WHERE option_id=$optionId")->fetch_assoc()['cnt'];
            echo json_encode(['success' => true, 'votes' => $count]);
            break;
        case 'create_communaute':
            $nom = $_POST['nom'] ?? '';
            $desc = $_POST['description'] ?? '';
            $couleur = $_POST['couleur'] ?? '#4a90e2';
            $stmt = $conn->prepare("INSERT INTO communautes (nom, description, couleur_theme, admin_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nom, $desc, $couleur, $userId);
            $stmt->execute();
            $commId = $stmt->insert_id;
            $conn->query("INSERT INTO membres_communautes (user_id, communauté_id, role) VALUES ($userId, $commId, 'admin')");
            echo json_encode(['success' => true, 'id' => $commId]);
            break;
        case 'join_communaute':
            $commId = (int)$_POST['communaute_id'];
            $conn->query("INSERT IGNORE INTO membres_communautes (user_id, communauté_id, role) VALUES ($userId, $commId, 'membre')");
            echo json_encode(['success' => true]);
            break;
        case 'leave_communaute':
            $commId = (int)$_POST['communaute_id'];
            $admin = $conn->query("SELECT admin_id FROM communautes WHERE id=$commId")->fetch_assoc()['admin_id'];
            if ($admin == $userId) {
                $nb = $conn->query("SELECT COUNT(*) as cnt FROM membres_communautes WHERE communauté_id=$commId")->fetch_assoc()['cnt'];
                if ($nb == 1) $conn->query("DELETE FROM communautes WHERE id=$commId");
                else $conn->query("DELETE FROM membres_communautes WHERE user_id=$userId AND communauté_id=$commId");
            } else $conn->query("DELETE FROM membres_communautes WHERE user_id=$userId AND communauté_id=$commId");
            echo json_encode(['success' => true]);
            break;
        case 'get_mes_communautes':
            $result = $conn->query("SELECT c.*, (c.admin_id = $userId) as is_admin FROM communautes c JOIN membres_communautes m ON c.id=m.communauté_id WHERE m.user_id=$userId");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_all_communautes':
            $result = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM membres_communautes WHERE communauté_id=c.id) as membres FROM communautes c ORDER BY c.nom");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_community_members':
            $commId = (int)$_GET['communaute_id'];
            $result = $conn->query("SELECT u.id, u.pseudo, m.role FROM membres_communautes m JOIN utilisateurs u ON m.user_id=u.id WHERE m.communauté_id=$commId");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'set_community_role':
            $userIdTarget = (int)$_POST['user_id'];
            $role = $_POST['role'];
            $commId = (int)$_POST['communaute_id'];
            $conn->query("UPDATE membres_communautes SET role='$role' WHERE user_id=$userIdTarget AND communauté_id=$commId");
            echo json_encode(['success' => true]);
            break;
        case 'send_message':
            $dest = (int)$_POST['destinataire_id'];
            $contenu = $_POST['contenu'] ?? '';
            $conv = $conn->query("SELECT c.id FROM conversations c
                JOIN participants_conversation p1 ON c.id = p1.conversation_id
                JOIN participants_conversation p2 ON c.id = p2.conversation_id
                WHERE p1.user_id = $userId AND p2.user_id = $dest
                AND (SELECT COUNT(*) FROM participants_conversation WHERE conversation_id = c.id) = 2");
            if ($conv->num_rows == 0) {
                $conn->query("INSERT INTO conversations (type) VALUES ('private')");
                $convId = $conn->insert_id;
                $conn->query("INSERT IGNORE INTO participants_conversation (conversation_id, user_id) VALUES ($convId, $userId), ($convId, $dest)");
            } else {
                $convId = $conv->fetch_assoc()['id'];
            }
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, user_id, contenu) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $convId, $userId, $contenu);
            $stmt->execute();
            $conn->query("INSERT INTO notifications (user_id, type, contenu, lien) VALUES ($dest, 'message', 'Nouveau message de {$_SESSION['pseudo']}', '#/messages')");
            echo json_encode(['success' => true]);
            break;
        case 'get_conversations':
            $result = $conn->query("SELECT c.id,
                (SELECT user_id FROM participants_conversation WHERE conversation_id=c.id AND user_id!=$userId LIMIT 1) as other_user,
                (SELECT pseudo FROM utilisateurs WHERE id=other_user) as other_pseudo,
                (SELECT contenu FROM messages WHERE conversation_id=c.id ORDER BY date_envoi DESC LIMIT 1) as last_message
                FROM conversations c JOIN participants_conversation p ON c.id=p.conversation_id WHERE p.user_id=$userId");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'get_messages':
            $convId = (int)$_GET['conv_id'];
            $result = $conn->query("SELECT m.*, u.pseudo FROM messages m JOIN utilisateurs u ON m.user_id=u.id WHERE conversation_id=$convId ORDER BY date_envoi ASC");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'create_group_conversation':
            $nom = $_POST['nom_groupe'] ?? '';
            $membres = json_decode($_POST['membres'], true);
            $membres[] = $userId;
            $conn->query("INSERT INTO conversations (type, nom_groupe) VALUES ('group', '$nom')");
            $convId = $conn->insert_id;
            foreach ($membres as $mid) {
                $conn->query("INSERT INTO participants_conversation (conversation_id, user_id) VALUES ($convId, $mid)");
            }
            echo json_encode(['success' => true]);
            break;
        case 'send_message_to_conversation':
            $convId = (int)$_POST['conversation_id'];
            $contenu = $_POST['contenu'];
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, user_id, contenu) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $convId, $userId, $contenu);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
        case 'get_notifications':
            $result = $conn->query("SELECT * FROM notifications WHERE user_id=$userId ORDER BY date_notification DESC");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'mark_notification_read':
            $notifId = (int)$_POST['notif_id'];
            $conn->query("UPDATE notifications SET lu=1 WHERE id=$notifId");
            echo json_encode(['success' => true]);
            break;
        case 'report':
            $pubId = isset($_POST['publication_id']) ? (int)$_POST['publication_id'] : null;
            $comId = isset($_POST['commentaire_id']) ? (int)$_POST['commentaire_id'] : null;
            $motif = $_POST['motif'] ?? '';
            $conn->query("INSERT INTO signalements (user_id, publication_id, commentaire_id, motif) VALUES ($userId, " . ($pubId ?: 'NULL') . ", " . ($comId ?: 'NULL') . ", '$motif')");
            echo json_encode(['success' => true]);
            break;
        case 'get_reports':
            if ($_SESSION['role'] != 'admin') { http_response_code(403); break; }
            $result = $conn->query("SELECT s.*, u.pseudo as signalant, p.contenu_texte as pub_texte, c.contenu as com_texte FROM signalements s
                LEFT JOIN utilisateurs u ON s.user_id=u.id
                LEFT JOIN publications p ON s.publication_id=p.id
                LEFT JOIN commentaires c ON s.commentaire_id=c.id WHERE s.traite=0");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'delete_reported_content':
            if ($_SESSION['role'] != 'admin') { http_response_code(403); break; }
            $reportId = (int)$_POST['report_id'];
            $report = $conn->query("SELECT publication_id, commentaire_id FROM signalements WHERE id=$reportId")->fetch_assoc();
            if ($report['publication_id']) $conn->query("DELETE FROM publications WHERE id={$report['publication_id']}");
            if ($report['commentaire_id']) $conn->query("DELETE FROM commentaires WHERE id={$report['commentaire_id']}");
            $conn->query("UPDATE signalements SET traite=1 WHERE id=$reportId");
            echo json_encode(['success' => true]);
            break;
        case 'block_user':
            $blockedId = (int)$_POST['user_id'];
            $conn->query("INSERT IGNORE INTO blocages (user_id, blocked_user_id) VALUES ($userId, $blockedId)");
            $conn->query("DELETE FROM abonnements WHERE (abonne_id=$userId AND abonné_id=$blockedId) OR (abonne_id=$blockedId AND abonné_id=$userId)");
            echo json_encode(['success' => true]);
            break;
        case 'unblock_user':
            $blockedId = (int)$_POST['user_id'];
            $conn->query("DELETE FROM blocages WHERE user_id=$userId AND blocked_user_id=$blockedId");
            echo json_encode(['success' => true]);
            break;
        case 'get_blocked_users':
            $result = $conn->query("SELECT u.id, u.pseudo, u.avatar FROM blocages b JOIN utilisateurs u ON b.blocked_user_id = u.id WHERE b.user_id = $userId");
            echo json_encode($result->fetch_all(MYSQLI_ASSOC));
            break;
        case 'search':
            $q = $_GET['q'] ?? '';
            $users = $conn->query("SELECT id, pseudo, avatar FROM utilisateurs WHERE pseudo LIKE '%$q%' LIMIT 5")->fetch_all(MYSQLI_ASSOC);
            $comms = $conn->query("SELECT id, nom, description FROM communautes WHERE nom LIKE '%$q%' LIMIT 5")->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['users' => $users, 'communautes' => $comms]);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>