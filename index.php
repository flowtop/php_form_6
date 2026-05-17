<?php

session_start();
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'u82813';
$username_db = 'u82813';
$password_db = '4313992';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username_db,
        $password_db,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie(session_name(), '', time() - 3600);
    header('Location: index.php');
    exit();
}

$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

//функции валидации
function validateFio($fio) {
    if (empty($fio)) return 'ФИО обязательно для заполнения';
    if (strlen($fio) > 150) return 'ФИО не должно превышать 150 символов';
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $fio)) {
        return 'ФИО содержит недопустимые символы';
    }
    return null;
}

function validatePhone($phone) {
    if (empty($phone)) return 'Телефон обязателен для заполнения';
    if (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        return 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр)';
    }
    return null;
}

function validateEmail($email) {
    if (empty($email)) return 'E-mail обязателен для заполнения';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Введите корректный email адрес';
    }
    return null;
}

function validateBirthDate($birth_date) {
    if (empty($birth_date)) return 'Дата рождения обязательна для заполнения';
    $date = DateTime::createFromFormat('Y-m-d', $birth_date);
    $today = new DateTime();
    if (!$date || $date > $today) return 'Введите корректную дату рождения';
    return null;
}

function validateGender($gender) {
    if (empty($gender)) return 'Укажите пол';
    if (!in_array($gender, ['male', 'female'])) return 'Выбран недопустимый пол';
    return null;
}

function validateLanguages($languages, $allowed) {
    if (empty($languages)) return 'Выберите хотя бы один язык программирования';
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed)) return 'Выбран недопустимый язык';
    }
    return null;
}

function validateBiography($bio) {
    if (!empty($bio) && strlen($bio) > 1000) return 'Биография не должна превышать 1000 символов';
    return null;
}

function validateContract($contract) {
    if ($contract != '1') return 'Необходимо подтвердить ознакомление с контрактом';
    return null;
}

function generateLogin($fio, $email) {
    $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', strtok($fio, ' '))));
    $email_prefix = strtolower(substr(strtok($email, '@'), 0, 5));
    $random = rand(100, 999);
    $login = $base . '_' . $email_prefix . '_' . $random;
    return substr($login, 0, 50);
}

function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

function getFieldName($field) {
    $names = [
        'fio' => 'ФИО', 'phone' => 'Телефон', 'email' => 'E-mail',
        'birth_date' => 'Дата рождения', 'gender' => 'Пол',
        'languages' => 'Языки программирования', 'biography' => 'Биография',
        'contract_agreed' => 'Согласие с контрактом'
    ];
    return $names[$field] ?? $field;
}

//ОБРАБОТКА GET-ЗАПРОСА
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login_temp', '', 100000);
        setcookie('pass_temp', '', 100000);
        $messages[] = '<div class="success-message">✅ Данные успешно сохранены!</div>';
        
        if (!empty($_COOKIE['login_temp']) && !empty($_COOKIE['pass_temp'])) {
            $messages[] = sprintf(
                '<div class="info-message">🔐 Ваши данные для входа:<br>
                📌 Логин: <strong>%s</strong><br>
                🔑 Пароль: <strong>%s</strong><br>
                <a href="login.php">Нажмите здесь</a> чтобы войти и изменить данные.</div>',
                htmlspecialchars($_COOKIE['login_temp']),
                htmlspecialchars($_COOKIE['pass_temp'])
            );
        }
    }
    
    $fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract_agreed'];
    
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        if ($errors[$field] && !empty($_COOKIE[$field . '_error_msg'])) {
            $messages[] = '<div class="error-message">❌ Ошибка в поле "' . getFieldName($field) . '": ' . htmlspecialchars($_COOKIE[$field . '_error_msg']) . '</div>';
            setcookie($field . '_error', '', 100000);
            setcookie($field . '_error_msg', '', 100000);
        }
        
        if ($field == 'languages') {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? [] : explode(',', $_COOKIE[$field . '_value']);
        } else {
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : htmlspecialchars($_COOKIE[$field . '_value']);
        }
    }
    
    if (!empty($_SESSION['login']) && !empty($_SESSION['uid'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM task6_applications WHERE id = ? AND login = ?");
            $stmt->execute([$_SESSION['uid'], $_SESSION['login']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $values['fio'] = htmlspecialchars($userData['fio']);
                $values['phone'] = htmlspecialchars($userData['phone']);
                $values['email'] = htmlspecialchars($userData['email']);
                $values['birth_date'] = $userData['birth_date'];
                $values['gender'] = $userData['gender'];
                $values['biography'] = htmlspecialchars($userData['biography']);
                $values['contract_agreed'] = $userData['contract_agreed'];
                
                $stmtLang = $pdo->prepare("SELECT pl.name FROM task6_application_languages al JOIN task6_programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
                $stmtLang->execute([$userData['id']]);
                $values['languages'] = $stmtLang->fetchAll(PDO::FETCH_COLUMN);
                
                $messages[] = '<div class="info-message">👋 Вы вошли как <strong>' . htmlspecialchars($_SESSION['login']) . '</strong>. Можете изменить свои данные.</div>';
            }
        } catch (PDOException $e) {}
    }
    
    include('form.php');
    exit();
}

//ОБРАБОТКА POST-ЗАПРОСА
else {
    $hasErrors = false;
    
    $fio = trim($_POST['fio'] ?? '');
    $fio_error = validateFio($fio);
    if ($fio_error) {
        setcookie('fio_error', '1', time() + 24 * 60 * 60);
        setcookie('fio_error_msg', $fio_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('fio_value', $fio, time() + 365 * 24 * 60 * 60);
    
    $phone = trim($_POST['phone'] ?? '');
    $phone_error = validatePhone($phone);
    if ($phone_error) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        setcookie('phone_error_msg', $phone_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('phone_value', $phone, time() + 365 * 24 * 60 * 60);
    
    $email = trim($_POST['email'] ?? '');
    $email_error = validateEmail($email);
    if ($email_error) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        setcookie('email_error_msg', $email_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('email_value', $email, time() + 365 * 24 * 60 * 60);
    
    $birth_date = $_POST['birth_date'] ?? '';
    $birth_date_error = validateBirthDate($birth_date);
    if ($birth_date_error) {
        setcookie('birth_date_error', '1', time() + 24 * 60 * 60);
        setcookie('birth_date_error_msg', $birth_date_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('birth_date_value', $birth_date, time() + 365 * 24 * 60 * 60);
    
    $gender = $_POST['gender'] ?? '';
    $gender_error = validateGender($gender);
    if ($gender_error) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        setcookie('gender_error_msg', $gender_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('gender_value', $gender, time() + 365 * 24 * 60 * 60);
    
    $languages = $_POST['languages'] ?? [];
    $languages_error = validateLanguages($languages, $allowedLanguages);
    if ($languages_error) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        setcookie('languages_error_msg', $languages_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('languages_value', implode(',', $languages), time() + 365 * 24 * 60 * 60);
    
    $biography = trim($_POST['biography'] ?? '');
    $biography_error = validateBiography($biography);
    if ($biography_error) {
        setcookie('biography_error', '1', time() + 24 * 60 * 60);
        setcookie('biography_error_msg', $biography_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('biography_value', $biography, time() + 365 * 24 * 60 * 60);
    
    $contract_agreed = isset($_POST['contract_agreed']) ? '1' : '';
    $contract_error = validateContract($contract_agreed);
    if ($contract_error) {
        setcookie('contract_agreed_error', '1', time() + 24 * 60 * 60);
        setcookie('contract_agreed_error_msg', $contract_error, time() + 24 * 60 * 60);
        $hasErrors = true;
    }
    setcookie('contract_agreed_value', $contract_agreed, time() + 365 * 24 * 60 * 60);
    
    if ($hasErrors) {
        header('Location: index.php');
        exit();
    }
    
    // Удаляем куки ошибок
    $error_fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract_agreed'];
    foreach ($error_fields as $field) {
        setcookie($field . '_error', '', 100000);
        setcookie($field . '_error_msg', '', 100000);
    }
    
    try {
        // Проверяем авторизован ли пользователь
        $isAuthorized = !empty($_SESSION['login']) && !empty($_SESSION['uid']);
        
        if ($isAuthorized) {
            $stmt = $pdo->prepare("
                UPDATE task6_applications 
                SET fio = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, contract_agreed = ?
                WHERE id = ? AND login = ?
            ");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_agreed == '1' ? 1 : 0, $_SESSION['uid'], $_SESSION['login']]);
            
            $pdo->prepare("DELETE FROM task6_application_languages WHERE application_id = ?")->execute([$_SESSION['uid']]);
            
            $langIdStmt = $pdo->prepare("SELECT id FROM task6_programming_languages WHERE name = ?");
            $insertLangStmt = $pdo->prepare("INSERT INTO task6_application_languages (application_id, language_id) VALUES (?, ?)");
            
            foreach ($languages as $langName) {
                $langIdStmt->execute([$langName]);
                $langId = $langIdStmt->fetchColumn();
                if ($langId) $insertLangStmt->execute([$_SESSION['uid'], $langId]);
            }
            
            setcookie('save', '1', time() + 24 * 60 * 60);
        } else {
            // Новая запись - генерируем логин и пароль
            $login = generateLogin($fio, $email);
            $plainPassword = generatePassword(10);
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            
            // Проверяем уникальность логина
            $checkStmt = $pdo->prepare("SELECT id FROM task6_applications WHERE login = ?");
            $checkStmt->execute([$login]);
            if ($checkStmt->fetch()) {
                $login = $login . '_' . rand(1000, 9999);
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO task6_applications (fio, phone, email, birth_date, gender, biography, contract_agreed, login, password_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, $contract_agreed == '1' ? 1 : 0, $login, $passwordHash]);
            
            $applicationId = $pdo->lastInsertId();
            
            $langIdStmt = $pdo->prepare("SELECT id FROM task6_programming_languages WHERE name = ?");
            $insertLangStmt = $pdo->prepare("INSERT INTO task6_application_languages (application_id, language_id) VALUES (?, ?)");
            
            foreach ($languages as $langName) {
                $langIdStmt->execute([$langName]);
                $langId = $langIdStmt->fetchColumn();
                if ($langId) $insertLangStmt->execute([$applicationId, $langId]);
            }
            
            $pdo->commit();
            
            setcookie('save', '1', time() + 24 * 60 * 60);
            setcookie('login_temp', $login, time() + 24 * 60 * 60);
            setcookie('pass_temp', $plainPassword, time() + 24 * 60 * 60);
        }
        
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setcookie('save_error', '1', time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
    }
}
?>
