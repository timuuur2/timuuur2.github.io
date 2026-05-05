// ============================================================
// form-fetch.js — Задание 8: отправка формы через Fetch API
// Фоллбек на обычный POST работает если JS отключён.
// ============================================================

(function () {
    'use strict';

    const form    = document.getElementById('application-form');
    const msgBox  = document.getElementById('form-message');
    const submitBtn = document.getElementById('submit-btn');

    if (!form) return;

    // Навешиваем обработчик динамически — если JS включён,
    // перехватываем отправку и используем Fetch (Задание 8)
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearMessages();
        if (!clientValidate()) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';

        const data = buildPayload();

        try {
            const res  = await fetch('api.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(data),
            });

            const json = await res.json();

            if (res.status === 201) {
                // Успех — показываем логин и пароль (Задание 5)
                showSuccess(json);
                form.reset();

                // Сохраняем токен для дальнейших запросов
                localStorage.setItem('dc_token', json.token);
                localStorage.setItem('dc_id',    json.id);

            } else if (res.status === 422 && json.details) {
                // Ошибки валидации с сервера — подсвечиваем поля
                showServerErrors(json.details);
            } else {
                showError(json.error || 'Произошла ошибка. Попробуйте ещё раз.');
            }
        } catch (err) {
            showError('Ошибка соединения. Проверьте интернет и попробуйте ещё раз.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Оставить заявку!';
        }
    });

    // --------------------------------------------------------
    // Клиентская валидация (дублирует серверную)
    // --------------------------------------------------------
    function clientValidate() {
        let valid = true;
        const rules = [
            { id: 'full_name',  test: v => /^[\p{L}\s\-]{1,150}$/u.test(v),  msg: 'ФИО: только буквы и пробелы, до 150 символов' },
            { id: 'phone',      test: v => /^\+?[\d\s\-\(\)]{7,20}$/.test(v), msg: 'Введите корректный телефон' },
            { id: 'email',      test: v => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v), msg: 'Введите корректный e-mail' },
            { id: 'birthdate',  test: v => { const d = new Date(v); return !isNaN(d) && d < new Date(); }, msg: 'Введите корректную дату рождения' },
            { id: 'biography',  test: v => v.trim().length > 0 && v.trim().length <= 5000, msg: 'Заполните биографию (до 5000 символов)' },
        ];

        for (const rule of rules) {
            const el  = document.getElementById(rule.id);
            const val = el ? el.value.trim() : '';
            if (!rule.test(val)) {
                markFieldError(rule.id, rule.msg);
                valid = false;
            }
        }

        // Пол
        const genderSelected = form.querySelector('input[name="gender"]:checked');
        if (!genderSelected) {
            markFieldError('gender_group', 'Выберите пол');
            valid = false;
        }

        // Языки
        const langSelect = document.getElementById('languages');
        if (!langSelect || langSelect.selectedOptions.length === 0) {
            markFieldError('languages', 'Выберите хотя бы один язык программирования');
            valid = false;
        }

        // Согласие
        const agreed = form.querySelector('input[name="agreed"]');
        if (agreed && !agreed.checked) {
            showError('Необходимо дать согласие на обработку персональных данных.');
            valid = false;
        }

        return valid;
    }

    // --------------------------------------------------------
    // Собираем данные формы в JSON-объект
    // --------------------------------------------------------
    function buildPayload() {
        const langSelect = document.getElementById('languages');
        const languages  = Array.from(langSelect.selectedOptions).map(o => parseInt(o.value));
        const gender     = (form.querySelector('input[name="gender"]:checked') || {}).value;

        return {
            full_name: document.getElementById('full_name').value.trim(),
            phone:     document.getElementById('phone').value.trim(),
            email:     document.getElementById('email').value.trim(),
            birthdate: document.getElementById('birthdate').value,
            gender:    gender,
            languages: languages,
            biography: document.getElementById('biography').value.trim(),
            agreed:    '1',
        };
    }

    // --------------------------------------------------------
    // UI helpers
    // --------------------------------------------------------
    function clearMessages() {
        msgBox.style.display = 'none';
        msgBox.innerHTML = '';
        form.querySelectorAll('.webform__field--error').forEach(el => el.classList.remove('webform__field--error'));
        form.querySelectorAll('.webform__error-msg').forEach(el => el.remove());
    }

    function showSuccess(json) {
        msgBox.style.display = 'block';
        msgBox.className = 'webform__message webform__message--success';
        msgBox.innerHTML = `
            <p><strong>🎉 Заявка успешно отправлена!</strong></p>
            <p>Ваши данные для входа в личный кабинет:</p>
            <p>Логин: <strong>${escHtml(json.login)}</strong></p>
            <p>Пароль: <strong>${escHtml(json.password)}</strong></p>
            <p style="color:#6b7280;font-size:13px">⚠️ Сохраните эти данные — они показываются только один раз!</p>
            <a href="${escHtml(json.profile_url)}" style="color:#F14D34;font-weight:700;">Перейти в личный кабинет →</a>
        `;
        msgBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showError(msg) {
        msgBox.style.display = 'block';
        msgBox.className = 'webform__message webform__message--error';
        msgBox.textContent = msg;
    }

    function showServerErrors(details) {
        msgBox.style.display = 'block';
        msgBox.className = 'webform__message webform__message--error';
        msgBox.innerHTML = '<strong>Исправьте ошибки:</strong><ul>' +
            Object.values(details).map(e => `<li>${escHtml(e)}</li>`).join('') + '</ul>';

        for (const [field, msg] of Object.entries(details)) {
            markFieldError(field, msg);
        }
    }

    function markFieldError(fieldId, msg) {
        const el = document.getElementById(fieldId) || form.querySelector(`[name="${fieldId}"]`);
        if (!el) return;
        const wrapper = el.closest('.webform__field') || el.parentElement;
        if (wrapper) {
            wrapper.classList.add('webform__field--error');
            const errEl = document.createElement('span');
            errEl.className = 'webform__error-msg';
            errEl.textContent = msg;
            wrapper.appendChild(errEl);
        }
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }
})();
