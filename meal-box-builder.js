jQuery(document).ready(function($) {
    let selectedMeals = {};
    let maxMeals = 0;
    let selectedCategories = [];

    const STORAGE_KEY = 'mealBoxSelections';

    // ✅ GA4 tracking functions
    function trackBoxSelected(boxSize) {
        if (typeof gtag === 'function') {
            gtag('event', 'select_item', {
                item_list_name: 'Box Selection',
                items: [{
                    item_name: `${boxSize}-Meal Box`,
                    item_category: 'Box Size'
                }]
            });
        }
    }

    function trackMealAdded(mealName, qty = 1) {
        if (typeof gtag === 'function') {
            gtag('event', 'select_item', {
                item_list_name: 'Meal Selection',
                items: [{
                    item_name: mealName,
                    item_category: 'Meal',
                    quantity: qty
                }]
            });
        }
    }

    function trackBeginCheckout(totalValue = 0) {
        if (typeof gtag === 'function') {
            gtag('event', 'begin_checkout', {
                currency: 'USD',
                value: totalValue
            });
        }
    }

    function saveToStorage() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            meals: selectedMeals,
            maxMeals: maxMeals
        }));
    }

    function loadFromStorage() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return false;

        try {
            const parsed = JSON.parse(saved);
            if (typeof parsed.meals === 'object') selectedMeals = parsed.meals;
            if (typeof parsed.maxMeals === 'number') maxMeals = parsed.maxMeals;
            return true;
        } catch (e) {
            console.warn('Failed to load meal selections from storage.', e);
            return false;
        }
    }

    function clearStorageAndSelections() {
        selectedMeals = {};
        localStorage.removeItem(STORAGE_KEY);
    }

    function showMaxMeals() {
        $('#max-meals').text(maxMeals);
    }

    function bindNutritionToggle() {
        $('.nutrition-toggle').off('click').on('click', function() {
            $(this).closest('.meal-card').find('.nutrition-panel').stop(true, true).slideToggle(200);
        });
    }

    function initializeMealBuilder() {
        hydrateInputsFromSelectedMeals();
        enrichMealsIfNeeded();
        updateSelectedCount();
        bindNutritionToggle();

        if (window.innerWidth >= 1025) {
            $('#meal-floating-counter').removeClass('collapsed');
            $('#meal-counter-toggle').hide();
        } else {
            $('#meal-floating-counter').addClass('collapsed');
            $('#meal-counter-toggle').text('Show Cart').show();
        }
    }

    function enrichMealsIfNeeded() {
        const idsToFetch = [];

        for (let id in selectedMeals) {
            const meal = selectedMeals[id];
            if (!meal.name || typeof meal.price === 'undefined') {
                idsToFetch.push(id);
            }
        }

        if (idsToFetch.length > 0) {
            $.post(mealBoxAjax.ajax_url, {
                action: 'get_meal_details',
                nonce: mealBoxAjax.nonce,
                meal_ids: idsToFetch
            }, function(response) {
                if (response.success && response.data) {
                    for (let id in response.data) {
                        if (selectedMeals[id]) {
                            selectedMeals[id].name = response.data[id].name;
                            selectedMeals[id].price = parseFloat(response.data[id].price);
                        }
                    }
                    updateSelectedCount();
                    saveToStorage();
                }
            });
        }
    }

    $('.box-option').on('click', function() {
        const boxSize = $(this).data('box-size');
        $('.box-option').removeClass('selected');
        $(this).addClass('selected');

        // ✅ Track box selection
        trackBoxSelected(boxSize);

        clearStorageAndSelections();

        $.post(mealBoxAjax.ajax_url, {
            action: 'select_box_size',
            box_size: boxSize,
            nonce: mealBoxAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#meal-box-message').text(`You've selected a ${boxSize}-meal box!`);
                loadMealSelectionStep(boxSize);
            } else {
                $('#meal-box-message').text("Something went wrong. Try again.");
            }
        });
    });

    function loadMealSelectionStep(boxSize) {
        maxMeals = boxSize;
        saveToStorage();

        $.post(mealBoxAjax.ajax_url, {
            action: 'load_meal_selection_template',
            nonce: mealBoxAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#meal-box-selection').replaceWith(response.data.html);
                initializeMealBuilder();
            }
        });
    }

    function hydrateInputsFromSelectedMeals() {
        for (let mealId in selectedMeals) {
            const input = $(`.meal-qty[data-meal-id="${mealId}"]`);
            if (input.length) {
                input.val(selectedMeals[mealId].qty).trigger('change');
                input.attr('data-meal-name', selectedMeals[mealId].name || 'Meal');
                input.attr('data-meal-price', selectedMeals[mealId].price || 0);
            }
        }
    }

    $(document).on('click', '#meal-counter-toggle', function() {
        $('#meal-floating-counter').toggleClass('collapsed');
        $(this).text($('#meal-floating-counter').hasClass('collapsed') ? 'Show Cart' : 'Hide Cart');
    });

    $(document).on('click', '.meal-category-filter', function() {
        const categoryId = $(this).data('cat-id');
        $(this).toggleClass('selected');

        if ($(this).hasClass('selected')) {
            selectedCategories.push(categoryId);
        } else {
            selectedCategories = selectedCategories.filter(id => id !== categoryId);
        }

        loadMealsByCategories();
    });

    $(document).on('click', '#clear-category-filters', function() {
        selectedCategories = [];
        selectedMeals = {};
        saveToStorage();
        $('.meal-category-filter').removeClass('selected');
        $('#meal-products').empty();
        $('#meal-default-message').show();
        updateSelectedCount();

        $.post(mealBoxAjax.ajax_url, {
            action: 'clear_selected_meals',
            nonce: mealBoxAjax.nonce
        });
    });

    function loadMealsByCategories() {
        if (!selectedCategories.length) {
            $('#meal-products').empty();
            $('#meal-default-message').show();
            return;
        }

        $('#meal-default-message').hide();

        $.post(mealBoxAjax.ajax_url, {
            action: 'load_meals_by_category',
            category_ids: selectedCategories,
            nonce: mealBoxAjax.nonce
        }, function(response) {
            if (response.success) {
                $('#meal-products').html(response.data.html);
                hydrateInputsFromSelectedMeals();
                updateSelectedCount();
                bindNutritionToggle();
            }
        });
    }

    $(document).on('change', '.meal-qty', function() {
        const mealId = $(this).data('meal-id');
        const mealName = $(this).data('meal-name') || 'Meal';
        const mealPrice = parseFloat($(this).data('meal-price')) || 0;
        let qty = parseInt($(this).val()) || 0;

        let currentTotal = Object.values(selectedMeals).reduce((sum, meal) => sum + meal.qty, 0) - (selectedMeals[mealId]?.qty || 0);

        if (currentTotal + qty > maxMeals) {
            qty = maxMeals - currentTotal;
            $(this).val(qty);
        }

        if (qty > 0) {
            selectedMeals[mealId] = { qty, name: mealName, price: mealPrice };

            // ✅ Track meal add/update
            trackMealAdded(mealName, qty);
        } else {
            delete selectedMeals[mealId];
        }

        updateSelectedCount();
        saveToStorage();
    });

    $(document).on('click', '.qty-up, .qty-down', function() {
        const container = $(this).closest('.quantity-selector');
        const input = container.find('.meal-qty');
        let qty = parseInt(input.val()) || 0;

        qty = $(this).hasClass('qty-up') ? qty + 1 : Math.max(0, qty - 1);
        input.val(qty).trigger('change');
    });

    function updateSelectedCount() {
        const count = Object.values(selectedMeals).reduce((sum, meal) => sum + meal.qty, 0);
        $('#selected-count').text(count);
        $('#max-meals').text(maxMeals);

        const $continueBtn = $('#meal-box-continue');
        if ($continueBtn.length) {
            $continueBtn.prop('disabled', count !== maxMeals);
            $continueBtn.toggleClass('ready', count === maxMeals);
        }

        const $list = $('#selected-meal-list').empty();
        let total = 0;

        for (let mealId in selectedMeals) {
            const meal = selectedMeals[mealId];
            const subtotal = meal.price * meal.qty;
            total += subtotal;

            $list.append(`
                <div class="selected-meal-item" data-meal-id="${mealId}">
                    <span>${meal.name} × ${meal.qty} — $${subtotal.toFixed(2)}</span>
                    <button class="remove-meal" data-meal-id="${mealId}">✕</button>
                </div>
            `);
        }

        $('#meal-total').text(`Total: $${total.toFixed(2)}`);
    }

    $(document).on('click', '.remove-meal', function() {
        const mealId = $(this).data('meal-id');
        delete selectedMeals[mealId];
        $(`.meal-qty[data-meal-id="${mealId}"]`).val(0);
        updateSelectedCount();
        saveToStorage();

        $.post(mealBoxAjax.ajax_url, {
            action: 'remove_selected_meal',
            meal_id: mealId,
            nonce: mealBoxAjax.nonce
        });
    });

    if (loadFromStorage()) showMaxMeals();

    $(window).on('load', function() {
        hydrateInputsFromSelectedMeals();
        enrichMealsIfNeeded();
        updateSelectedCount();
        bindNutritionToggle();

        const $lockedBtn = $('.box-option.locked.selected');
        if ($lockedBtn.length && !$lockedBtn.hasClass('selected-loaded')) {
            $lockedBtn.addClass('selected-loaded');

            const boxSize = $lockedBtn.data('box-size');
            clearStorageAndSelections();

            $.post(mealBoxAjax.ajax_url, {
                action: 'select_box_size',
                box_size: boxSize,
                nonce: mealBoxAjax.nonce
            }, function(response) {
                if (response.success) {
                    $('#meal-box-message').text(`You've selected a ${boxSize}-meal box!`);
                    loadMealSelectionStep(boxSize);
                }
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        const isEditing = urlParams.has('from_order');
        const stored = localStorage.getItem(STORAGE_KEY);

        if (isEditing && stored) {
            try {
                const parsed = JSON.parse(stored);
                const autoBox = parseInt(parsed.maxMeals);

                if ([10, 20].includes(autoBox)) {
                    maxMeals = autoBox;
                    selectedMeals = parsed.meals || {};
                    saveToStorage();

                    $.post(mealBoxAjax.ajax_url, {
                        action: 'select_box_size',
                        box_size: autoBox,
                        nonce: mealBoxAjax.nonce
                    }, function(response) {
                        if (response.success) {
                            $('#meal-box-message').text(`You've selected a ${autoBox}-meal box!`);
                            loadMealSelectionStep(autoBox);
                        }
                    });
                }
            } catch (err) {
                console.warn("Failed to parse stored mealBoxSelections:", err);
            }
        }
    });

    $(document).on('click', '#reset-box-builder', function() {
        if (!confirm('This will clear your cart and selections. Start over?')) return;

        clearStorageAndSelections();

        $.post(mealBoxAjax.ajax_url, {
            action: 'reset_meal_box_flow',
            nonce: mealBoxAjax.nonce
        }).done(function() {
            window.location.href = '/order-now/';
        }).fail(function() {
            alert('Failed to reset. Try refreshing.');
        });
    });

    $(document).on('click', '#meal-box-continue', function () {
        // ✅ Track begin_checkout with total
        let total = Object.values(selectedMeals).reduce((sum, meal) => sum + (meal.qty * meal.price), 0);
        trackBeginCheckout(total);

        const simplifiedMeals = Object.fromEntries(
            Object.entries(selectedMeals).map(([id, m]) => [id, m.qty])
        );

        $.post(mealBoxAjax.ajax_url, {
            action: 'save_selected_meals',
            nonce: mealBoxAjax.nonce,
            meals: simplifiedMeals
        }, function(response) {
            if (response.success) {
                $.post(mealBoxAjax.ajax_url, {
                    action: 'set_subscription_choice',
                    nonce: mealBoxAjax.nonce,
                    purchase_type: $('input[name="purchase_type"]:checked').val() || 'onetime',
                    interval: $('input[name="subscription_interval"]:checked').val() || '2',
                    first_time_discount: $('#first-time-discount').is(':checked'),
                    selected_categories: selectedCategories
                }, function(response) {
                    if (response.success) {
                        $.post(mealBoxAjax.ajax_url, {
                            action: 'load_review_template',
                            nonce: mealBoxAjax.nonce
                        }, function(response) {
                            if (response.success) {
                                $('#meal-box-step-wrapper').html(
                                    '<div id="meal-box-selection">' + response.data.html + '</div>'
                                );
                            }
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '#go-back-step2', function() {
        $.post(mealBoxAjax.ajax_url, {
            action: 'load_meal_selection_template',
            nonce: mealBoxAjax.nonce
        }, function(response) {
            if (response.success) {
                $('.checkout-wrapper').replaceWith(response.data.html);
                initializeMealBuilder();
            }
        });
    });
});
