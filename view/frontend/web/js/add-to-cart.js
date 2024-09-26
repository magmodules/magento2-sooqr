define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function ($, customerData) {
    return function() {
        document.addEventListener('submit', (e) => {

            const form = e.target.closest('form');
            if (!form || !form.classList.contains('sqrAddToCart')) {
                return;
            }

            e.preventDefault();

            const button = form.querySelector('button');
            const minicart = document.querySelector('[data-block="minicart"]');

            $.ajax({
                type: 'GET',
                url: $(form).attr('action'),
                data: $(form).serialize(),

                beforeSend() {
                    button.setAttribute('disabled', 'disabled');
                    minicart.dispatchEvent(new CustomEvent('contentLoading'));
                },

                success() {
                    customerData.invalidate(['cart']);
                    customerData.reload(['cart'], true);
                },

                error() {
                    $('.sqr-closeButton').trigger('click');
                    const customerMessages = customerData.get('messages')() || {},
                        messages = customerMessages.messages || [];

                    messages.push({
                        text: 'Something went wrong while adding product to the cart. Please reload page and try again.',
                        type: 'error'
                    });

                    customerMessages.messages = messages;
                    customerData.set('messages', customerMessages);
                },

                complete() {
                    button.removeAttribute('disabled');
                    minicart.dispatchEvent(new CustomEvent('contentUpdated'));
                }
            });

        });
    }
});
