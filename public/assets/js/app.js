(function () {
    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function initSelect2(context) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return;
        }

        jQuery(context).find('select.select2-basic').each(function () {
            const $select = jQuery(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Select',
                allowClear: true,
            });
        });

        jQuery(context).find('select.select2-ajax').each(function () {
            const $select = jQuery(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Search',
                allowClear: true,
                minimumInputLength: window.invoiceFormConfig?.select2MinimumInputLength ?? 1,
                ajax: {
                    url: $select.data('autocomplete-url'),
                    dataType: 'json',
                    delay: 200,
                    data: (params) => ({
                        q: params.term || '',
                        page: params.page || 1,
                    }),
                    processResults: (data, params) => {
                        params.page = params.page || 1;

                        return {
                            results: data.results || [],
                            pagination: {
                                more: Boolean(data.pagination?.more),
                            },
                        };
                    },
                },
            });
        });
    }

    function initNoAutofill(context) {
        context.querySelectorAll('[data-no-autofill]').forEach((field) => {
            const unlock = () => {
                field.readOnly = false;
                field.removeEventListener('focus', unlock);
                field.removeEventListener('pointerdown', unlock);
                field.removeEventListener('keydown', unlock);
            };

            field.setAttribute('autocomplete', field.type === 'password' ? 'new-password' : 'off');
            field.setAttribute('readonly', 'readonly');
            field.addEventListener('focus', unlock);
            field.addEventListener('pointerdown', unlock);
            field.addEventListener('keydown', unlock);
        });
    }

    const itemList = document.getElementById('invoice-items-list');
    if (itemList && window.invoiceFormConfig) {
        const addButton = document.getElementById('add-item-row');

        function nextItemIndex() {
            return itemList.querySelectorAll('.clean-item-card').length;
        }

        function reindexCard(card, index) {
            card.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/items\[\d+]/g, `items[${index}]`);
            });
        }

        function resetSelect2Artifacts(card) {
            if (!window.jQuery) {
                return;
            }

            jQuery(card).find('select').each(function () {
                const $select = jQuery(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.removeClass('select2-hidden-accessible');
                $select.removeAttr('data-select2-id');
                $select.removeAttr('tabindex');
                $select.removeAttr('aria-hidden');
            });

            card.querySelectorAll('.select2').forEach((node) => node.remove());
        }

        function duplicateCard(sourceCard) {
            const clone = sourceCard.cloneNode(true);
            resetSelect2Artifacts(clone);
            reindexCard(clone, nextItemIndex());
            itemList.insertBefore(clone, sourceCard.nextSibling);
            initSelect2(clone);
            recalc();
        }

        function replaceOptions(select, items, placeholder, mapper) {
            if (!select) return;
            const current = select.value;
            const options = [`<option value="">${placeholder}</option>`]
                .concat(items.map((item) => {
                    const mapped = mapper(item);
                    const selected = String(mapped.value) === String(current) ? ' selected' : '';
                    return `<option value="${escapeHtml(mapped.value)}"${selected}${mapped.attrs || ''}>${escapeHtml(mapped.label)}</option>`;
                }));
            select.innerHTML = options.join('');
            if (window.jQuery && jQuery(select).hasClass('select2-hidden-accessible')) {
                jQuery(select).trigger('change.select2');
            }
        }

        function syncRowMetadata(row) {
            const hsSelect = row.querySelector('.hs-code-select');
            const hsOption = hsSelect?.selectedOptions?.[0];
            if (hsOption && hsOption.value) {
                const hsCode = hsOption.dataset.code || '';
                const optionText = hsOption.textContent || '';
                const derivedDescription = optionText.includes(' - ')
                    ? optionText.split(' - ').slice(1).join(' - ').trim()
                    : optionText.trim();
                const description = (hsOption.dataset.description || derivedDescription).trim();

                row.querySelector('input[name$="[description]"]').value = description;
                row.querySelector('input[name$="[hs_code]"]').value = hsCode;
            }

            const uomSelect = row.querySelector('.uom-select');
            const uomOption = uomSelect?.selectedOptions?.[0];
            const saleTypeSelect = row.querySelector('.sale-type-select');
            const saleTypeOption = saleTypeSelect?.selectedOptions?.[0];
            if (saleTypeOption && saleTypeOption.value) {
                row.querySelector('input[name$="[sale_type]"]').value = saleTypeOption.dataset.name || '';
            }

            const sroSelect = row.querySelector('.sro-schedule-select');
            const sroOption = sroSelect?.selectedOptions?.[0];
            if (sroOption && sroOption.value) {
                row.querySelector('input[name$="[sro_schedule_number]"]').value = sroOption.dataset.name || '';
            }

            const rateSelect = row.querySelector('.tax-rate-select');
            const rateOption = rateSelect?.selectedOptions?.[0];
            if (rateOption && rateOption.value) {
                row.querySelector('.rate-percent').value = rateOption.dataset.rate || 0;
            }
        }

        function recalc() {
            let sumBase = 0;
            let sumTax = 0;
            let sumExtraTax = 0;
            let sumFurtherTax = 0;
            let sumFed = 0;
            let sumGrand = 0;
            let sumDiscount = 0;

            itemList.querySelectorAll('.clean-item-card').forEach((row, index) => {
                const rowNumber = row.querySelector('.row-number');
                if (rowNumber) {
                    rowNumber.textContent = String(index + 1);
                }
                syncRowMetadata(row);
                const qty = parseFloat(row.querySelector('.quantity')?.value || 0);
                const price = parseFloat(row.querySelector('.unit-price')?.value || 0);
                const selectedRate = row.querySelector('.tax-rate-select')?.selectedOptions?.[0]?.dataset.rate;
                const rate = parseFloat(selectedRate || row.querySelector('.rate-percent')?.value || 0);
                const rateField = row.querySelector('.rate-percent');
                if (rateField) {
                    rateField.value = Number.isFinite(rate) ? String(rate) : '0';
                }
                const discountField = row.querySelector('.discount');
                let discount = parseFloat(discountField?.value || 0);
                const extraTax = parseFloat(row.querySelector('input[name$="[extra_tax]"]')?.value || 0);
                const furtherTax = parseFloat(row.querySelector('input[name$="[further_tax]"]')?.value || 0);
                const fedPayable = parseFloat(row.querySelector('input[name$="[fed_payable]"]')?.value || 0);
                const fixedNotifiedValue = parseFloat(row.querySelector('input[name$="[fixed_notified_value]"]')?.value || 0);
                const saleType = String(row.querySelector('input[name$="[sale_type]"]')?.value || '').toLowerCase();
                const isThirdSchedule = saleType.includes('3rd schedule');
                const grossValue = Math.max(qty * price, 0);
                if (discount > grossValue) {
                    discount = grossValue;
                    if (discountField) {
                        discountField.value = grossValue.toFixed(2);
                    }
                }
                const taxBasisGrossValue = isThirdSchedule && fixedNotifiedValue > 0 ? fixedNotifiedValue : grossValue;
                const tax = rate > 0 ? taxBasisGrossValue * (rate / 100) : 0;
                const valueExcludingTax = isThirdSchedule && fixedNotifiedValue > 0
                    ? grossValue
                    : Math.max(grossValue - tax, 0);
                const totalBasis = isThirdSchedule && fixedNotifiedValue > 0 ? valueExcludingTax : grossValue;
                const total = Math.max(totalBasis + tax + extraTax + furtherTax + fedPayable - discount, 0);
                const totalField = row.querySelector('.line-total');
                const baseField = row.querySelector('.value-excl-tax');
                const taxField = row.querySelector('.sales-tax-field');
                const taxTotalField = row.querySelector('.sales-tax-total');

                if (totalField) {
                    if ('value' in totalField) {
                        totalField.value = total.toFixed(2);
                    } else {
                        totalField.textContent = total.toFixed(2);
                    }
                }
                if (baseField) {
                    baseField.value = valueExcludingTax.toFixed(2);
                }
                if (taxField) {
                    taxField.value = tax.toFixed(2);
                }
                if (taxTotalField) {
                    taxTotalField.value = tax.toFixed(2);
                }
                sumBase += valueExcludingTax;
                sumTax += tax;
                sumExtraTax += extraTax;
                sumFurtherTax += furtherTax;
                sumFed += fedPayable;
                sumGrand += total;
                sumDiscount += discount;
            });

            document.getElementById('sum-base').textContent = sumBase.toFixed(2);
            document.getElementById('sum-tax').textContent = sumTax.toFixed(2);
            const extraTaxElement = document.getElementById('sum-extra-tax');
            if (extraTaxElement) {
                extraTaxElement.textContent = sumExtraTax.toFixed(2);
            }
            const furtherTaxElement = document.getElementById('sum-further-tax');
            if (furtherTaxElement) {
                furtherTaxElement.textContent = sumFurtherTax.toFixed(2);
            }
            const fedElement = document.getElementById('sum-fed');
            if (fedElement) {
                fedElement.textContent = sumFed.toFixed(2);
            }
            document.getElementById('sum-grand').textContent = sumGrand.toFixed(2);
            const discountElement = document.getElementById('sum-discount');
            if (discountElement) {
                discountElement.textContent = sumDiscount.toFixed(2);
            }
        }

        function handleItemSelectChange(target) {
            if (!target?.matches('.hs-code-select, .uom-select, .sale-type-select, .sro-schedule-select, .tax-rate-select')) {
                return;
            }

            const row = target.closest('.clean-item-card');
            if (!row) {
                return;
            }

            syncRowMetadata(row);

            recalc();
        }

        addButton?.addEventListener('click', () => {
            const index = nextItemIndex();
            const html = window.invoiceFormConfig.rowTemplate.replaceAll('__NAME__', `items[${index}]`);
            itemList.insertAdjacentHTML('beforeend', html);
            initSelect2(itemList.lastElementChild);
            recalc();
        });

        itemList.addEventListener('click', (event) => {
            if (event.target.classList.contains('duplicate-row')) {
                const card = event.target.closest('.clean-item-card');
                if (card) {
                    duplicateCard(card);
                }
                return;
            }

            if (event.target.classList.contains('remove-row')) {
                event.target.closest('.clean-item-card')?.remove();
                recalc();
            }
        });

        itemList.addEventListener('input', (event) => {
            if (event.target.classList.contains('calc-field')) {
                recalc();
            }
        });

        itemList.addEventListener('change', (event) => {
            if (event.target.classList.contains('calc-field')) {
                recalc();
                return;
            }

            handleItemSelectChange(event.target);
        });

        if (window.jQuery) {
            jQuery(itemList).on('select2:select select2:clear', 'select', function () {
                handleItemSelectChange(this);
            });
        }

        recalc();
        initSelect2(document);
    }

    const customerSelect = document.querySelector('select[name="customer_id"]');
    if (customerSelect) {
        function selectedCustomerData() {
            const option = customerSelect.selectedOptions[0];
            if (option && option.value) {
                return {
                    id: option.value,
                    name: option.dataset.name || option.textContent || '',
                    ntn: option.dataset.ntn || '',
                    strn: option.dataset.strn || '',
                    address: option.dataset.address || '',
                    buyer_type: option.dataset.buyerType || '',
                    province_id: option.dataset.provinceId || '',
                };
            }

            if (window.jQuery && jQuery(customerSelect).hasClass('select2-hidden-accessible')) {
                const selected = jQuery(customerSelect).select2('data');
                if (selected && selected[0] && selected[0].id) {
                    return selected[0];
                }
            }

            return null;
        }

        function syncBuyerFields() {
            const data = selectedCustomerData();
            const buyerFields = {
                buyer_name: data?.name || '',
                buyer_ntn_cnic: data?.ntn || '',
                buyer_strn: data?.strn || '',
                buyer_address: data?.address || '',
            };

            Object.entries(buyerFields).forEach(([name, value]) => {
                const field = document.querySelector(`[name="${name}"]`);
                if (field) {
                    field.value = value;
                }
            });

            const buyerTypeField = document.querySelector('[name="buyer_type_display"]');
            if (buyerTypeField) {
                buyerTypeField.value = data?.buyer_type || '';
            }

            if (data?.province_id) {
                ['sale_origin_province_id', 'destination_province_id'].forEach((name) => {
                    const field = document.querySelector(`[name="${name}"]`);
                    if (!field) {
                        return;
                    }

                    field.value = String(data.province_id);

                    if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
                        jQuery(field).trigger('change');
                    } else {
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        }

        customerSelect.addEventListener('change', syncBuyerFields);
        if (window.jQuery) {
            jQuery(customerSelect).on('select2:select select2:clear', syncBuyerFields);
        }
        syncBuyerFields();
    }

    initNoAutofill(document);
    initSelect2(document);

    document.querySelectorAll('.countdown').forEach((element) => {
        const until = new Date(element.dataset.until);
        const tick = () => {
            const diff = until - new Date();
            if (diff <= 0) {
                element.textContent = 'Expired';
                return;
            }
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            element.textContent = `${hours}h ${minutes}m remaining`;
        };
        tick();
        setInterval(tick, 60000);
    });
})();
