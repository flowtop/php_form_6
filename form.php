<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="header">
        <div class="header__container container">
            <h1>Панель администратора. Задание 6.</h1>
        </div>
    </div>

    <main>
        <div class="main__container container">
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
                    <input type="text" id="fio" name="fio" class="<?= $errors['fio'] ?? false ? 'error-input' : '' ?>"
                        value="<?= htmlspecialchars($values['fio'] ?? '') ?>" placeholder="Иванов Иван Иванович">
                    <span class="small-hint">Только буквы, пробелы и дефисы. Максимум 150 символов.</span>
                </div>

                <div class="form-group">
                    <label for="phone" class="required">Телефон</label>
                    <input type="tel" id="phone" name="phone"
                        class="<?= $errors['phone'] ?? false ? 'error-input' : '' ?>"
                        value="<?= htmlspecialchars($values['phone'] ?? '') ?>" placeholder="+7XXXXXXXXXX">
                    <span class="small-hint">Формат: +7XXXXXXXXXX или 8XXXXXXXXXX (11 цифр)</span>
                </div>

                <div class="form-group">
                    <label for="email" class="required">E-mail</label>
                    <input type="email" id="email" name="email"
                        class="<?= $errors['email'] ?? false ? 'error-input' : '' ?>"
                        value="<?= htmlspecialchars($values['email'] ?? '') ?>" placeholder="ivanov@example.com">
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
                            <input type="radio" name="gender" value="male" <?=($values['gender'] ?? '' )=='male'
                                ? 'checked' : '' ?>> Мужской
                        </label>
                        <label>
                            <input type="radio" name="gender" value="female" <?=($values['gender'] ?? '' )=='female'
                                ? 'checked' : '' ?>> Женский
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="languages" class="required">Любимый язык программирования</label>
                    <select name="languages[]" id="languages" multiple size="6"
                        class="<?= $errors['languages'] ?? false ? 'error-input' : '' ?>">
                        <option value="Pascal" <?=in_array('Pascal', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Pascal</option>
                        <option value="C" <?=in_array('C', $values['languages'] ?? []) ? 'selected' : '' ?>>C</option>
                        <option value="C++" <?=in_array('C++', $values['languages'] ?? []) ? 'selected' : '' ?>>C++
                        </option>
                        <option value="JavaScript" <?=in_array('JavaScript', $values['languages'] ?? []) ? 'selected'
                            : '' ?>>JavaScript</option>
                        <option value="PHP" <?=in_array('PHP', $values['languages'] ?? []) ? 'selected' : '' ?>>PHP
                        </option>
                        <option value="Python" <?=in_array('Python', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Python</option>
                        <option value="Java" <?=in_array('Java', $values['languages'] ?? []) ? 'selected' : '' ?>>Java
                        </option>
                        <option value="Haskell" <?=in_array('Haskell', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Haskell</option>
                        <option value="Clojure" <?=in_array('Clojure', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Clojure</option>
                        <option value="Prolog" <?=in_array('Prolog', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Prolog</option>
                        <option value="Scala" <?=in_array('Scala', $values['languages'] ?? []) ? 'selected' : '' ?>
                            >Scala</option>
                        <option value="Go" <?=in_array('Go', $values['languages'] ?? []) ? 'selected' : '' ?>>Go
                        </option>
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
                            <?=($values['contract_agreed'] ?? '' )=='1' ? 'checked' : '' ?>>
                        <label for="contract_agreed" class="required">Я ознакомлен(а) с условиями контракта</label>
                    </div>
                </div>

                <button class="send_form_btn" type="submit">Отправить</button>
            </form>

            <?php if (!empty($_SESSION['login'])): ?>
            <div class="logout-message">
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Выйти из аккаунта?')">🚪 Выйти</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>