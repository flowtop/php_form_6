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
    $pdo->prepare("INSERT INTO admin_users (login, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')")->execute();
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .stats-card h3 {
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }
        
        .stats-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stats-card p {
            margin: 8px 0;
            font-size: 16px;
        }
        
        .lang-stats {
            list-style: none;
        }
        
        .lang-stats li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .lang-stats .lang-count {
            background: #667eea;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .table-container {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2563eb;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .badge {
            background: #e5e7eb;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin: 2px;
        }
        
        .message {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
        }
        
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .actions { flex-direction: column; }
        }
    </style>
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
