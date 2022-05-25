define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function ($, customerData) {
    return function (config, element) {
        let actionSelector = '.sqrAddToCart';
        _wssq.push(['suggest._bindEvent', 'updateResults', function () {
            $(actionSelector).on('submit', function (e) {
                e.preventDefault();
                var submitButton = $(this).find('button[type="submit"]');
                $.ajax({
                    type: "GET",
                    url: $(this).attr('action'),
                    data: $(this).serialize(), // serialize form data
                    beforeSend: function () {
                        submitButton.prop('disabled', true);
                        $('[data-block="minicart"]').trigger('contentLoading');
                    },
                    success: function (data) {
                        var sections = ['cart'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, true);
                    },
                    error: function (result) {
                        $('.sqr-closeButton').trigger('click');
                        var customerMessages = customerData.get('messages')() || {},
                            messages = customerMessages.messages || [];

                        messages.push({
                            text: 'Something went wrong while adding product to the cart. Please reload page and try again.',
                            type: 'error'
                        });

                        customerMessages.messages = messages;
                        customerData.set('messages', customerMessages);
                    },
                    complete: function () {
                        $('[data-block="minicart"]').trigger('contentUpdated');
                        submitButton.prop('disabled', false);
                    }
                });
            });
        }]);
    }
});
