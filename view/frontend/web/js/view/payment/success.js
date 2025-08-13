document.addEventListener('DOMContentLoaded', function () {
    const main = document.getElementById('tpay_success_status');

    if ('undefined' === typeof main) {
        return;
    }

    document.tpay = {};
    document.tpay.transition = function (role) {
        document.getElementById('tpay_success_processing').checked = false;
        for (child of main.children) {
            var childRole = child.dataset.role;
            if (role === childRole) {
                child.style.display = 'block';
            } else {
                child.style.display = 'none';
            }
        }
    }

    document.tpay.await = function () {
        setTimeout(async function () {
            try {
                const response = await fetch(main.dataset.statusUrl);
                const json = await response.json();
                if (json.errorMessage) {
                    document.getElementById('tpay_error_message').innerText = json.errorMessage;
                }
                if (json.status) {
                    document.tpay.transition(json.status);
                    if ('wait' === json.status) {
                        document.tpay.await();
                    }
                }
            } catch (e) {
                document.tpay.await();
            }
        }, 1000);
    }

    document.tpay.retry = async function () {
        try {
            var response;
            document.getElementById('tpay_error_message').innerText = '';
            document.getElementById('tpay_success_processing').checked = true;
            if (document.getElementById('blik-radio').checked) {
                var data = new FormData();
                data.append('blikCode', document.getElementById('blik-code').value.replace(/[^0-9]/g, ''));
                data.append('form_key', main.dataset.formKey);
                response = await fetch(main.dataset.retryUrl, {
                    method: 'POST',
                    body: data,
                });
            } else {
                var data = new FormData();
                data.append('form_key', main.dataset.formKey);
                response = await fetch(main.dataset.retryUrl, {
                    method: 'POST',
                    body: data
                })
            }
            document.getElementById('blik-code').value = '';
            const json = await response.json();
            if (json.errorMessage) {
                document.getElementById('tpay_error_message').innerText = json.errorMessage;
            }
            if (true === json.disableBlik) {
               document.getElementById('tpay_success_blik_block').style.display = 'none';
               document.getElementById('bank-transfer-radio').checked = true;
            }
            if (json.redirect) {
                window.location.href = json.redirect;
            } else {
                document.tpay.transition(json.status);
                if ('wait' === json.status) {
                    document.tpay.await();
                }

            }
        } catch (e) {
            console.log(e);
        }
    }

    document.tpay.evaluate = function () {
        if (document.getElementById('bank-transfer-radio').checked) {
            document.getElementById('payment-button').disabled = false;
        }
        if ( document.getElementById('blik-radio').checked ) {
            if(document.getElementById('blik-code').value.replace(/[^0-9]/g, '').length == 6){
                document.getElementById('payment-button').disabled = false;
                return;
            }
            document.getElementById('payment-button').disabled = true;
        }
    }

    if ('wait' === main.dataset.status) {
        document.tpay.await();
    }

    document.getElementById('bank-transfer-radio').onchange = function (event) {
        document.tpay.evaluate();
    };

    document.getElementById('blik-radio').onchange = function (event) {
        document.tpay.evaluate();
    }

    document.getElementById('payment-button').onclick = function (event) {
        event.preventDefault();
        document.tpay.retry();
    }

    document.getElementById('blik-code').onkeyup = function (event) {
        const valueAsArray = event.target.value.replace(/[^0-9]/g, '').substring(0, 6).split('');
        if (valueAsArray.length > 3) {
            valueAsArray.splice(3, 0, ' ');
        }
        event.target.value = valueAsArray.join('');
        document.tpay.evaluate();
    }
});
