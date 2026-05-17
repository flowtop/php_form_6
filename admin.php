<?php

$host = 'localhost';
$dbname = 'u82813';
$username = 'u82813';
$password = '4313992';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function getAllApplications($pdo) {
    $stmt = $pdo->query("
        SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
        FROM task6_applications a
        LEFT JOIN task6_application_languages al ON a.id = al.application_id
        LEFT JOIN task6_programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.id DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteApplication($pdo, $id) {
    try {
        $pdo->prepare("DELETE FROM task6_application_languages WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM task6_applications WHERE id = ?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function getLanguageStats($pdo) {
    $stmt = $pdo->query("
        SELECT pl.name, COUNT(al.application_id) as count
        FROM task6_programming_languages pl
        LEFT JOIN task6_application_languages al ON pl.id = al.language_id
        GROUP BY pl.id
        ORDER BY count DESC, pl.name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalStats($pdo) {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM task6_applications")->fetchColumn();
    $totalMen = $pdo->query("SELECT COUNT(*) FROM task6_applications WHERE gender = 'male'")->fetchColumn();
    $totalWomen = $pdo->query("SELECT COUNT(*) FROM task6_applications WHERE gender = 'female'")->fetchColumn();
    
    return [
        'total' => $totalUsers,
        'men' => $totalMen,
        'women' => $totalWomen
    ];
}


$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        login VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");


$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE login = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO admin_users (login, password_hash) VALUES ('admin', '$2y$10$IYrzZTsg9loGRuWWuU6Jbe7UrXIrCaJ20d9Q3UGb1hUI5/22UUMlW')")->execute();
}

// HTTP-авторизация
if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - Task6"');
    echo '<h1>🔐 Требуется авторизация</h1>';
    exit();
}

$stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - Task6"');
    echo '<h1>🔐 Неверный логин или пароль</h1>';
    exit();
}


$message = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteApplication($pdo, $id)) {
        $message = "✅ Анкета #{$id} успешно удалена";
    } else {
        $error = "❌ Ошибка при удалении анкеты #{$id}";
    }
}

// ============================================
// ПОЛУЧЕНИЕ ДАННЫХ ДЛЯ ОТОБРАЖЕНИЯ
// ============================================
$applications = getAllApplications($pdo);
$languageStats = getLanguageStats($pdo);
$totalStats = getTotalStats($pdo);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Задание 6</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔐 Панель администратора</h1>
        <div>👋 Здравствуйте, <?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></div>
    </div>
    
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- СТАТИСТИКА -->
    <div class="stats-container">
        <div class="stats-card">
            <h3>📊 Общая статистика</h3>
            <p>Всего пользователей: <span class="number"><?= $totalStats['total'] ?></span></p>
            <p>👨 Мужчин: <?= $totalStats['men'] ?></p>
            <p>👩 Женщин: <?= $totalStats['women'] ?></p>
        </div>
        
        <div class="stats-card">
            <h3>💻 Языки программирования</h3>
            <?php if (!empty($languageStats)): ?>
                <ul class="lang-stats">
                    <?php foreach ($languageStats as $lang): ?>
                        <li>
                            <span><?= htmlspecialchars($lang['name']) ?></span>
                            <span class="lang-count">👥 <?= $lang['count'] ?> чел.</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Нет данных</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ТАБЛИЦА С АНКЕТАМИ -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Биография</th>
                    <th>Контракт</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px;">
                            📭 Нет данных. Пользователи ещё не заполняли анкеты.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= $app['id'] ?></td>
                            <td><?= htmlspecialchars($app['fio']) ?></td>
                            <td><?= htmlspecialchars($app['phone']) ?></td>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= $app['birth_date'] ?></td>
                            <td><?= $app['gender'] == 'male' ? '👨 Мужской' : '👩 Женский' ?></td>
                            <td>
                                <?php 
                                $languages = explode(',', $app['languages'] ?? '');
                                foreach ($languages as $lang):
                                    if (trim($lang)):
                                ?>
                                    <span class="badge"><?= htmlspecialchars(trim($lang)) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </td>
                            <td><?= htmlspecialchars(substr($app['biography'] ?? '', 0, 50)) ?>...</td>
                            <td><?= $app['contract_agreed'] ? '✅ Да' : '❌ Нет' ?></td>
                            <td><?= $app['created_at'] ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $app['id'] ?>" class="btn-edit">✏️ Редактировать</a>
                                <a href="?delete=<?= $app['id'] ?>" class="btn-delete" 
                                   onclick="return confirm('Удалить анкету?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
