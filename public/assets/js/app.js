var doCartAction = false;

let counter = {
    'to': 0,
    'cc': 0,
    'cci': 0,
    'attachments': 0
}

function isFileEmpty(file, callback) {
    const reader = new FileReader();

    reader.onload = function(event) {
        const content = event.target.result;
        const isEmpty = content.length === 0;
        callback(isEmpty);
    };

    reader.readAsText(file);
}

function sanitizeHTML(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

$(document).click(function () {
    $('input[type=file]').each(function () {
        console.log($(this)[0].files)
    })
})

$(document).ready(function () {
    $('.form-label').each(function () {
        fora = $(this).attr('for')
        var regex = /mail_form_(to|cc|cci|attachments)_([0-9]+)/;

        if (!regex.test(fora)) {
            return
        }

        var data = regex.exec(fora)
        var type = data[1]
        var id = parseInt(data[2])

        counter[type] = Math.max(id + 1, counter[type])
        $(this).css('display', 'none')
    })
})

$('.form-control').click(function () {
    var id = $(this).attr('id')
    var regex = /mail_form_(to|cc|cci|attachments)_([0-9]+)/;

    if (regex.test(id)) {
        $(this).preventDefault()
    }
})

$('#attachment-input').change(function () {
    let inputContainer = $('#mail_form_attachments');
    var label = $('#attachment-input-label')
    var errorField = $('#form-attachments-error')
    var thisFiles = $(this)[0].files

    isFileEmpty(thisFiles[0], function (isEmpty) {
        if (isEmpty) {
            errorField.html('Le fichier ne doit pas être vide')
            errorField.removeClass('d-none')
            errorField.addClass('d-block')
            label.addClass('is-invalid')
            return;
        } else {
            if (thisFiles[0].size > 5 * 1024 * 1024 * 8) {
                errorField.html('La taille des fichiers est au maximum de 5Mo')
                errorField.removeClass('d-none')
                errorField.addClass('d-block')
                label.addClass('is-invalid')
                return;
            }

            label.removeClass('is-invalid')
            errorField.removeClass('d-block')
            errorField.addClass('d-none')

            var id = counter['attachments']

            inputContainer.append('<div id="attachments-container-' + id + '" class="d-flex flex-row">' +
                '                      <input readonly type="file" id="mail_form_attachments_' + id + '" name="mail_form[attachments][' + id + ']" class="form-control my-2" style="display: none">' +
                '                      <input readonly type="text" class="my-2 form-control" value="' + sanitizeHTML(thisFiles[0].name) + '"/>' +
                '                      <div class="my-auto" style="width: 36px!important; height: 36px!important;">' +
                '                          <span id="remove-attachments-item-' + id + '" class="mail-button mail-remove-button fa-solid fa-trash"></span>' +
                '                      </div>' +
                '                 </div>')

            $('#remove-attachments-item-' + id).click(function () {
                $('#attachments-container-' + id).remove()
            })

            $('#mail_form_attachments_' + counter['attachments'])[0].files = thisFiles;
            counter['attachments'] += 1
        }
    })
});

$('.mail-add-button').click(function () {
    let add = $(this).data('add-mail');
    let input = $('#add-' + add + '-input');
    let content = input.val();

    let inputContainer = $('#mail_form_' + add);
    var regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    var errorField = $('#form-' + add + '-error')

    if (!regexEmail.test(content)) {
        errorField.html('Adresse invalide')
        errorField.removeClass('d-none')
        errorField.addClass('d-block')
        input.addClass('is-invalid')
        return;
    }

    var id = counter[add]

    input.removeClass('is-invalid')
    errorField.removeClass('d-block')
    errorField.addClass('d-none')
    inputContainer.append('<div id="' + add + '-container-' + id + '" class="d-flex flex-row">' +
        '                      <input readonly value="' + sanitizeHTML(content) + '" type="email" id="mail_form_' + add + '_' + id+ '" name="mail_form[' + add + '][' + id + ']" class="form-control my-2">' +
        '                      <div class="my-auto" style="width: 36px!important; height: 36px!important;">' +
        '                          <span id="remove-' + add + '-item-' + id + '" class="mail-button mail-remove-button fa-solid fa-trash"></span>' +
        '                      </div>' +
        '                 </div>')

    $('#remove-' + add + '-item-' + id).click(function () {
        $('#' + add + '-container-' + id).remove()
    })

    input.val("")
    counter[add] += 1
});

function cleanUrl(url) {
    while (url.slice(-1) === '&' || url.slice(-1) === '?') {
        url = url.slice(0, -1);
    }

    return url;
}

$(document).ready(function () {
    $('.error').each(function () {
        toastr.error($(this).html());
        $(this).remove();
    });

    $('.success').each(function () {
        toastr.success($(this).html());
        $(this).remove();
    });

    $('.info').each(function () {
        toastr.info($(this).html());
        $(this).remove();
    });

    $('.warning').each(function () {
        toastr.warning($(this).html());
        $(this).remove();
    })
});

$(document).ready(function () {
    var params = new URLSearchParams(window.location.search);

    if (params.has('notifications')) {
        params.delete('notifications');

        window.history.replaceState({}, document.title, cleanUrl(window.location.href.split('?')[0] + '?' + params.toString()));
    }

    var action = params.get('action');

    if (action) {
        var path = window.location.pathname.toLowerCase();

        if (action.toLowerCase() === 'add_to_cart') {
            var productId = params.get('product_id');
            var productSlug = params.get('product_slug');
            var quantity = params.get('quantity');
            var onSuccess = params.get('on_success');

            if (productId && productSlug && quantity && path === ROUTES['app_product'].replace('slug', productSlug)) {
                addToCart(productId, productSlug, quantity, onSuccess, false);

                params.delete('action');
                params.delete('product_id');
                params.delete('product_slug');
                params.delete('quantity');
                params.delete('on_success');

                window.history.replaceState({}, document.title, cleanUrl(window.location.href.split('?')[0] + '?' + params.toString()));
            }
        }
    }
});

$('.btn-add-to-cart').click(function() {
    var productId = $(this).data('product-id');
    var productSlug = $(this).data('product-slug');
    var abortIfOffline = $(this).data('abort-if-offline');
    var quantity = $(this).data('quantity') ? $(this).data('quantity') : 1;
    var onSuccess = $(this).data('on-success');

    addToCart(productId, productSlug, quantity, onSuccess, abortIfOffline);
});

function addToCart(productId, productSlug, quantity, onSuccess, abortIfOffline) {
    if (doCartAction) {
        return;
    }

    if (LOGGED) {
        doCartAction = true;

        $.ajax({
            url: ROUTES['app_add_to_cart'],
            type: 'POST',
            data: {
                id: productId,
                quantity: quantity
            },
            success: function (data) {
                doCartAction = false;

                if (data['message']) {
                    if (data['state'] === 'success') {
                        toastr.success(data['message']);

                        if (onSuccess) {
                            window[onSuccess](data);
                        }
                    } else if (data['state'] === 'error') {
                        toastr.error(data['message']);
                    }

                    $(this).html(data['message']);
                }
            },
        });
    } else {
        if (!abortIfOffline) {
            window.location.href = ROUTES['app_login'] + '?' + new URLSearchParams({
                redirect_to: ROUTES['app_product'].replace('slug', productSlug) + '?' + new URLSearchParams({
                    action: 'add_to_cart',
                    product_id: productId,
                    product_slug: productSlug,
                    quantity: quantity,
                    on_success: onSuccess
                }).toString()
            }).toString();
        }
    }
}

$('.btn-remove-from-cart').click(function() {
    var productId = $(this).data('product-id');
    var productSlug = $(this).data('product-slug');
    var abortIfOffline = $(this).data('abort-if-offline');
    var quantity = $(this).data('quantity') ? $(this).data('quantity') : 1;
    var onSuccess = $(this).data('on-success');

    removeFromCart(productId, productSlug, quantity, onSuccess, abortIfOffline);
});

function removeFromCart(productId, productSlug, quantity, onSuccess, abortIfOffline) {
    if (doCartAction) {
        return;
    }

    if (LOGGED) {
        doCartAction = true;

        $.ajax({
            url: ROUTES['app_remove_from_cart'],
            type: 'POST',
            data: {
                id: productId,
                quantity: quantity
            },
            success: function (data) {
                doCartAction = false;

                if (data['message']) {
                    if (data['state'] === 'success') {
                        toastr.success(data['message']);

                        if (onSuccess) {
                            window[onSuccess](data);
                        }
                    } else if (data['state'] === 'error') {
                        toastr.error(data['message']);
                    }

                    $(this).html(data['message']);
                }
            },
        });
    } else {
        if (!abortIfOffline) {
            window.location.href = ROUTES['app_login'] + '?' + new URLSearchParams({
                redirect_to: ROUTES['app_product'].replace('slug', productSlug) + '?' + new URLSearchParams({
                    action: 'remove_from_cart',
                    product_id: productId,
                    product_slug: productSlug,
                    quantity: quantity,
                    on_success: onSuccess
                }).toString()
            }).toString();
        }
    }
}

$('.category-switch').change(function () {
   var checked = $(this).is(':checked');
   var category = $(this).data('category');

    $('.product').each(function () {
        if ($(this).data('category') === category) {
            if (checked) {
                $(this).data('category-filter', 'shown');
            } else {
                $(this).data('category-filter', 'hidden');
            }

            updateElementVisibility($(this));
        }
    });
});

$('.product-filter').change(function () {
    var value = $(this).val();

    $('.product').each(function () {
        var productName = $(this).data('name');

        if (productName.toLowerCase().indexOf(value.toLowerCase()) !== -1) {
            $(this).data('search-filter', 'shown');
        } else {
            $(this).data('search-filter', 'hidden');
        }

        updateElementVisibility($(this));
    });
});

function updateElementVisibility(element) {
    var categoryFilterState = element.data('category-filter');
    var searchFilterState = element.data('search-filter');

    if (categoryFilterState === 'shown' && searchFilterState === 'shown') {
        element.removeClass('d-none');
    } else {
        element.addClass('d-none');
    }
}

$('.app-link').click(function () {
    var route = $(this).data('route');
    var keepQuery = $(this).data('keep-query');

    var url = ROUTES[route];

    if (keepQuery) {
        url = cleanUrl(url + window.location.search);
    }

    window.location.href = url;
});

function formattedPrice(price) {
    return price.toFixed(2).replace(/\,/g, '.').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function refreshCart(data) {
    if (window.location.pathname.toLowerCase() === ROUTES['app_cart']) {
        var cart = data['cart'];

        if (cart['cart_complete'].length === 0) {
            $('.cart-elements-table').addClass('d-none');
            $('.cart-empty').removeClass('d-none');
        } else {
            $('.cart-elements-table').removeClass('d-none');
            $('.cart-empty').addClass('d-none');
        }

        $('.cart-total-price').each(function () {
           $(this).html(formattedPrice(cart['cart_total_price'] / 100));
        });

        $('.cart-total-quantity').each(function () {
            $(this).html(cart['cart_total_quantity']);
        });

        $('.cart-element').each(function () {
           var elementId = $(this).data('element-id');
           var match = false;

           cart['cart_complete'].forEach(function (element) {
               if (element['product']['id'] === elementId) {
                   match = true;
                   return false;
               }
           });

           if (!match) {
               $(this).remove();
           }
        });

        cart['cart_complete'].forEach(function (element) {
            if ($('.cart-element-' + element['product']['id']).length > 0) {
                $('.cart-element-' + element['product']['id'] + '-quantity').html(element['quantity']);
                $('.cart-element-' + element['product']['id'] + '-total-price').html(formattedPrice(element['product']['price'] / 100 * element['quantity']));
            } else {
                $('.cart-elements').append(
                    `   <tr class="cart-element cart-element-${element['product']['id']}" data-element-id="${element['product']['id']}">
                            <td class="col-6 d-md-flex w-100 align-middle">
                                <div class="col-md-4 d-flex flex-column">
                                    <div class="my-auto mx-md-auto">
                                        <img height="100px" src="/uploads/products/${element['product']['illustration']}" alt=""/>
                                    </div>
                                </div>
                                <div class="col-md-8 d-flex flex-column vertical-separator-border ps-md-2">
                                    <div class="my-auto">
                                        <span class="product-name">${element['product']['name']}</span><br/>
                                        <span class="text-muted product-subtitle">${element['product']['subtitle']}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="col-2 align-middle">
                                <span>x <span class="cart-element-${element['product']['id']}-quantity">${element['quantity']}</span></span><br/>
                                <i data-on-success="refreshCart" data-abort-if-offline="true" data-product-id="${element['product']['id']}" data-product-slug="${element['product']['slug']}" class="fa-solid fa-plus mt-1 me-2 cursor-pointer btn-add-to-cart"></i>
                                <i data-on-success="refreshCart" data-abort-if-offline="true" data-product-id="${element['product']['id']}" data-product-slug="${element['product']['slug']}" class="cursor-pointer fa-solid fa-minus mt-1 btn-remove-from-cart"></i>
                                <i data-on-success="refreshCart" data-quantity="-1" data-abort-if-offline="true" data-product-id="${element['product']['id']}" data-product-slug="${element['product']['slug']}" class="cursor-pointer mt-1 ms-2 fa-solid fa-trash btn-remove-from-cart"></i>
                            </td>
                            <td class="col-2 align-middle">
                                <span><span class="cart-element-${element['product']['id']}-price">${formattedPrice(element['product']['price'] / 100)}</span>€</span>
                            </td>
                            <td class="col-2 align-middle">
                                <span><span class="cart-element-${element['product']['id']}-total-price">${formattedPrice(element['product']['price'] / 100 * element['quantity'])}</span>€</span>
                            </td>
                        </tr>`
                );
            }
        });
    }
}

$('.mail-navbar-switch').click(function () {
    var active = $(this).data('active')

    if (active) {
        $(this).removeClass('mail-navbar-switch-active')
        $('.mail-navbar').each(function () {
            $(this).removeClass('mail-navbar-active')
        })
    } else {
        $(this).addClass('mail-navbar-switch-active')
        $('.mail-navbar').each(function () {
            $(this).addClass('mail-navbar-active')
        })
    }

    $(this).data('active', !active)
})