define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/storage'
    ],
    function (
        $,
        ko,
        Component,
        placeOrderAction,
        customer,
        additionalValidators,
        url,
        urlBuilder,
        errorProcessor,
        fullScreenLoader,
        redirectOnSuccessAction,
        storage
    ) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: true,
            defaults: {
                template: 'Atoa_AtoaPayment/payment/atoa'
            },

            initialize: function () {
                this._super();
                this.popupOpen = ko.observable(false);

                var self = this;
                document.addEventListener('click', function () {
                    if (self.popupOpen()) {
                        self.popupOpen(false);
                    }
                });

                return this;
            },

            /**
             * Toggle the +N popup open/closed (touch support).
             *
             * @param {Object} vm
             * @param {Event} event
             */
            togglePopup: function (vm, event) {
                event.stopPropagation();
                this.popupOpen(!this.popupOpen());
            },

            /**
             * Returns 'CARD' for the atoa_card method, 'PAY_BY_BANK' for everything else.
             *
             * @return {string}
             */
            getPaymentType: function () {
                return this.getCode() === 'atoa_card' ? 'CARD' : 'PAY_BY_BANK';
            },

            /**
             * Provide additional payment data so the server side knows the payment type.
             *
             * @return {Object}
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_type': this.getPaymentType()
                    }
                };
            },

            placeOrder: function (data, event) {
                var self = this;
                if (event) {
                    event.preventDefault();
                }

                self.startPerformingPlaceOrderAction();

                var emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';

                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean(
                        $(loginFormSelector + ' input[name=username]').valid()
                    );
                }

                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    self.getPlaceOrderDeferredObject().fail(
                        function (response) {
                            console.error('[Atoa] placeOrder failed', response);
                            errorProcessor.process(response, self.messageContainer);
                            fullScreenLoader.stopLoader();
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(
                        function (orderId) {
                            var serviceUrl = urlBuilder.createUrl(
                                '/atoa/:orderId/redirect/:paymentType',
                                {
                                    orderId: orderId,
                                    paymentType: self.getPaymentType()
                                }
                            );
                            storage.post(serviceUrl).fail(
                                function (response) {
                                    console.error('[Atoa] redirect request failed', response);
                                    errorProcessor.process(response, self.messageContainer);
                                    fullScreenLoader.stopLoader();
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                                function (response) {
                                    if (response && response.redirect_url) {
                                        $.mage.redirect(response.redirect_url);
                                    } else {
                                        console.error('[Atoa] missing redirect_url in response', response);
                                        errorProcessor.process(response, self.messageContainer);
                                        fullScreenLoader.stopLoader();
                                        self.isPlaceOrderActionAllowed(true);
                                    }
                                }
                            );
                        }
                    );
                    return true;
                }

                self.stopPerformingPlaceOrderAction();
                return false;
            },

            /**
             * Start performing place order action,
             * by disabling the place order button and showing full screen loader.
             */
            startPerformingPlaceOrderAction: function () {
                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
            },

            /**
             * Stop performing place order action,
             * by re-enabling the place order button and hiding the full screen loader.
             */
            stopPerformingPlaceOrderAction: function () {
                fullScreenLoader.stopLoader();
                this.isPlaceOrderActionAllowed(true);
            },

            /**
             * @return {*}
             */
            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            getLogoMarkupSrc: function () {
                return window.checkoutConfig.payment.atoa.logoMarkHref;
            },

            /**
             * All bank logos for the 'atoa' payment method.
             *
             * @return {Array<{src: string, alt: string}>}
             */
            getBankLogos: function () {
                return (window.checkoutConfig.payment.atoa.bankConfig || {}).logos || [];
            },

            /**
             * First 4 logos shown inline — the rest are in the hover popup.
             *
             * @return {Array}
             */
            getVisibleBankLogos: function () {
                return this.getBankLogos().slice(0, 4);
            },

            /**
             * Logos that appear only in the hover popup (+N bubble).
             *
             * @return {Array}
             */
            getHiddenBankLogos: function () {
                return this.getBankLogos().slice(4);
            },

            /**
             * Card logos for the 'atoa_card' payment method.
             *
             * @return {Array<{src: string, alt: string}>}
             */
            getCardLogos: function () {
                return (window.checkoutConfig.payment.atoa_card &&
                    window.checkoutConfig.payment.atoa_card.cardConfig &&
                    window.checkoutConfig.payment.atoa_card.cardConfig.logos) || [];
            },

            getBannerCheckoutText: function () {
                var config = window.checkoutConfig.payment[this.getCode()];
                return (config && config.bannerCheckoutText) ||
                    window.checkoutConfig.payment.atoa.bannerCheckoutText;
            },

            getStyle: function () {
                var config = window.checkoutConfig.payment[this.getCode()];
                var style = (config && config.style) || window.checkoutConfig.payment.atoa.style;
                return 'atoa-payment-checkout style' + style;
            },

            /**
             * True when this renderer is handling the bank payment method.
             *
             * @return {boolean}
             */
            isBank: function () {
                return this.getCode() === 'atoa';
            },

            /**
             * True when this renderer is handling the card payment method.
             *
             * @return {boolean}
             */
            isCard: function () {
                return this.getCode() === 'atoa_card';
            }
        });
    }
);
