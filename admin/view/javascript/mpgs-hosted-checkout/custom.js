/*
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

$(function ($) {
    'use strict';
    var mpgs_admin_config = {
        init: function () {
            var username = $('#username-container'),
                password = $('#password-container'),
                sandbox_username = $('#sandbox-username-container'),
                sandbox_password = $('#sandbox-password-container'),
                gateway_url = $('#custom-url-container'),
                threedsecure = $('#threedsecure-container'),
                saved_cards = $('#saved-cards-container'),
                hc_type = $('#hc-type-container');

            $('#sandbox-mode').on('change', function () {
                if ($(this).val() === '1') {
                    sandbox_username.show();
                    sandbox_password.show();
                    sandbox_username.addClass('required');
                    sandbox_password.addClass('required');

                    // Hide Production Username & Password
                    username.hide();
                    password.hide();
                    username.removeClass('required');
                    password.removeClass('required');
                } else {
                    username.show();
                    password.show();
                    username.addClass('required');
                    password.addClass('required');

                    // Hide Sandbox Username & Password
                    sandbox_username.hide();
                    sandbox_password.hide();
                    sandbox_username.removeClass('required');
                    sandbox_password.removeClass('required');

                }
            }).change();

            $('#select-api-gateway').on('change', function () {
                if ($(this).val() === 'api_other') {
                    gateway_url.show();
                } else {
                    gateway_url.hide();
                }
            }).change();

            $('#integration-model').on('change', function () {
                if ($(this).val() === 'hostedcheckout') {
                    threedsecure.hide();
                    saved_cards.hide();
                    hc_type.show();
                } else {
                    hc_type.hide();
                    threedsecure.show();
                    saved_cards.show();
                }
            }).change();
        }
    };
    mpgs_admin_config.init();
});