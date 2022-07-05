window.addEventListener('load', function () {
    // Testing the connection of data source.
    connectionTest();

    // Update input user interface.
    updateInputUserInterface();

    var selector = '#fmpressConnectSettings .category-tabs li a';
    var el = document.querySelectorAll(selector);
    for (var i = 0; i < el.length; i++) {
        el[i].addEventListener('click', function (event) {
            event.preventDefault();
            fmpressConnectHideTabs();
            fmpressConnectShowTab(this);
        });
    }

    // Add event listner for driver.
    var idText = 'driver';
    var el = document.getElementById(idText);
    if (el) {
        el.addEventListener('change', updateInputUserInterface, false);
    }

    // Display the password entry area for the data source.
    var idText = 'setDatabasePassword';
    var el = document.getElementById(idText);
    if (el) {
        el.addEventListener('click', showDataSourcePasswordInput, false);
    }

    // Hide the password entry area of the data source.
    var idText = 'cancelDatabasePassword';
    var el = document.getElementById(idText);
    if (el) {
        el.addEventListener('click', cancelDatasourcePasswordInput, false);
    }

    // Toggle the type attribute of the data source password.
    var idText = 'hideDatabasePassword';
    var el = document.getElementById(idText);
    if (el) {
        el.addEventListener('click', switchDataSourceInputType, false);
    }

    function fmpressConnectHideTabs() {
        // Adjust class names for tabs.
        var selector = '#fmpressConnectSettings .category-tabs li';
        var el = document.querySelectorAll(selector);
        for (var i = 0; i < el.length; i++) {
            el[i].classList.remove('tabs');
            el[i].classList.add('hide-if-no-js');
        }

        // Show tab contents.
        selector = '#fmpressConnectSettings .tabs-panel';
        el = document.querySelectorAll(selector);
        for (var n = 0; n < el.length; n++) {
            el[n].style.display = 'none';
        }
    }

    function fmpressConnectShowTab(el) {
        // Adjust class names for tabs.
        el.parentNode.classList.remove('hide-if-no-js');
        el.parentNode.classList.add('tabs');

        // Show tab contents.
        var targetId = el.getAttribute('href');
        var el = document.querySelector(targetId);
        el.style.display = 'block';
    }

    // Change input user interface.
    function updateInputUserInterface() {
        var idText = 'driver';
        var el = document.getElementById(idText);
        if (el && el.options && typeof el.options[1] !== 'undefined' && el.options[1].selected == true) {
            var selector = 'label[for="databaseUsername"]';
            var el = document.querySelector(selector);
            if (el) {
                el.parentNode.style.display = 'block';
            }

            var selector = 'label[for="databasePassword"]';
            var el = document.querySelector(selector);
            if (el) {
                el.textContent = el.dataset.labelforserver;
            }

            var idText = 'setDatabasePassword';
            var el = document.getElementById(idText);
            if (el) {
                el.textContent = el.dataset.labelforserver;
            }
        }
        if (el && el.options && typeof el.options[2] !== 'undefined' && el.options[2].selected == true) {
            var selector = 'label[for="databaseUsername"]';
            var el = document.querySelector(selector);
            if (el) {
                el.parentNode.style.display = 'none';
            }

            var selector = 'label[for="databasePassword"]';
            var el = document.querySelector(selector);
            if (el) {
                el.textContent = el.dataset.labelforcloud;
            }

            var idText = 'setDatabasePassword';
            var el = document.getElementById(idText);
            if (el) {
                el.textContent = el.dataset.labelforcloud;
            }
        }
    }

    // Display the password entry area for the data source.
    function showDataSourcePasswordInput() {
        var selector = 'div[data-aria="setDatabasePassword"]';
        var el = document.querySelector(selector);
        if (el) {
            el.style.display = 'block';
            hideSetPasswordButton();
        }
    }

    // Hide the password entry area of the data source.
    function cancelDatasourcePasswordInput() {
        var selector = 'div[data-aria="setDatabasePassword"]';
        var el = document.querySelector(selector);
        if (el) {
            el.style.display = 'none';
            showSetPasswordButton();
        }
    }

    // Display a button to set a password.
    function showSetPasswordButton() {
        var idText = 'setDatabasePassword';
        var el = document.getElementById(idText);
        if (el) {
            el.style.display = 'block';
        }
    }

    // Hide the Set Password button.
    function hideSetPasswordButton() {
        var idText = 'setDatabasePassword';
        var el = document.getElementById(idText);
        if (el) {
            el.style.display = 'none';
        }
    }

    // Toggle the type attribute of the data source password.
    function switchDataSourceInputType() {
        var idText = 'databasePassword';
        var el = document.getElementById(idText);
        if (el.type === 'text') {
            el.type = 'password';
            switchDataSourceButtonText('show');
        } else {
            el.type = 'text';
            switchDataSourceButtonText('hide');
        }
    }

    // Toggle text and icons for button to switch password type.
    function switchDataSourceButtonText(action) {
        // Text.
        var buttonText = document.querySelector('#hideDatabasePassword .text');
        // Icon.
        var buttonIcon = document.querySelector('#hideDatabasePassword .dashicons');
        // Toggle.
        if (action === 'show') {
            buttonText.textContent = 'Show';
            buttonIcon.className = 'dashicons dashicons-visibility';
        } else {
            buttonText.textContent = 'Hide';
            buttonIcon.className = 'dashicons dashicons-hidden';
        }
    }

    // Testing the connection of data source.
    function connectionTest() {
        'use strict';
        if (typeof ajaxurl === 'undefined' || typeof localize === 'undefined') {
            return;
        }

        var btn = document.getElementById('connectionTest');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function (event) {
            event.preventDefault();

            var data = {
                "action": "connection_test",
                "wp_post_id": getQueryVar('post'),
                'fmpress_ajax_nonce': localize.fmpressAjaxNonce
            };

            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl);
            var array = [];
            Object.keys(data).forEach(element =>
                array.push(
                    encodeURIComponent(element) + "=" + encodeURIComponent(data[element])
                )
            );
            var body = array.join("&");
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send(body);

            xhr.onload = () => {
                if ('' !== xhr.response) {
                    show_message(JSON.parse(xhr.response));
                }
            };
            xhr.onerror = () => {
                console.error(xhr.status);
                console.error(xhr.response);
            };
        })
    }

    // Getting URL parameters.
    function getQueryVar(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    // Show messages.
    function show_message(messages) {
        var message = '';
        for (var i = 0; i < messages.length; i++) {
            message += messages[i] + "\n";
        }
        window.alert(message);
    }
}, false);
