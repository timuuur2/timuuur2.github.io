// Константы
const FORM_ENDPOINT = 'https://formcarry.com/s/lo6vczOlYO'; // Замените на ваш endpoint
const STORAGE_KEY = 'contactFormData';

// Элементы DOM
const openFormBtn = document.getElementById('openFormBtn');
const popup = document.getElementById('popup');
const closeBtn = document.getElementById('closeBtn');
const popupOverlay = document.querySelector('.popup-overlay');
const contactForm = document.getElementById('contactForm');
const messageBox = document.getElementById('messageBox');

// Поля формы
const formFields = {
    fullName: document.getElementById('fullName'),
    email: document.getElementById('email'),
    phone: document.getElementById('phone'),
    organization: document.getElementById('organization'),
    message: document.getElementById('message'),
    consent: document.getElementById('consent')
};

// Функция открытия попапа
function openPopup() {
    popup.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Изменяем URL с помощью History API
    if (window.location.hash !== '#contact') {
        history.pushState({ popup: true }, '', '#contact');
    }
    
    // Восстанавливаем сохраненные данные
    loadFormData();
}

// Функция закрытия попапа
function closePopup() {
    popup.classList.remove('active');
    document.body.style.overflow = '';
    
    // Возвращаем URL к исходному состоянию
    if (window.location.hash === '#contact') {
        history.back();
    }
}

// Функция сохранения данных формы в LocalStorage
function saveFormData() {
    const formData = {
        fullName: formFields.fullName.value,
        email: formFields.email.value,
        phone: formFields.phone.value,
        organization: formFields.organization.value,
        message: formFields.message.value,
        consent: formFields.consent.checked
    };
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
}

// Функция загрузки данных формы из LocalStorage
function loadFormData() {
    const savedData = localStorage.getItem(STORAGE_KEY);
    
    if (savedData) {
        try {
            const formData = JSON.parse(savedData);
            
            formFields.fullName.value = formData.fullName || '';
            formFields.email.value = formData.email || '';
            formFields.phone.value = formData.phone || '';
            formFields.organization.value = formData.organization || '';
            formFields.message.value = formData.message || '';
            formFields.consent.checked = formData.consent || false;
        } catch (e) {
            console.error('Ошибка при загрузке данных из LocalStorage:', e);
        }
    }
}

// Функция очистки данных формы из LocalStorage
function clearFormData() {
    localStorage.removeItem(STORAGE_KEY);
}

// Функция отображения сообщения
function showMessage(text, type) {
    messageBox.textContent = text;
    messageBox.className = 'message-box ' + type;
    
    // Автоматически скрываем сообщение через 5 секунд
    setTimeout(function () {
        messageBox.className = 'message-box';
    }, 5000);
}

// Функция валидации формы
function validateForm() {
    let isValid = true;
    
    // Проверка ФИО
    if (formFields.fullName.value.trim() === '') {
        isValid = false;
        formFields.fullName.style.borderColor = '#e74c3c';
    } else {
        formFields.fullName.style.borderColor = '#e0e0e0';
    }
    
    // Проверка Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formFields.email.value.trim())) {
        isValid = false;
        formFields.email.style.borderColor = '#e74c3c';
    } else {
        formFields.email.style.borderColor = '#e0e0e0';
    }
    
    // Проверка телефона
    if (formFields.phone.value.trim() === '') {
        isValid = false;
        formFields.phone.style.borderColor = '#e74c3c';
    } else {
        formFields.phone.style.borderColor = '#e0e0e0';
    }
    
    // Проверка сообщения
    if (formFields.message.value.trim() === '') {
        isValid = false;
        formFields.message.style.borderColor = '#e74c3c';
    } else {
        formFields.message.style.borderColor = '#e0e0e0';
    }
    
    // Проверка согласия
    if (!formFields.consent.checked) {
        isValid = false;
        showMessage('Необходимо согласие с политикой обработки данных', 'error');
    }
    
    return isValid;
}

// Обработчик отправки формы
contactForm.addEventListener('submit', function (e) {
    e.preventDefault();
    
    // Валидация формы
    if (!validateForm()) {
        if (formFields.consent.checked) {
            showMessage('Пожалуйста, заполните все обязательные поля', 'error');
        }
        return;
    }
    
    // Подготовка данных для отправки
    const formData = new FormData(contactForm);
    
    // Отключаем кнопку отправки
    const submitBtn = contactForm.querySelector('.submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Отправка...';
    
    // Отправка данных на сервер
    fetch(FORM_ENDPOINT, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (data) {
        // Успешная отправка
        showMessage('Сообщение успешно отправлено!', 'success');
        
        // Очищаем форму
        contactForm.reset();
        
        // Очищаем LocalStorage
        clearFormData();
        
        // Закрываем попап через 2 секунды
        setTimeout(function () {
            closePopup();
        }, 2000);
    })
    .catch(function (error) {
        // Ошибка отправки
        console.error('Ошибка при отправке формы:', error);
        showMessage('Произошла ошибка при отправке. Попробуйте позже.', 'error');
    })
    .finally(function () {
        // Включаем кнопку обратно
        submitBtn.disabled = false;
        submitBtn.textContent = 'Отправить';
    });
});

// Обработчики событий для открытия/закрытия попапа
openFormBtn.addEventListener('click', openPopup);
closeBtn.addEventListener('click', closePopup);
popupOverlay.addEventListener('click', closePopup);

// Обработчик кнопки "Назад" в браузере
window.addEventListener('popstate', function (e) {
    if (popup.classList.contains('active')) {
        popup.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Проверка URL при загрузке страницы
window.addEventListener('load', function () {
    if (window.location.hash === '#contact') {
        openPopup();
    }
});

// Автоматическое сохранение данных формы при изменении
Object.keys(formFields).forEach(function (key) {
    const field = formFields[key];
    
    if (field.type === 'checkbox') {
        field.addEventListener('change', saveFormData);
    } else {
        field.addEventListener('input', saveFormData);
    }
});

// Закрытие попапа по Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && popup.classList.contains('active')) {
        closePopup();
    }
});