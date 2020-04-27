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
    const API_VERSION = '55';
    const MODULE_VERSION = '1.0.0';

    private $error = [];

    public function index()
    {
        $this->load->language('extension/payment/mpgs_hosted_checkout');
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('payment_mpgs_hosted_checkout', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $this->document->addScript('view/javascript/mpgs-hosted-checkout/custom.js');

        if (isset($this->error['api_username'])) {
            $data['error_api_username'] = $this->error['api_username'];
        } else {
            $data['error_api_username'] = '';
        }

        if (isset($this->error['api_password'])) {
            $data['error_api_password'] = $this->error['api_password'];
        } else {
            $data['error_api_password'] = '';
        }

        if (isset($this->error['api_sandbox_username'])) {
            $data['error_api_sandbox_username'] = $this->error['api_sandbox_username'];
        } else {
            $data['error_api_sandbox_username'] = '';
        }

        if (isset($this->error['api_sandbox_password'])) {
            $data['error_api_sandbox_password'] = $this->error['api_sandbox_password'];
        } else {
            $data['error_api_sandbox_password'] = '';
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/mpgs_hosted_checkout', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/payment/mpgs_hosted_checkout', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['module_version'] = self::MODULE_VERSION;
        $data['api_version'] = self::API_VERSION;

        if (isset($this->request->post['payment_mpgs_hosted_checkout_status'])) {
            $data['payment_mpgs_hosted_checkout_status'] = $this->request->post['payment_mpgs_hosted_checkout_status'];
        } else {
            $data['payment_mpgs_hosted_checkout_status'] = $this->config->get('payment_mpgs_hosted_checkout_status');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_payment_action'])) {
            $data['payment_mpgs_hosted_checkout_payment_action'] = $this->request->post['payment_mpgs_hosted_checkout_payment_action'];
        } else {
            $data['payment_mpgs_hosted_checkout_payment_action'] = $this->config->get('payment_mpgs_hosted_checkout_payment_action') ? : 'authorize';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_title'])) {
            $data['payment_mpgs_hosted_checkout_title'] = $this->request->post['payment_mpgs_hosted_checkout_title'];
        } else {
            $data['payment_mpgs_hosted_checkout_title'] = $this->config->get('payment_mpgs_hosted_checkout_title') ? : 'Mastercard Payment Gateway Services';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_api_username'])) {
            $data['payment_mpgs_hosted_checkout_api_username'] = $this->request->post['payment_mpgs_hosted_checkout_api_username'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_username'] = $this->config->get('payment_mpgs_hosted_checkout_api_username');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_api_password'])) {
            $data['payment_mpgs_hosted_checkout_api_password'] = $this->request->post['payment_mpgs_hosted_checkout_api_password'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_password'] = $this->config->get('payment_mpgs_hosted_checkout_api_password');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_api_sandbox_username'])) {
            $data['payment_mpgs_hosted_checkout_api_sandbox_username'] = $this->request->post['payment_mpgs_hosted_checkout_api_sandbox_username'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_sandbox_username'] = $this->config->get('payment_mpgs_hosted_checkout_api_sandbox_username');
        }

        if (isset($this->request->post['payment_pp_express_sandbox_password'])) {
            $data['payment_mpgs_hosted_checkout_api_sandbox_password'] = $this->request->post['payment_mpgs_hosted_checkout_api_sandbox_password'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_sandbox_password'] = $this->config->get('payment_mpgs_hosted_checkout_api_sandbox_password');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_webhook_secret'])) {
            $data['payment_mpgs_hosted_checkout_webhook_secret'] = $this->request->post['payment_mpgs_hosted_checkout_webhook_secret'];
        } else {
            $data['payment_mpgs_hosted_checkout_webhook_secret'] = $this->config->get('payment_mpgs_hosted_checkout_webhook_secret');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_api_gateway'])) {
            $data['payment_mpgs_hosted_checkout_api_gateway'] = $this->request->post['payment_mpgs_hosted_checkout_api_gateway'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_gateway'] = $this->config->get('payment_mpgs_hosted_checkout_api_gateway') ? : 'api_eu';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_api_gateway_other'])) {
            $data['payment_mpgs_hosted_checkout_api_gateway_other'] = $this->request->post['payment_mpgs_hosted_checkout_api_gateway_other'];
        } else {
            $data['payment_mpgs_hosted_checkout_api_gateway_other'] = $this->config->get('payment_mpgs_hosted_checkout_api_gateway_other');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_test'])) {
            $data['payment_mpgs_hosted_checkout_test'] = $this->request->post['payment_mpgs_hosted_checkout_test'];
        } else {
            $data['payment_mpgs_hosted_checkout_test'] = $this->config->get('payment_mpgs_hosted_checkout_test');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_integration_model'])) {
            $data['payment_mpgs_hosted_checkout_integration_model'] = $this->request->post['payment_mpgs_hosted_checkout_integration_model'];
        } else {
            $data['payment_mpgs_hosted_checkout_integration_model'] = $this->config->get('payment_mpgs_hosted_checkout_integration_model') ? : 'hostedcheckout';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_hc_type'])) {
            $data['payment_mpgs_hosted_checkout_hc_type'] = $this->request->post['payment_mpgs_hosted_checkout_hc_type'];
        } else {
            $data['payment_mpgs_hosted_checkout_hc_type'] = $this->config->get('payment_mpgs_hosted_checkout_hc_type') ? : 'redirect';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_saved_cards'])) {
            $data['payment_mpgs_hosted_checkout_saved_cards'] = $this->request->post['payment_mpgs_hosted_checkout_saved_cards'];
        } else {
            $data['payment_mpgs_hosted_checkout_saved_cards'] = ($this->config->get('payment_mpgs_hosted_checkout_saved_cards') === '0') ? '0' : '1';
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_debug'])) {
            $data['payment_mpgs_hosted_checkout_debug'] = $this->request->post['payment_mpgs_hosted_checkout_debug'];
        } else {
            $data['payment_mpgs_hosted_checkout_debug'] = $this->config->get('payment_mpgs_hosted_checkout_debug');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_order_status_id'])) {
            $data['payment_mpgs_hosted_checkout_order_status_id'] = $this->request->post['payment_mpgs_hosted_checkout_order_status_id'];
        } else {
            $data['payment_mpgs_hosted_checkout_order_status_id'] = $this->config->get('payment_mpgs_hosted_checkout_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_mpgs_hosted_checkout_send_line_items'])) {
            $data['payment_mpgs_hosted_checkout_send_line_items'] = $this->request->post['payment_mpgs_hosted_checkout_send_line_items'];
        } else {
            $data['payment_mpgs_hosted_checkout_send_line_items'] = $this->config->get('payment_mpgs_hosted_checkout_send_line_items');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_sort_order'])) {
            $data['payment_mpgs_hosted_checkout_sort_order'] = $this->request->post['payment_mpgs_hosted_checkout_sort_order'];
        } else {
            $data['payment_mpgs_hosted_checkout_sort_order'] = $this->config->get('payment_mpgs_hosted_checkout_sort_order');
        }

        if (isset($this->request->post['payment_mpgs_hosted_checkout_order_prefix'])) {
            $data['payment_mpgs_hosted_checkout_order_prefix'] = $this->request->post['payment_mpgs_hosted_checkout_order_prefix'];
        } else {
            $data['payment_mpgs_hosted_checkout_order_prefix'] = $this->config->get('payment_mpgs_hosted_checkout_order_prefix');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/mpgs_hosted_checkout', $data));
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/mpgs_hosted_checkout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->request->post['payment_mpgs_hosted_checkout_test']) {
            if (!$this->request->post['payment_mpgs_hosted_checkout_api_sandbox_username']) {
                $this->error['api_sandbox_username'] = $this->language->get('error_api_sandbox_username');
            }
            if (!$this->request->post['payment_mpgs_hosted_checkout_api_sandbox_password']) {
                $this->error['api_sandbox_password'] = $this->language->get('error_api_sandbox_password');
            }
        } else {
            if (!$this->request->post['payment_mpgs_hosted_checkout_api_username']) {
                $this->error['api_username'] = $this->language->get('error_api_username');
            }
            if (!$this->request->post['payment_mpgs_hosted_checkout_api_password']) {
                $this->error['api_password'] = $this->language->get('error_api_password');
            }
        }

        return !$this->error;
    }

    public function install()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');
        $this->model_extension_payment_mpgs_hosted_checkout->createTable();
        $this->hook_events();
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');
        $this->model_extension_payment_mpgs_hosted_checkout->dropTable();
        $this->model_extension_payment_mpgs_hosted_checkout->removeEvents();
    }

    public function hook_events()
    {
        $this->load->model('extension/payment/mpgs_hosted_checkout');

        $this->model_extension_payment_mpgs_hosted_checkout->removeEvents();
        $this->model_extension_payment_mpgs_hosted_checkout->addEvents();
    }
}
