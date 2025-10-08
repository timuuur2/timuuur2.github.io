const serviceTypes = {
    basic: {
        name: 'Базовая услуга',
        price: 1000,
        options: [],
        properties: []
    },
    standard: {
        name: 'Стандартная услуга',
        price: 2000,
        options: [
            { id: 'opt1', name: 'Опция 1', price: 500 },
            { id: 'opt2', name: 'Опция 2', price: 700 }
        ],
        properties: []
    },
    premium: {
        name: 'Премиум услуга',
        price: 3000,
        options: [],
        properties: [
            { id: 'prop1', name: 'Свойство 1', price: 300 },
            { id: 'prop2', name: 'Свойство 2', price: 400 },
            { id: 'prop3', name: 'Свойство 3', price: 600 }
        ]
    }
};

let currentService = null;
let quantity = 1;
let selectedOptions = [];
let selectedProperties = [];

const quantityInput = document.getElementById('quantity');
const serviceTypeRadios = document.querySelectorAll('input[name="service"]');
const optionsContainer = document.getElementById('optionsContainer');
const propertiesContainer = document.getElementById('propertiesContainer');
const productOptionsSelect = document.getElementById('productOptions');
const productOptionsCheckbox = document.getElementById('productOptionsCheckbox');
const productProperties = document.getElementById('productProperties');
const totalPriceElement = document.getElementById('totalPrice');

quantityInput.addEventListener('input', function() {
    quantity = parseInt(this.value) || 1;
    if (quantity < 1) {
        quantity = 1;
        this.value = 1;
    }
    calculatePrice();
});

serviceTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.radio-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        this.parentElement.classList.add('selected');
        
        currentService = serviceTypes[this.value];
        selectedOptions = [];
        selectedProperties = [];
        updateForm();
        calculatePrice();
    });
});

function updateForm() {
    if (!currentService) return;

    // Опции
    if (currentService.options.length > 0) {
        optionsContainer.classList.add('visible');
        productOptionsSelect.classList.remove('hidden');
        productOptionsCheckbox.classList.add('hidden');
        
        productOptionsSelect.innerHTML = '<option value="">Выберите опцию</option>';
        currentService.options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.id;
            option.textContent = `${opt.name} - ${opt.price} ₽`;
            option.dataset.price = opt.price;
            productOptionsSelect.appendChild(option);
        });

        productOptionsSelect.onchange = function() {
            selectedOptions = this.value ? [this.value] : [];
            calculatePrice();
        };
    } else {
        optionsContainer.classList.remove('visible');
    }

    // Свойства
    if (currentService.properties.length > 0) {
        propertiesContainer.classList.add('visible');
        productProperties.innerHTML = '';
        
        currentService.properties.forEach(prop => {
            const label = document.createElement('label');
            label.className = 'checkbox-option';
            label.innerHTML = `
                <input type="checkbox" value="${prop.id}" data-price="${prop.price}">
                <span>${prop.name} - ${prop.price} ₽</span>
            `;
            
            const checkbox = label.querySelector('input');
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    label.classList.add('checked');
                    selectedProperties.push(this.value);
                } else {
                    label.classList.remove('checked');
                    selectedProperties = selectedProperties.filter(id => id !== this.value);
                }
                calculatePrice();
            });
            
            productProperties.appendChild(label);
        });
    } else {
        propertiesContainer.classList.remove('visible');
    }
}

function calculatePrice() {
    if (!currentService) {
        totalPriceElement.textContent = '0 ₽';
        return;
    }

    let basePrice = currentService.price;
    let optionsPrice = 0;
    let propertiesPrice = 0;

    selectedOptions.forEach(optId => {
        const option = currentService.options.find(o => o.id === optId);
        if (option) optionsPrice += option.price;
    });

    selectedProperties.forEach(propId => {
        const property = currentService.properties.find(p => p.id === propId);
        if (property) propertiesPrice += property.price;
    });

    const totalPrice = (basePrice + optionsPrice + propertiesPrice) * quantity;
    totalPriceElement.textContent = `${totalPrice.toLocaleString('ru-RU')} ₽`;
}

calculatePrice();
