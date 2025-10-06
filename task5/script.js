document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('orderForm');
    const quantityInput = document.getElementById('quantity');
    const productSelect = document.getElementById('product');
    const resultDiv = document.getElementById('result');
    const totalAmountDiv = document.getElementById('totalAmount');
    const quantityError = document.getElementById('quantityError');
    const productError = document.getElementById('productError');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Сброс ошибок
        quantityError.style.display = 'none';
        productError.style.display = 'none';
        
        let isValid = true;
        
        const quantity = parseInt(quantityInput.value);
        const productPrice = parseFloat(productSelect.value);
        
        if (!quantity || quantity < 1) {
            quantityError.textContent = 'Пожалуйста, введите корректное количество товара';
            quantityError.style.display = 'block';
            isValid = false;
        }
        
        if (!productPrice) {
            productError.textContent = 'Пожалуйста, выберите товар из списка';
            productError.style.display = 'block';
            isValid = false;
        }
        
        if (isValid) {
            const totalCost = quantity * productPrice;
            totalAmountDiv.textContent = totalCost.toLocaleString('ru-RU') + ' ₽';
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    // Скрытие результата при изменении полей
    quantityInput.addEventListener('input', function() {
        resultDiv.style.display = 'none';
        quantityError.style.display = 'none';
    });

    productSelect.addEventListener('change', function() {
        resultDiv.style.display = 'none';
        productError.style.display = 'none';
    });
});
