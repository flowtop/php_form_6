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


function getApplicationById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ',') as languages
        FROM task6_applications a
        LEFT JOIN task6_application_languages al ON a.id = al.application_id
        LEFT JOIN task6_programming_languages pl ON al.language_id = pl.id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateApplication($pdo, $id, $data) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            UPDATE task6_applications 
            SET fio = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, contract_agreed = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['fio'], $data['phone'], $data['email'], $data['birth_date'],
            $data['gender'], $data['biography'], $data['contract_agreed'], $id
        ]);
        
        $pdo->prepare("DELETE FROM task6_application_languages WHERE application_id = ?")->execute([$id]);
        
        if (!empty($data['languages'])) {
            $langIdStmt = $pdo->prepare("SELECT id FROM task6_programming_languages WHERE name = ?");
            $insertStmt = $pdo->prepare("INSERT INTO task6_application_languages (application_id, language_id) VALUES (?, ?)");
            
            foreach ($data['languages'] as $langName) {
                $langIdStmt->execute([$langName]);
                $langId = $langIdStmt->fetchColumn();
                if ($langId) $insertStmt->execute([$id, $langId]);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: admin.php');
    exit();
}

$application = getApplicationById($pdo, $id);
if (!$application) {
    header('Location: admin.php');
    exit();
}

$message = '';
$error = '';
$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    $fio = trim($_POST['fio'] ?? '');
    if (empty($fio)) {
        $errors['fio'] = 'ФИО обязательно';
    } elseif (strlen($fio) > 150) {
        $errors['fio'] = 'ФИО не более 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $fio)) {
        $errors['fio'] = 'Недопустимые символы в ФИО';
    }
    
    $phone = trim($_POST['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен';
    } elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Неверный формат телефона';
    }
    
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email обязателен';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный email';
    }
    
    $birth_date = $_POST['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Дата рождения обязательна';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        $today = new DateTime();
        if (!$date || $date > $today) {
            $errors['birth_date'] = 'Неверная дата';
        }
    }
    
    $gender = $_POST['gender'] ?? '';
    if (empty($gender)) {
        $errors['gender'] = 'Укажите пол';
    } elseif (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Неверный пол';
    }
    
    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        $errors['languages'] = 'Выберите язык';
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $allowedLanguages)) {
                $errors['languages'] = 'Недопустимый язык';
                break;
            }
        }
    }
    
    $biography = trim($_POST['biography'] ?? '');
    if (strlen($biography) > 1000) {
        $errors['biography'] = 'Биография не более 1000 символов';
    }
    
    $contract_agreed = isset($_POST['contract_agreed']) ? 1 : 0;
    
    if (empty($errors)) {
        $data = [
            'fio' => $fio,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'biography' => $biography,
            'contract_agreed' => $contract_agreed,
            'languages' => $languages
        ];
        
        if (updateApplication($pdo, $id, $data)) {
            $message = "✅ Анкета #{$id} успешно обновлена";
            $application = getApplicationById($pdo, $id);
            $application['languages_array'] = explode(',', $application['languages'] ?? '');
        } else {
            $error = "❌ Ошибка при обновлении";
        }
    } else {
        $error = implode('<br>', $errors);
        $application['fio'] = $fio;
        $application['phone'] = $phone;
        $application['email'] = $email;
        $application['birth_date'] = $birth_date;
        $application['gender'] = $gender;
        $application['biography'] = $biography;
        $application['contract_agreed'] = $contract_agreed;
        $application['languages_array'] = $languages;
    }
}

if (!isset($application['languages_array'])) {
    $application['languages_array'] = explode(',', $application['languages'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование анкеты #<?= $id ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.3);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        .required::after { content: " *"; color: #ef4444; }
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
        }
        select[multiple] { height: 150px; }
        .radio-group { display: flex; gap: 24px; padding: 8px 0; }
        .radio-group label { display: flex; align-items: center; gap: 8px; font-weight: normal; }
        .checkbox-group { display: flex; align-items: center; gap: 12px; }
        .btn-save, .btn-cancel {
            padding: 12px 24px;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 12px;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-cancel {
            background: #e5e7eb;
            color: #1f2937;
            text-decoration: none;
            display: inline-block;
        }
        .message {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .small-hint { font-size: 11px; color: #6b7280; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1>✏️ Редактирование анкеты #<?= $id ?></h1>
            <p>Пользователь: <?= htmlspecialchars($application['fio']) ?></p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="required">ФИО</label>
                <input type="text" name="fio" value="<?= htmlspecialchars($application['fio']) ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Телефон</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($application['phone']) ?>">
            </div>
            
            <div class="form-group">
                <label class="required">E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($application['email']) ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Дата рождения</label>
                <input type="date" name="birth_date" value="<?= $application['birth_date'] ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Пол</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" <?= $application['gender'] == 'male' ? 'checked' : '' ?>> Мужской</label>
                    <label><input type="radio" name="gender" value="female" <?= $application['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="required">Языки программирования</label>
                <select name="languages[]" multiple size="6">
                    <?php foreach ($allowedLanguages as $lang): ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $application['languages_array']) ? 'selected' : '' ?>><?= $lang ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="small-hint">Удерживайте Ctrl для выбора нескольких</div>
            </div>
            
            <div class="form-group">
                <label>Биография</label>
                <textarea name="biography" rows="5"><?= htmlspecialchars($application['biography'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="contract_agreed" value="1" <?= $application['contract_agreed'] ? 'checked' : '' ?>>
                    <label>Я ознакомлен(а) с условиями контракта</label>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <button type="submit" class="btn-save">💾 Сохранить изменения</button>
                <a href="admin.php" class="btn-cancel">← Отмена</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
