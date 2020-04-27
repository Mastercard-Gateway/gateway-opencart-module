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

// Heading
$_['heading_title']					 = 'MPGS Hosted Checkout';

// Text
$_['text_extension']				 = 'Extensions';
$_['text_success']				     = 'Success: You have modified MPGS Hosted Checkout details!';
$_['text_edit']                      = 'Edit MPGS Hosted Checkout';
$_['text_sale']                      = 'Purchase (Pay)';
$_['text_authorize']                 = 'Authorize & Capture';
$_['text_api_eu']                    = 'Europe';
$_['text_api_as']                    = 'Asia Pacific';
$_['text_api_na']                    = 'North America';
$_['text_api_uat']                   = 'UAT';
$_['text_api_other']                 = 'Custom URL';
$_['text_redirect']                  = 'Redirect Payment Page';
$_['text_modal']                     = 'Lightbox';
$_['text_hostedcheckout']            = 'Hosted Checkout';
$_['text_hostedsession']             = 'Hosted Session';

// Help
$_['help_title']                     = 'This controls the title which the user sees during checkout.';
$_['help_webhook_secret']            = 'Be sure to enable the WebHook support in your MasterCard Merchant Administration';
$_['help_debug_mode']                = 'Debug logging only works with Sandbox mode. It will log all communication of Mastercard gateway into /storage/logs/mpgs_gateway.log file.';
$_['help_order_prefix']              = 'Should be specified in case multiple integrations use the same Merchant ID';

// Entry
$_['entry_status']					 = 'Status';
$_['entry_api_username']			 = 'API Username';
$_['entry_api_password']		     = 'API Password';
$_['entry_api_sandbox_username']     = 'API Sandbox Username';
$_['entry_api_sandbox_password']     = 'API Sandbox Password';
$_['entry_webhook_secret']           = 'Webhook Secret';
$_['entry_api_gateway']              = 'Gateway';
$_['entry_test']					 = 'Test Mode';
$_['entry_debug']					 = 'Debug';
$_['entry_payment_action']           = 'Payment Action';
$_['entry_title']                    = 'Title';
$_['entry_order_status']             = 'New Order Status';
$_['entry_form_title']               = 'Payment Modal Title';
$_['entry_api_gateway_other']        = 'Custom Gateway URL';
$_['entry_sort_order']               = 'Sort Order';
$_['entry_send_line_items']          = 'Send Line Items';
$_['entry_hc_type']                  = 'Checkout Interaction';
$_['entry_integration_model']        = 'Integration Model';
$_['entry_threedsecure']             = '3D-Secure';
$_['entry_saved_cards']              = 'Saved Cards';
$_['entry_order_prefix']             = 'Gateway Order ID Prefix';
$_['entry_module_version']           = 'Module Version:';
$_['entry_api_version']              = 'API Version:';

// Tab
$_['tab_api']				         = 'API Credentials';
$_['tab_general']				     = 'General';
$_['tab_additional']				 = 'Additional Options';

// Error
$_['error_permission']	             = 'Warning: You do not have permission to modify payment MPGS Hosted Checkout!';
$_['error_api_username']			 = 'API Username Required!';
$_['error_api_password']			 = 'API Password Required!';
$_['error_api_sandbox_username']	 = 'API Sandbox Username Required!';
$_['error_api_sandbox_password']	 = 'API Sandbox Password Required!';
$_['error_api_gateway_other']	     = "Custom Gateway URL must be specified if Gateway is set to 'Custom URL'";
