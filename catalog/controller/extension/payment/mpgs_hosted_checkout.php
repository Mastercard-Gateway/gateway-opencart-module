<?php
/**
 * Copyright (c) 2020 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Class ControllerExtensionPaymentMpgsHostedCheckout
 */
class ControllerExtensionPaymentMpgsHostedCheckout extends Controller
{
    const ORDER_CAPTURED = '2';
    const ORDER_AUTHORIZED = '1';
    const ORDER_FAILED = '10';
    const ORDER_CANCELED = '7';

    protected $orderAmount = 0;

    /**
     * @return mixed
     */
    public function index()
    {
        $this->load->language('extension/payment/mpgs_hosted_checkout');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $gatewayUri = $this->model_extension_payment_mpgs_hosted_checkout->getGatewayUri();
        $apiVersion = $this->model_extension_payment_mpgs_hosted_checkout->getApiVersion();
        $integrationModel = $this->model_extension_payment_mpgs_hosted_checkout->getIntegrationModel();

        if (!empty($this->session->data['order_id']) && !empty($this->session->data['currency']) && !empty($this->session->data['payment_address'])) {
            try {
                if ($integrationModel === 'hostedcheckout') {
                    $built = $this->buildCheckoutSession();
                    if ($built === true) {
                        $data['configured_variables'] = json_encode($this->configureHostedCheckout());
                    }
                } elseif ($integrationModel === 'hostedsession'){
                    $this->getOrderItemsTaxAndTotals();
                    $gatewayOrderId = $this->getOrderPrefix($this->session->data['order_id']);
                    $response = $this->createSession($gatewayOrderId, $this->orderAmount);
                    if ($response['result'] === 'SUCCESS') {
                        $data['session_id'] = $response['session']['id'];
                        $data['session_version'] = $response['session']['version'];
                        $data['merchant_id'] = $response['merchant'];
                        $data['ws_version'] = $this->model_extension_payment_mpgs_hosted_checkout->getApiVersion();
                        $data['order_id'] = $gatewayOrderId;
                        $data['currency'] = $this->session->data['currency'];
                        $data['amount'] = $this->orderAmount;
                    }
                    $data['update_session_action'] = $this->url->link('extension/payment/mpgs_hosted_checkout/updateSession', 'order_id=' . $gatewayOrderId, true);
                    $data['form_action'] = $this->url->link('extension/payment/mpgs_hosted_checkout/saveHostedSessionPayment', 'order_id=' . $gatewayOrderId . '&amount=' . $this->orderAmount, true);
                }
            } catch (Exception $e) {
                $data['error_session'] = $e->getMessage();
            }
        }

        if (empty($data['error_session'])) {
            if ($integrationModel === 'hostedcheckout') {
                $data['hosted_checkout_js'] = $gatewayUri . 'checkout/version/' . $apiVersion . '/checkout.js';
                $data['checkout_interaction'] = $this->config->get('payment_mpgs_hosted_checkout_hc_type');
                $data['completeCallback'] = $this->url->link('extension/payment/mpgs_hosted_checkout/processHostedCheckout', '', true);
                $data['cancelCallback'] = $this->url->link('extension/payment/mpgs_hosted_checkout/cancelCallback', '', true);
            } else {
                if ($this->customer->isLogged()) {
                    $data['savedCards'] = $this->getTokenizeCards($this->customer->getId());
                    $data['text_save_card'] = $this->language->get('text_save_card');
                } else {
                    $data['savedCards'] = [];
                }

                // Entry
                $data['entry_cc_number'] = $this->language->get('entry_cc_number');
                $data['entry_expiry_month'] = $this->language->get('entry_expiry_month');
                $data['entry_expiry_year'] = $this->language->get('entry_expiry_year');
                $data['entry_security_code'] = $this->language->get('entry_security_code');
                $data['entry_cardholder_name'] = $this->language->get('entry_cardholder_name');
                $data['text_credit_card'] = $this->language->get('text_credit_card');

                // Error
                $data['error_card_number'] = $this->language->get('error_card_number');
                $data['error_expiry_month'] = $this->language->get('error_expiry_month');
                $data['error_expiry_year'] = $this->language->get('error_expiry_year');
                $data['error_security_code'] = $this->language->get('error_security_code');

                $data['isSavedCardsEnabled'] = $this->model_extension_payment_mpgs_hosted_checkout->isSavedCardsEnabled();
            }
        }

        if ($integrationModel === 'hostedcheckout') {
            return $this->load->view('extension/payment/mpgs_hosted_checkout', $data);
        } else {
            return $this->load->view('extension/payment/mpgs_hosted_session', $data);
        }
    }

    /**
     * @param $route
     */
    public function init($route)
    {
        $allowed = ['checkout/checkout'];

        if (!in_array($route, $allowed)) {
            return;
        }

        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $gatewayUri = $this->model_extension_payment_mpgs_hosted_checkout->getGatewayUri();
        $apiVersion = $this->model_extension_payment_mpgs_hosted_checkout->getApiVersion();
        $integrationModel = $this->model_extension_payment_mpgs_hosted_checkout->getIntegrationModel();
        $apiUsername = $this->model_extension_payment_mpgs_hosted_checkout->getApiUsername();

        if ($integrationModel === 'hostedsession') {
            $hostedSessionJs = $gatewayUri . 'form/version/' . $apiVersion . '/merchant/' . $apiUsername . '/session.js';
            $this->document->addScript($hostedSessionJs);
            $threeDsApiVersion = $this->model_extension_payment_mpgs_hosted_checkout->threeDSApiVersion();
            $threeDsJS = $gatewayUri . 'static/threeDS/' . $threeDsApiVersion . '/three-ds.min.js';
            $this->document->addScript($threeDsJS);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function buildCheckoutSession()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');
        $this->model_extension_payment_mpgs_hosted_checkout->clearCheckoutSession();

        $requestData = [
            'apiOperation' => 'CREATE_CHECKOUT_SESSION',
            'partnerSolutionId' => $this->model_extension_payment_mpgs_hosted_checkout->buildPartnerSolutionId(),
            'order' => array_merge($this->getOrder(), $this->getOrderItemsTaxAndTotals()),
            'interaction' => $this->getInteraction(),
            'billing' => $this->getBillingAddress(),
            'customer' => $this->getCustomer()
        ];

        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/session';
        $response = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('POST', $uri, $requestData);

        if (!empty($response['result']) && $response['result'] === 'SUCCESS') {
            if ($this->model_extension_payment_mpgs_hosted_checkout->getIntegrationModel() === 'hostedcheckout') {
                $this->session->data['mpgs_hosted_checkout'] = $response;
            } else {
                $this->session->data['mpgs_hosted_session'] = $response;
            }
            return true;
        } elseif (!empty($response['result']) && $response['result'] === 'ERROR') {
            //throw new Exception($response['error']['cause'] . ': ' . $response['error']['explanation']);
            throw new Exception(json_encode($response['error']));
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function getInteraction()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $integration['merchant']['name'] = $this->config->get('config_name');
        $integration['operation'] = $this->model_extension_payment_mpgs_hosted_checkout->getPaymentAction();
        $integration['returnUrl'] = $this->url->link('extension/payment/mpgs_hosted_checkout/processHostedCheckout', '', true);
        $integration['displayControl']['shipping'] = 'HIDE';
        $integration['displayControl']['billingAddress'] = 'HIDE';
        $integration['displayControl']['orderSummary'] = 'HIDE';
        $integration['displayControl']['paymentConfirmation'] = 'HIDE';
        $integration['displayControl']['customerEmail'] = 'HIDE';

        return $integration;
    }

    /**
     * @return mixed
     */
    protected function getOrder()
    {
        $orderData['id'] = $this->getOrderPrefix($this->session->data['order_id']);
        $orderData['currency'] = $this->session->data['currency'];
        $orderData['description'] = 'Ordered goods';
        $orderData['notificationUrl'] = $this->url->link('extension/payment/mpgs_hosted_checkout/callback', '', true);

        return $orderData;
    }

    /**
     * Order items, tax and order totals
     *
     * @return array
     */
    protected function getOrderItemsTaxAndTotals()
    {
        $orderData = [];
        $sendLineItems = $this->config->get('payment_mpgs_hosted_checkout_send_line_items');
        if ($sendLineItems) {
            $this->load->model('catalog/product');
            foreach ($this->cart->getProducts() as $product) {
                $productModel = $this->model_catalog_product->getProduct($product['product_id']);

                $items = [];
                if ($productModel['manufacturer']) {
                    $items['brand'] = utf8_substr($productModel['manufacturer'], 0, 127);
                }

                $description = [];
                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = isset($option['value']) ? $option['value'] : '';
                    } else {
                        $uploadInfo = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($uploadInfo) {
                            $value = $uploadInfo['name'];
                        } else {
                            $value = '';
                        }
                    }
                    $description[] = $option['name'] . ':' . (utf8_strlen($value) > 20 ? utf8_substr($value, 0,
                                20) . '..' : $value);
                }
                if (!empty($description)) {
                    $items['description'] = utf8_substr(implode(', ', $description), 0, 127);
                } elseif ($product['model']) {
                    $items['description'] = utf8_substr($product['model'], 0, 127);
                }
                $items['name'] = utf8_substr($product['name'], 0, 127);
                $items['quantity'] = $product['quantity'];
                if ($product['model']) {
                    $items['sku'] = utf8_substr($product['model'], 0, 127);
                }
                $items['unitPrice'] = round($product['price'], 2);

                $orderData['item'][] = $items;
            }
        }

        /** Tax, Shipping, Discount and Order Total */
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $totalData = [
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        ];

        $this->load->model('setting/extension');

        // Display prices
        $sorOrder = [];
        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sorOrder[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sorOrder, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($totalData);
            }

            $sorOrder = [];
            foreach ($totals as $key => $value) {
                $sorOrder[$key] = $value['sort_order'];
            }

            array_multisort($sorOrder, SORT_ASC, $totals);
        }

        $skipTotals = [
            'sub_total',
            'total',
            'tax'
        ];

        $formattedTotal = round($total, 2);
        $subTotal = 0;
        $tax = 0;
        $taxInfo = [];
        $shipping = 0;

        foreach ($totals as $key => $value) {
            $formattedValue = round($value['value'], 2);

            if ($value['code'] == 'sub_total') {
                $subTotal += $formattedValue;
            }

            if ($value['code'] == 'tax') {
                $tax += $formattedValue;
                $taxInfo[] = [
                    'amount' => $formattedValue,
                    'type' => $value['title']
                ];
            }

            if (!in_array($value['code'], $skipTotals)) {
                $shipping += $formattedValue;
            }
        }

        $finalTotal = $subTotal + $tax + $shipping;
        if ($finalTotal == $formattedTotal) {
            $this->orderAmount = $formattedTotal;
            $orderData['amount'] = $formattedTotal;
            if ($sendLineItems) {
                $orderData['itemAmount'] = $subTotal;
                $orderData['shippingAndHandlingAmount'] = $shipping;
                $orderData['taxAmount'] = $tax;
            }
        }

        /** Order Tax Details */
        if (!empty($taxInfo) && $sendLineItems) {
            $orderData['tax'] = $taxInfo;
        }

        return $orderData;
    }

    /**
     * @return array
     */
    protected function getBillingAddress()
    {
        $billingAddress = [];
        $paymentAddress = $this->session->data['payment_address'];
        if (!empty($paymentAddress['city'])) {
            $billingAddress['address']['city'] = utf8_substr($paymentAddress['city'], 0, 100);
        }

        if (!empty($paymentAddress['company'])) {
            $billingAddress['address']['company'] = $paymentAddress['company'];
        }

        if (!empty($paymentAddress['iso_code_3'])) {
            $billingAddress['address']['country'] = $paymentAddress['iso_code_3'];
        }

        if (!empty($paymentAddress['postcode'])) {
            $billingAddress['address']['postcodeZip'] = utf8_substr($paymentAddress['postcode'], 0, 10);
        }

        if (!empty($paymentAddress['zone'])) {
            $billingAddress['address']['stateProvince'] = utf8_substr($paymentAddress['zone'], 0, 20);
        }

        if (!empty($paymentAddress['address_1'])) {
            $billingAddress['address']['street'] = utf8_substr($paymentAddress['address_1'], 0, 100);
        }

        if (!empty($paymentAddress['address_2'])) {
            $billingAddress['address']['street2'] = utf8_substr($paymentAddress['address_2'], 0, 100);
        }

        return $billingAddress;
    }

    /**
     * @return array
     */
    protected function getCustomer()
    {
        $customerData = [];
        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');

            $customerModel = $this->model_account_customer->getCustomer($this->customer->getId());

            $customerData['firstName'] = utf8_substr($customerModel['firstname'], 0, 50);
            $customerData['lastName'] = utf8_substr($customerModel['lastname'], 0, 50);
            $customerData['email'] = $customerModel['email'];
            $customerData['phone'] = utf8_substr($customerModel['telephone'], 0, 20);
        } elseif (isset($this->session->data['guest'])) {
            $guestUser = $this->session->data['guest'];

            $customerData['firstName'] = utf8_substr($guestUser['firstname'], 0, 50);
            $customerData['lastName'] = utf8_substr($guestUser['lastname'], 0, 50);
            $customerData['email'] = $guestUser['email'];
            $customerData['phone'] = utf8_substr($guestUser['telephone'], 0, 20);
        }

        return $customerData;
    }

    /**
     * Process Hosted Checkout Payment Method
     */
    public function processHostedCheckout()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $requestIndicator = $this->request->get['resultIndicator'];
        $mpgsSuccessIndicator = $this->session->data['mpgs_hosted_checkout']['successIndicator'];
        $orderId = $this->getOrderPrefix($this->session->data['order_id']);

        try {
            if ($mpgsSuccessIndicator !== $requestIndicator) {
                throw new Exception('Result indicator has mismatched.');
            }

            $retrievedOrder = $this->retrieveOrder($orderId);
            if ($retrievedOrder['result'] !== 'SUCCESS') {
                throw new Exception('Payment declined.');
            }

            $txn = $retrievedOrder['transaction'][0];
            $this->processOrder($retrievedOrder, $txn);

            $this->model_extension_payment_mpgs_hosted_checkout->clearCheckoutSession();
            $this->response->redirect($this->url->link('checkout/success', '', true));
        } catch (Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            $this->addOrderHistory($orderId, self::ORDER_FAILED, $e->getMessage());
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }

    public function callback()
    {
        //$this->addOrderHistory('435', '11', 'Payment has been REFUNDED via Webhook Notification.');
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getTokenizeCards($customerId)
    {
        $this->load->language('extension/payment/mpgs_hosted_checkout');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $customerTokens = $this->model_extension_payment_mpgs_hosted_checkout->getCustomerTokens($customerId);
        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/token/';

        $cards = [];

        foreach ($customerTokens as $token) {
            $response = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('GET', $uri . urlencode($token['token']));

            if ($response['result'] !== 'SUCCESS' || $response['status'] !== 'VALID') {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "mpgs_hpf_token` WHERE hpf_token_id='" . (int)$token['hpf_token_id'] . "'");
            } else {
                $expiry = [];
                $cardNumber = substr($response['sourceOfFunds']['provided']['card']['number'], - 4);
                preg_match( '/^(\d{2})(\d{2})$/', $response['sourceOfFunds']['provided']['card']['expiry'], $expiry);

                $cards[] = [
                    'id' => (int)$token['hpf_token_id'],
                    'type' => sprintf($this->language->get('text_card_type'), ucfirst(strtolower($response['sourceOfFunds']['provided']['card']['brand']))),
                    'label' => sprintf($this->language->get('text_card_label'), $cardNumber),
                    'expiry' => sprintf($this->language->get('text_card_expiry'), $expiry[1] . '/' . $expiry[2])
                ];
            }
        }

        return $cards;
    }

    protected function getTokenById($tokenId)
    {
        $tokensResult = $this->db->query("SELECT token FROM `" . DB_PREFIX . "mpgs_hpf_token` WHERE hpf_token_id='" . (int)$tokenId . "'");
        return $tokensResult->row;
    }

    public function createSession($orderId, $amount)
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        // Create Session
        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/session';
        $session = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('POST', $uri);

        // Update Session
        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/session/' . $session['session']['id'];
        $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('PUT', $uri, [
            'order' => [
                'amount' => $amount,
                'currency' => $this->session->data['currency'],
                'id' => $orderId
            ],
            'partnerSolutionId' => $this->model_extension_payment_mpgs_hosted_checkout->buildPartnerSolutionId(),
            'authentication' => [
                'channel' => 'PAYER_BROWSER',
                'purpose' => 'PAYMENT_TRANSACTION',
                'redirectResponseUrl' => str_replace('&amp;', '&',
                    $this->url->link('extension/payment/mpgs_hosted_checkout/payerAuthComplete', [
                        'session_id' => $session['session']['id'],
                    ], true)
                )
            ],
            'billing' => $this->getBillingAddress(),
            'customer' => $this->getCustomer(),
        ]);

        return $session;
    }

    /**
     * Controller action
     * Payer returns from 3DS1 or 3DS2 auth challenge
     */
    public function payerAuthComplete()
    {
        $this->load->language('extension/payment/mpgs_hosted_checkout');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $txnId = $this->request->post['transaction_id'];
        $orderId = $this->request->post['order_id'];
        $gatewayRecommendation = $this->request->post['response_gatewayRecommendation'];
        $sessionId = $this->request->request['session_id'];

        try {
            if ($gatewayRecommendation !== 'PROCEED') {
                throw new Exception('Payment declined (3DS). Please try another card.');
            }

            $operation = 'AUTHORIZE';
            if ($this->model_extension_payment_mpgs_hosted_checkout->getPaymentAction() === 'PURCHASE') {
                $operation = 'PAY';
            }

            $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/order/' . $orderId . '/transaction/1';
            $response = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('PUT', $uri, [
                'apiOperation' => $operation,
                'session' => [
                    'id' => $sessionId
                ],
                'authentication' => [
                    'transactionId' => $txnId
                ],
                'transaction' => [
                    'reference' => $orderId
                ],
                'sourceOfFunds' => [
                    'type' => 'CARD'
                ],
                'partnerSolutionId' => $this->model_extension_payment_mpgs_hosted_checkout->buildPartnerSolutionId(),
                'order' => array_merge([
                    'reference' => $orderId,
                    'currency' => $this->session->data['currency'],
                ], $this->getOrderItemsTaxAndTotals()),
                'billing' => $this->getBillingAddress(),
                'customer' => $this->getCustomer(),
            ]);

            if (isset($response['result']) && $response['result'] == 'ERROR') {
                $error = $this->language->get('error_payment_declined');
                if (isset($response['error']['explanation'])) {
                    $error = sprintf('%s: %s', $response['error']['cause'], $response['error']['explanation']);
                }
                throw new Exception($error);
            }

            $this->processOrder($response['order'], $response);

            $enabled = $this->model_extension_payment_mpgs_hosted_checkout->isSavedCardsEnabled();
            $payingWithToken = isset($this->session->data['token_id']) ? (bool)$this->session->data['token_id'] : false;
            if ($enabled && !$payingWithToken && isset($this->session->data['save_card']) && $this->session->data['save_card'] === '1') {
                $this->saveCards([
                    'id' => $sessionId
                ]);
            }

            $this->clearTokenSaveCardSessionData();

            $this->model_extension_payment_mpgs_hosted_checkout->clearCheckoutSession();
            $this->response->redirect($this->url->link('checkout/success', '', true));

        } catch (Exception $e) {
            $this->clearTokenSaveCardSessionData();
            $this->session->data['error'] = $e->getMessage();
            $this->addOrderHistory($orderId, self::ORDER_FAILED, $e->getMessage());
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }

    /**
     * Controller action
     * Process Hosted Session Payment Method
     */
    public function saveHostedSessionPayment()
    {
        $this->load->language('extension/payment/mpgs_hosted_checkout');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        if (isset($this->request->request['mpgs-payment-token']) && $this->request->request['mpgs-payment-token'] !== 'new') {
            $tokenId = $this->request->request['mpgs-payment-token'];

            $this->session->data['token_id'] = $tokenId;
            $token = $this->getTokenById($tokenId);

            $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/session/' . $this->request->request['session_id'];
            $result = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('PUT', $uri, [
                'sourceOfFunds' => [
                    'token' => $token['token'],
                    'type' => 'CARD'
                ]
            ]);

            if (!isset($result['session']) || $result['session']['updateStatus'] !== 'SUCCESS') {
                header('HTTP/1.0 500 Session Update Error');
                exit();
            }
        }
        if (isset($this->request->request['mpgs-save-new-method']) && $this->request->request['mpgs-save-new-method'] === '1') {
            $this->session->data['save_card'] = $this->request->request['mpgs-save-new-method'];
        }
    }

    /**
     * Clear values of Hosted Payment Form
     * fields from session
     */
    protected function clearTokenSaveCardSessionData()
    {
        unset($this->session->data['save_card']);
        unset($this->session->data['token_id']);
        unset($this->session->data['source_of_funds']);
    }

    /**
     * @param $session
     * @throws Exception
     */
    protected function saveCards($session)
    {
        $tokenResponse = $this->createCardToken($session['id']);

        if (!isset($tokenResponse['token']) || empty($tokenResponse['token'])) {
            throw new Exception($this->language->get('error_token_not_present'));
        }

        if ($this->customer->isLogged()) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "mpgs_hpf_token` SET `customer_id`='" . (int)$this->customer->getId() . "', `token`='" . $this->db->escape($tokenResponse['token']) . "', `created_at`=NOW()");
        }
    }

    /**
     * @param $sessionId
     * @return mixed
     */
    protected function createCardToken($sessionId)
    {
        $requestData = [
            'session' => [
                'id' => $sessionId
            ],
            'sourceOfFunds' => [
                'type' => 'CARD'
            ]
        ];

        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/token';
        $response = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('POST', $uri, $requestData);

        return $response;
    }

    /**
     * Cancel callback
     * @todo: used?
     */
    public function cancelCallback()
    {
        $this->addOrderHistory($this->session->data['order_id'], self::ORDER_CANCELED, 'MasterCard Payment transaction has been CANCELED by customer and Payment is pending. So Order has been CANCELED.');
        $this->response->redirect($this->url->link('checkout/cart', '', true));
    }

    /**
     * @param $retrievedOrder
     * @param $txn
     */
    protected function processOrder($retrievedOrder, $txn)
    {
        if ($retrievedOrder['status'] === 'CAPTURED') {
            $message = sprintf('Mastercard payment CAPTURED (ID: %s, Auth Code: %s)', $txn['transaction']['id'], $txn['transaction']['authorizationCode']);
            $orderStatusId = self::ORDER_CAPTURED;
        } elseif ($retrievedOrder['status'] === 'AUTHORIZED') {
            $message = sprintf('Mastercard payment AUTHORIZED (ID: %s, Auth Code: %s)', $txn['transaction']['id'], $txn['transaction']['authorizationCode']);
            $orderStatusId = self::ORDER_AUTHORIZED;
        }
        $this->addOrderHistory($this->session->data['order_id'], $orderStatusId, $message);
        //$this->addOrderHistory($retrievedOrder['id'], $orderStatusId, $message);
    }

    /**
     * @param $orderId
     * @param $orderStatusId
     * @param $message
     */
    protected function addOrderHistory($orderId, $orderStatusId, $message)
    {
        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $message);
    }

    /**
     * @param $orderId
     * @return mixed
     */
    protected function retrieveOrder($orderId)
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $uri = $this->model_extension_payment_mpgs_hosted_checkout->getApiUri() . '/order/' . $orderId;

        $response = $this->model_extension_payment_mpgs_hosted_checkout->apiRequest('GET', $uri);
        return $response;
    }

    /**
     * @return array
     */
    public function configureHostedCheckout()
    {
        $this->load->helper('utf8');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $params = [
            'merchant' => $this->model_extension_payment_mpgs_hosted_checkout->getApiUsername(),
            'session' => [
                'id' => $this->session->data['mpgs_hosted_checkout']['session']['id'],
                'version' => $this->session->data['mpgs_hosted_checkout']['session']['version']
            ]
        ];

        return $params;
    }

    /**
     * @param $orderId
     * @return string
     */
    protected function getOrderPrefix($orderId)
    {
        $prefix = trim($this->config->get('payment_mpgs_hosted_checkout_order_prefix'));
        if (!empty($prefix)) {
            $orderId = $prefix . $orderId;
        }
        return $orderId;
    }
}
