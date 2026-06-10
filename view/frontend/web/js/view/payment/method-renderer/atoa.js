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

            /**
             * Get the actual payment type based on selected payment method
             */
            getPaymentType: function () {
                let paymentType = 'PAY_BY_BANK';
                let paymentCode = this.getCode && typeof this.getCode === 'function' ? this.getCode() : null;
                
                // Primary check: use getCode() if available
                if (paymentCode === 'atoa_card') {
                    paymentType = 'CARD';
                } else if (paymentCode === 'atoa') {
                    paymentType = 'PAY_BY_BANK';
                } else {
                    // Fallback: check which radio button is selected
                    let selectedPayment = $('input[name="payment[method]"]:checked').val();
                    console.log('Selected payment from DOM:', selectedPayment);
                    if (selectedPayment === 'atoa_card') {
                        paymentType = 'CARD';
                    }
                }
                
                console.log('Determined paymentType:', paymentType, 'paymentCode:', paymentCode);
                return paymentType;
            },

            getData: function () {
                return {
                    method: this.getCode(),
                    additional_data: {
                        payment_type: this.getPaymentType()
                    }
                };
            },

            placeOrder: function (data, event) {
                let self = this;
                if (event) {
                    event.preventDefault();
                }

                self.startPerformingPlaceOrderAction();

                let emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    self.getPlaceOrderDeferredObject().fail(
                        function (response) {
                            errorProcessor.process(response, self.messageContainer);
                            fullScreenLoader.stopLoader();
                            self.isPlaceOrderActionAllowed(true);
                        }
                    ).done(
                        function (response) {
                                    let serviceUrl = urlBuilder.createUrl(
                                '/atoa/:orderId/redirect/:paymentType',
                                {
                                    orderId: response,
                                    paymentType: self.getPaymentType()
                                }
                            );
                            storage.post(serviceUrl).fail(
                                function (response) {
                                    console.error('Atoa redirect API error', response);
                                    errorProcessor.process(response, self.messageContainer);
                                    fullScreenLoader.stopLoader();
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                                function (response) {
                                    if (response && response.redirect_url) {
                                        $.mage.redirect(response.redirect_url);
                                    } else {
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
                fullScreenLoader.stopLoader();
                self.isPlaceOrderActionAllowed(true);
                return false;
            },

            /**
             * Start performing place order action,
             * by disable a place order button and show full screen loader component.
             */
            startPerformingPlaceOrderAction: function () {
                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
            },

            /**
             * Stop performing place order action,
             * by disable a place order button and show full screen loader component.
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
                return window.checkoutConfig.payment[this.getCode()].logoMarkHref;
            },

            getBankLogos: function () {
                return window.checkoutConfig.payment[this.getCode()].bankLogos || [];
            },

            getVisibleBankLogos: function () {
                return this.getBankLogos().slice(0, 3);
            },

            getHiddenBankLogos: function () {
                return this.getBankLogos().slice(3);
            },

            getCardLogos: function () {
                return window.checkoutConfig.payment[this.getCode()].cardLogos || [];
            },

            getBannerCheckoutText: function () {
                return window.checkoutConfig.payment[this.getCode()].bannerCheckoutText;
            },

            getStyle: function () {
                return 'atoa-payment-checkout style' + window.checkoutConfig.payment[this.getCode()].style;
            }
        });
    }
);