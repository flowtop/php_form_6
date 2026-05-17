<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета разработчика - Задание 5</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 32px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        form {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }
        
        .required::after {
            content: " *";
            color: #ef4444;
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            background: #fefefe;
        }
        
        input.error-input,
        select.error-input,
        textarea.error-input {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .radio-group {
            display: flex;
            gap: 24px;
            padding: 8px 0;
        }
        
        .radio-group.error-group {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
            padding: 8px 16px;
            border-radius: 12px;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .radio-group input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        select[multiple] {
            height: 160px;
        }
        
        select[multiple] option {
            padding: 10px 12px;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        select[multiple] option:checked {
            background: #667eea linear-gradient(0deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }
        
        .checkbox-group.error-group {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
            padding: 8px 16px;
            border-radius: 12px;
        }
        
        .checkbox-group input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .small-hint {
            font-size: 11px;
            color: #6b7280;
            margin-top: 6px;
            display: block;
        }
        
        button {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 16px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.4);
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid #dc2626;
        }
        
        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid #16a34a;
        }
        
        .info-message {
            background: #dbeafe;
            color: #1e40af;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid #3b82f6;
        }
        
        #messages {
            margin-bottom: 20px;
        }
        
        .logout-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        @media (max-width: 640px) {
            form { padding: 24px; }
            .header { padding: 24px; }
            .radio-group { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 Анкета разработчика</h1>
        <p>Заполните форму — данные сохранятся, вы получите логин и пароль</p>
    </div>
    
    <div class="form-body">
        <?php if (!empty($messages)): ?>
            <div id="messages">
                <?php foreach ($messages as $message): ?>
                    <?= $message ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="fio" class="required">ФИО</label>
                <input type="text" id="fio" name="fio" 
                       class="<?= $errors['fio'] ?? false ? 'error-input' : '' ?>"
                       value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                       placeholder="Иванов Иван Иванович">
                <span class="small-hint">Только буквы, пробелы и дефисы. Максимум 150 символов.</span>
            </div>
            
            <div class="form-group">
                <label for="phone" class="required">Телефон</label>
                <input type="tel" id="phone" name="phone"
                       class="<?= $errors['phone'] ?? false ? 'error-input' : '' ?>"
                       value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                       placeholder="+7 (123) 456-78-90">
                <span class="small-hint">Формат: +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр)</span>
            </div>
            
            <div class="form-group">
                <label for="email" class="required">E-mail</label>
                <input type="email" id="email" name="email"
                       class="<?= $errors['email'] ?? false ? 'error-input' : '' ?>"
                       value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                       placeholder="ivanov@example.com">
            </div>
            
            <div class="form-group">
                <label for="birth_date" class="required">Дата рождения</label>
                <input type="date" id="birth_date" name="birth_date"
                       class="<?= $errors['birth_date'] ?? false ? 'error-input' : '' ?>"
                       value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Пол</label>
                <div class="radio-group <?= $errors['gender'] ?? false ? 'error-group' : '' ?>">
                    <label>
                        <input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>> Мужской
                    </label>
                    <label>
                        <input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>> Женский
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="languages" class="required">Любимый язык программирования</label>
                <select name="languages[]" id="languages" multiple size="6"
                        class="<?= $errors['languages'] ?? false ? 'error-input' : '' ?>">
                    <option value="Pascal" <?= in_array('Pascal', $values['languages'] ?? []) ? 'selected' : '' ?>>Pascal</option>
                    <option value="C" <?= in_array('C', $values['languages'] ?? []) ? 'selected' : '' ?>>C</option>
                    <option value="C++" <?= in_array('C++', $values['languages'] ?? []) ? 'selected' : '' ?>>C++</option>
                    <option value="JavaScript" <?= in_array('JavaScript', $values['languages'] ?? []) ? 'selected' : '' ?>>JavaScript</option>
                    <option value="PHP" <?= in_array('PHP', $values['languages'] ?? []) ? 'selected' : '' ?>>PHP</option>
                    <option value="Python" <?= in_array('Python', $values['languages'] ?? []) ? 'selected' : '' ?>>Python</option>
                    <option value="Java" <?= in_array('Java', $values['languages'] ?? []) ? 'selected' : '' ?>>Java</option>
                    <option value="Haskell" <?= in_array('Haskell', $values['languages'] ?? []) ? 'selected' : '' ?>>Haskell</option>
                    <option value="Clojure" <?= in_array('Clojure', $values['languages'] ?? []) ? 'selected' : '' ?>>Clojure</option>
                    <option value="Prolog" <?= in_array('Prolog', $values['languages'] ?? []) ? 'selected' : '' ?>>Prolog</option>
                    <option value="Scala" <?= in_array('Scala', $values['languages'] ?? []) ? 'selected' : '' ?>>Scala</option>
                    <option value="Go" <?= in_array('Go', $values['languages'] ?? []) ? 'selected' : '' ?>>Go</option>
                </select>
                <span class="small-hint">Удерживайте Ctrl (Cmd) для выбора нескольких языков</span>
            </div>
            
            <div class="form-group">
                <label for="biography">Биография</label>
                <textarea id="biography" name="biography" rows="5"
                          class="<?= $errors['biography'] ?? false ? 'error-input' : '' ?>"
                          placeholder="Расскажите немного о себе..."><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
                <span class="small-hint">Необязательное поле. Максимум 1000 символов.</span>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group <?= $errors['contract_agreed'] ?? false ? 'error-group' : '' ?>">
                    <input type="checkbox" name="contract_agreed" id="contract_agreed" value="1"
                           <?= ($values['contract_agreed'] ?? '') == '1' ? 'checked' : '' ?>>
                    <label for="contract_agreed" class="required">Я ознакомлен(а) с условиями контракта</label>
                </div>
            </div>
            
            <button type="submit">💾 Сохранить</button>
        </form>
        
        <?php if (!empty($_SESSION['login'])): ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Выйти из аккаунта?')">🚪 Выйти</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
