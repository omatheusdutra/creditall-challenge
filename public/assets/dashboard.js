(function () {
    const config = window.CreditallDashboard ?? { apiBase: '/api/v1' };
    const state = {
        products: { page: 1, perPage: 6, search: '', meta: {}, items: [], loading: false },
        customers: { page: 1, perPage: 6, search: '', meta: {}, items: [], loading: false },
        sales: { page: 1, perPage: 6, status: '', sold_from: '', sold_to: '', meta: {}, items: [], loading: false },
    };

    const references = {
        products: [],
        customers: [],
    };

    const refreshTimers = {};

    const metrics = {
        products: document.querySelector('[data-metric="products"]'),
        customers: document.querySelector('[data-metric="customers"]'),
        sales: document.querySelector('[data-metric="sales"]'),
    };

    const summaries = {
        gross: document.querySelector('[data-summary="gross"]'),
        discount: document.querySelector('[data-summary="discount"]'),
        net: document.querySelector('[data-summary="net"]'),
        completed: document.querySelector('[data-summary="completed"]'),
        range: document.getElementById('sales-summary-range'),
    };
    const panelSummaries = {
        products: {
            primary: document.querySelector('[data-panel-value="products-primary"]'),
            primaryNote: document.querySelector('[data-panel-note="products-primary"]'),
            secondary: document.querySelector('[data-panel-value="products-secondary"]'),
            secondaryNote: document.querySelector('[data-panel-note="products-secondary"]'),
            tertiary: document.querySelector('[data-panel-value="products-tertiary"]'),
            tertiaryNote: document.querySelector('[data-panel-note="products-tertiary"]'),
        },
        customers: {
            primary: document.querySelector('[data-panel-value="customers-primary"]'),
            primaryNote: document.querySelector('[data-panel-note="customers-primary"]'),
            secondary: document.querySelector('[data-panel-value="customers-secondary"]'),
            secondaryNote: document.querySelector('[data-panel-note="customers-secondary"]'),
            tertiary: document.querySelector('[data-panel-value="customers-tertiary"]'),
            tertiaryNote: document.querySelector('[data-panel-note="customers-tertiary"]'),
        },
        sales: {
            primary: document.querySelector('[data-panel-value="sales-primary"]'),
            primaryNote: document.querySelector('[data-panel-note="sales-primary"]'),
            secondary: document.querySelector('[data-panel-value="sales-secondary"]'),
            secondaryNote: document.querySelector('[data-panel-note="sales-secondary"]'),
            tertiary: document.querySelector('[data-panel-value="sales-tertiary"]'),
            tertiaryNote: document.querySelector('[data-panel-note="sales-tertiary"]'),
        },
    };

    const selectors = {
        product: document.getElementById('sale-product-select'),
        customer: document.getElementById('sale-customer-select'),
    };
    const customerCpfInput = document.querySelector('[data-cpf-mask]');
    const confirmModal = {
        shell: document.getElementById('confirm-modal'),
        title: document.getElementById('confirm-modal-title'),
        description: document.getElementById('confirm-modal-description'),
        confirm: document.getElementById('confirm-modal-confirm'),
        cancel: document.getElementById('confirm-modal-cancel'),
        closers: document.querySelectorAll('[data-modal-close]'),
    };
    let confirmModalResolver = null;

    document.addEventListener('DOMContentLoaded', () => {
        bindPanelNavigation();
        bindPanelJumpButtons();
        bindSearchInputs();
        bindStatusFilter();
        bindDateFilters();
        bindPageSizeControls();
        bindRefreshButtons();
        bindResetButtons();
        bindCustomerCpfMask();
        bindConfirmModal();
        bindForms();
        boot();
    });

    async function boot() {
        try {
            await refreshProducts();
            await refreshCustomers();
            await refreshSales();
            await refreshSaleReferences();
        } catch (error) {
            notify('Nao foi possivel iniciar o painel.', error.message, 'error');
        }
    }

    function bindPanelNavigation() {
        document.querySelectorAll('[data-panel-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.panelTarget;

                document.querySelectorAll('[data-panel-target]').forEach((navButton) => {
                    navButton.classList.toggle('is-active', navButton === button);
                });

                document.querySelectorAll('[data-panel]').forEach((panel) => {
                    panel.classList.toggle('is-active', panel.dataset.panel === target);
                });
            });
        });
    }

    function bindPanelJumpButtons() {
        document.querySelectorAll('[data-panel-jump]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.panelJump;

                activatePanel(target);
                document.querySelector('.workspace')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            });
        });
    }

    function bindSearchInputs() {
        document.querySelectorAll('[data-search-input]').forEach((input) => {
            let debounceId = null;

            input.addEventListener('input', () => {
                const entity = input.dataset.searchInput;
                window.clearTimeout(debounceId);

                debounceId = window.setTimeout(async () => {
                    state[entity].search = input.value.trim();
                    state[entity].page = 1;
                    await refreshEntity(entity);
                }, 250);
            });
        });
    }

    function bindStatusFilter() {
        const filter = document.querySelector('[data-status-filter="sales"]');

        if (!filter) {
            return;
        }

        filter.addEventListener('change', async () => {
            state.sales.status = filter.value;
            state.sales.page = 1;
            await refreshSales();
        });
    }

    function bindDateFilters() {
        document.querySelectorAll('[data-date-filter]').forEach((input) => {
            input.addEventListener('change', async () => {
                const field = input.dataset.dateFilter;
                state.sales[field] = input.value;
                state.sales.page = 1;
                await refreshSales();
            });
        });
    }

    function bindPageSizeControls() {
        document.querySelectorAll('[data-page-size]').forEach((select) => {
            select.addEventListener('change', async () => {
                const entity = select.dataset.pageSize;
                state[entity].perPage = Number(select.value);
                state[entity].page = 1;
                await refreshEntity(entity);
            });
        });
    }

    function bindRefreshButtons() {
        document.querySelectorAll('[data-refresh-entity]').forEach((button) => {
            button.addEventListener('click', async () => {
                const releasePending = setElementPending(button, 'Atualizando...');

                try {
                    await refreshEntity(button.dataset.refreshEntity);
                } finally {
                    releasePending();
                }
            });
        });
    }

    function bindResetButtons() {
        document.querySelectorAll('[data-reset-form]').forEach((button) => {
            button.addEventListener('click', () => {
                resetForm(button.dataset.resetForm);
            });
        });
    }

    function bindCustomerCpfMask() {
        if (!customerCpfInput) {
            return;
        }

        customerCpfInput.addEventListener('input', () => {
            customerCpfInput.value = formatCpfInput(customerCpfInput.value);
        });
    }

    function bindConfirmModal() {
        if (!confirmModal.shell) {
            return;
        }

        confirmModal.cancel?.addEventListener('click', () => closeConfirmModal(false));
        confirmModal.confirm?.addEventListener('click', () => closeConfirmModal(true));

        confirmModal.closers.forEach((element) => {
            element.addEventListener('click', () => closeConfirmModal(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && confirmModal.shell.classList.contains('is-open')) {
                closeConfirmModal(false);
            }
        });
    }

    function bindForms() {
        document.getElementById('product-form').addEventListener('submit', handleProductSubmit);
        document.getElementById('customer-form').addEventListener('submit', handleCustomerSubmit);
        document.getElementById('sale-form').addEventListener('submit', handleSaleSubmit);
    }

    async function handleProductSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const formData = new FormData(form);
        const id = formData.get('id');
        const endpoint = id ? `${config.apiBase}/products/${id}` : `${config.apiBase}/products`;
        const imageInput = form.querySelector('[name="image"]');
        const hasImage = (imageInput?.files?.length ?? 0) > 0;
        const removeImage = form.querySelector('[name="remove_image"]').checked;
        const releasePending = setFormPending(form, id ? 'Salvando produto...' : 'Criando produto...');
        const dismissPending = notify(
            id ? 'Atualizando produto' : 'Criando produto',
            'Enviando dados do catalogo para a API.',
            'info',
            { duration: 0 },
        );

        if (id) {
            formData.append('_method', 'PUT');
        }

        formData.set('remove_image', removeImage ? '1' : '0');

        try {
            clearFormErrors('product-errors');
            const requestOptions = hasImage
                ? {
                    method: 'POST',
                    body: formData,
                }
                : {
                    method: id ? 'PUT' : 'POST',
                    json: {
                        name: formData.get('name'),
                        description: formData.get('description'),
                        price: formData.get('price'),
                        remove_image: removeImage,
                    },
                };
            const response = await apiRequest(endpoint, requestOptions);
            const product = unwrapData(response);

            dismissPending();
            reconcileProductMutation(product, id ? 'update' : 'create');
            notify('Produto salvo', 'Os dados do catalogo foram atualizados.', 'success');
            resetForm('product-form');
        } catch (error) {
            dismissPending();
            renderFormErrors('product-errors', error);
            notify('Falha ao salvar produto', error.message, 'error');
        } finally {
            releasePending();
        }
    }

    async function handleCustomerSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const formData = new FormData(form);
        const id = formData.get('id');
        const payload = {
            name: formData.get('name'),
            email: formData.get('email'),
            cpf: formData.get('cpf'),
        };
        const endpoint = id ? `${config.apiBase}/customers/${id}` : `${config.apiBase}/customers`;
        const releasePending = setFormPending(form, id ? 'Salvando cliente...' : 'Criando cliente...');
        const dismissPending = notify(
            id ? 'Atualizando cliente' : 'Criando cliente',
            'Enviando dados do CRM para a API.',
            'info',
            { duration: 0 },
        );

        try {
            clearFormErrors('customer-errors');
            const response = await apiRequest(endpoint, {
                method: id ? 'PUT' : 'POST',
                json: payload,
            });
            const customer = unwrapData(response);

            dismissPending();
            reconcileCustomerMutation(customer, id ? 'update' : 'create');
            notify('Cliente salvo', 'Os registros de clientes foram atualizados.', 'success');
            resetForm('customer-form');
        } catch (error) {
            dismissPending();
            renderFormErrors('customer-errors', error);
            notify('Falha ao salvar cliente', error.message, 'error');
        } finally {
            releasePending();
        }
    }

    async function handleSaleSubmit(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const formData = new FormData(form);
        const id = formData.get('id');
        const endpoint = id ? `${config.apiBase}/sales/${id}` : `${config.apiBase}/sales`;
        const payload = {
            product_id: Number(formData.get('product_id')),
            customer_id: Number(formData.get('customer_id')),
            sold_at: toApiDateTime(formData.get('sold_at')),
            quantity: Number(formData.get('quantity')),
            discount: String(formData.get('discount')),
            status: formData.get('status'),
        };
        const releasePending = setFormPending(form, id ? 'Salvando venda...' : 'Criando venda...');
        const dismissPending = notify(
            id ? 'Atualizando venda' : 'Criando venda',
            'Enviando dados da transacao para a API.',
            'info',
            { duration: 0 },
        );

        try {
            clearFormErrors('sale-errors');
            const response = await apiRequest(endpoint, {
                method: id ? 'PUT' : 'POST',
                json: payload,
            });
            const sale = unwrapData(response);

            dismissPending();
            reconcileSaleMutation(sale, id ? 'update' : 'create');
            notify('Venda salva', 'O historico de transacoes foi atualizado.', 'success');
            resetForm('sale-form');
        } catch (error) {
            dismissPending();
            renderFormErrors('sale-errors', error);
            notify('Falha ao salvar venda', error.message, 'error');
        } finally {
            releasePending();
        }
    }

    async function refreshEntity(entity) {
        if (entity === 'products') {
            await refreshProducts();
            return;
        }

        if (entity === 'customers') {
            await refreshCustomers();
            return;
        }

        await refreshSales();
    }

    async function refreshProducts() {
        beginEntityLoading('products');

        try {
            const response = await apiRequest(buildUrl(`${config.apiBase}/products`, {
                per_page: state.products.perPage,
                page: state.products.page,
                search: state.products.search,
                sort: 'created_at',
                direction: 'desc',
            }));

            finishEntityLoading('products');
            storeCollection('products', response);
        } catch (error) {
            finishEntityLoading('products');
            renderEntityState('products');
            throw error;
        }
    }

    async function refreshCustomers() {
        beginEntityLoading('customers');

        try {
            const response = await apiRequest(buildUrl(`${config.apiBase}/customers`, {
                per_page: state.customers.perPage,
                page: state.customers.page,
                search: state.customers.search,
                sort: 'created_at',
                direction: 'desc',
            }));

            finishEntityLoading('customers');
            storeCollection('customers', response);
        } catch (error) {
            finishEntityLoading('customers');
            renderEntityState('customers');
            throw error;
        }
    }

    async function refreshSales() {
        beginEntityLoading('sales');

        try {
            const response = await apiRequest(buildUrl(`${config.apiBase}/sales`, {
                per_page: state.sales.perPage,
                page: state.sales.page,
                sort: 'sold_at',
                direction: 'desc',
                status: state.sales.status,
                sold_from: state.sales.sold_from,
                sold_to: state.sales.sold_to,
            }));

            finishEntityLoading('sales');
            storeCollection('sales', response);
        } catch (error) {
            finishEntityLoading('sales');
            renderEntityState('sales');
            throw error;
        }
    }

    async function refreshSaleReferences() {
        const [products, customers] = await Promise.all([
            apiRequest(buildUrl(`${config.apiBase}/products`, {
                per_page: 100,
                sort: 'name',
                direction: 'asc',
            })),
            apiRequest(buildUrl(`${config.apiBase}/customers`, {
                per_page: 100,
                sort: 'name',
                direction: 'asc',
            })),
        ]);

        references.products = products.data ?? [];
        references.customers = customers.data ?? [];
        renderSaleReferenceSelects();
    }

    function storeCollection(entity, response) {
        state[entity].items = Array.isArray(response.data) ? response.data : [];
        state[entity].meta = response.meta ?? buildFallbackMeta(entity, state[entity].items.length);
        renderEntityState(entity);
    }

    function renderEntityState(entity) {
        const collection = state[entity];
        const total = collection.meta?.total ?? collection.items.length;

        updateMetric(entity, total);
        renderPanelSummary(entity);

        if (entity === 'products') {
            renderCollection('products-list', collection.items, renderProductCard);
            renderPagination('products', 'products-pagination');
            return;
        }

        if (entity === 'customers') {
            renderCollection('customers-list', collection.items, renderCustomerCard);
            renderPagination('customers', 'customers-pagination');
            return;
        }

        renderCollection('sales-list', collection.items, renderSaleCard);
        renderPagination('sales', 'sales-pagination');
        renderSalesSummary(collection.items, collection.meta ?? {});
    }

    function beginEntityLoading(entity) {
        state[entity].loading = true;
        renderEntityLoading(entity);
    }

    function finishEntityLoading(entity) {
        state[entity].loading = false;
    }

    function renderEntityLoading(entity) {
        const listMap = {
            products: 'products-list',
            customers: 'customers-list',
            sales: 'sales-list',
        };
        const paginationMap = {
            products: 'products-pagination',
            customers: 'customers-pagination',
            sales: 'sales-pagination',
        };
        const container = document.getElementById(listMap[entity]);
        const pagination = document.getElementById(paginationMap[entity]);
        const skeletonCount = Math.min(Math.max(state[entity].perPage, 1), 4);

        renderPanelSummary(entity);
        container.innerHTML = buildSkeletonCards(skeletonCount);
        pagination.innerHTML = '<span class="pagination-meta">Carregando resultados...</span>';
    }

    function reconcileProductMutation(product, mode) {
        if (!product) {
            scheduleEntityRefresh('products', 0);
            return;
        }

        upsertReferenceItem('products', product);
        patchVisibleSalesFromReference('product', product);
        applyLocalMutation('products', product, mode, matchesProductFilters);
    }

    function reconcileCustomerMutation(customer, mode) {
        if (!customer) {
            scheduleEntityRefresh('customers', 0);
            return;
        }

        upsertReferenceItem('customers', customer);
        patchVisibleSalesFromReference('customer', customer);

        if (usesCustomerCpfSearch()) {
            scheduleEntityRefresh('customers', 0);
            return;
        }

        applyLocalMutation('customers', customer, mode, matchesCustomerFilters);
    }

    function reconcileSaleMutation(sale, mode) {
        if (!sale) {
            scheduleEntityRefresh('sales', 0);
            return;
        }

        applyLocalMutation('sales', sale, mode, matchesSaleFilters);
    }

    function applyLocalMutation(entity, item, mode, matchesFilter) {
        const collection = state[entity];
        let items = [...collection.items];
        const index = items.findIndex((existing) => Number(existing.id) === Number(item.id));
        const wasVisible = index >= 0;
        const matches = mode === 'delete' ? wasVisible : matchesFilter(item);
        const canInsertOnCurrentPage = collection.page === 1;
        let totalDelta = 0;

        if (mode === 'create' && matches) {
            totalDelta = 1;

            if (canInsertOnCurrentPage) {
                items = [item, ...items.filter((existing) => Number(existing.id) !== Number(item.id))];
            }
        }

        if (mode === 'update') {
            if (wasVisible && matches) {
                items[index] = item;
            }

            if (wasVisible && !matches) {
                items.splice(index, 1);
                totalDelta = -1;
            }
        }

        if (mode === 'delete' && wasVisible) {
            items.splice(index, 1);
            totalDelta = -1;
        }

        collection.items = items.slice(0, collection.perPage);
        updateLocalMeta(entity, totalDelta);
        renderEntityState(entity);

        if (collection.items.length === 0 && collection.page > 1 && (collection.meta?.total ?? 0) > 0) {
            collection.page -= 1;
            scheduleEntityRefresh(entity, 0);
            return;
        }

        if (needsBackfill(entity, mode)) {
            scheduleEntityRefresh(entity);
        }
    }

    function needsBackfill(entity, mode) {
        if (!['delete', 'update'].includes(mode)) {
            return false;
        }

        const collection = state[entity];
        const total = Number(collection.meta?.total ?? collection.items.length);

        return collection.items.length < collection.perPage
            && total > collection.items.length;
    }

    function updateLocalMeta(entity, totalDelta) {
        const collection = state[entity];
        const meta = { ...buildFallbackMeta(entity, collection.items.length), ...collection.meta };
        const currentTotal = Number(meta.total ?? collection.items.length);

        meta.total = Math.max(0, currentTotal + totalDelta);
        meta.current_page = collection.page;
        meta.per_page = collection.perPage;
        meta.last_page = Math.max(1, Math.ceil(Math.max(meta.total, 1) / collection.perPage));

        if (collection.items.length === 0 || meta.total === 0) {
            meta.from = null;
            meta.to = null;
        } else {
            const from = Math.min(((collection.page - 1) * collection.perPage) + 1, meta.total);

            meta.from = from;
            meta.to = Math.min(from + collection.items.length - 1, meta.total);
        }

        collection.meta = meta;
    }

    function buildFallbackMeta(entity, itemCount) {
        return {
            current_page: state[entity].page,
            last_page: Math.max(1, Math.ceil(Math.max(itemCount, 1) / state[entity].perPage)),
            per_page: state[entity].perPage,
            total: itemCount,
            from: itemCount > 0 ? 1 : null,
            to: itemCount > 0 ? itemCount : null,
        };
    }

    function scheduleEntityRefresh(entity, delay = 180) {
        window.clearTimeout(refreshTimers[entity]);
        refreshTimers[entity] = window.setTimeout(() => {
            refreshEntity(entity).catch((error) => {
                window.console.error(`Background refresh failed for ${entity}.`, error);
            });
        }, delay);
    }

    function renderSaleReferenceSelects() {
        hydrateSelect(selectors.product, references.products, 'Selecione um produto', (item) => ({
            value: item.id,
            label: `${item.name} | ${currency(item.price)}`,
        }));

        hydrateSelect(selectors.customer, references.customers, 'Selecione um cliente', (item) => ({
            value: item.id,
            label: `${item.name} | ${item.email}`,
        }));
    }

    function upsertReferenceItem(entity, item) {
        const key = entity === 'products' ? 'products' : 'customers';
        const items = references[key].filter((existing) => Number(existing.id) !== Number(item.id));

        items.push(item);
        items.sort((left, right) => String(left.name ?? '').localeCompare(String(right.name ?? '')));
        references[key] = items;
        renderSaleReferenceSelects();
    }

    function removeReferenceItem(entity, id) {
        const key = entity === 'product' ? 'products' : 'customers';
        references[key] = references[key].filter((existing) => Number(existing.id) !== Number(id));
        renderSaleReferenceSelects();
    }

    function renderPanelSummary(entity) {
        if (entity === 'products') {
            renderProductsPanelSummary();
            return;
        }

        if (entity === 'customers') {
            renderCustomersPanelSummary();
            return;
        }

        renderSalesPanelSummary();
    }

    function renderProductsPanelSummary() {
        const meta = state.products.meta ?? buildFallbackMeta('products', state.products.items.length);
        const visibleCount = state.products.items.length;
        const prices = state.products.items
            .map((product) => Number(product.price))
            .filter((price) => Number.isFinite(price) && price > 0);
        const search = state.products.search.trim();

        setPanelSummaryStat('products', 'primary', `${visibleCount} ${visibleCount === 1 ? 'item' : 'itens'}`, `${meta.total ?? visibleCount} no total`);
        setPanelSummaryStat(
            'products',
            'secondary',
            prices.length > 0 ? `${currency(Math.min(...prices))} ate ${currency(Math.max(...prices))}` : 'Sem faixa visivel',
            prices.length > 0 ? 'Baseado nos itens da tela' : 'Aguardando catalogo',
        );
        setPanelSummaryStat(
            'products',
            'tertiary',
            search === '' ? 'Sem busca' : abbreviate(search, 22),
            `Pagina ${meta.current_page ?? 1} de ${meta.last_page ?? 1}`,
        );
    }

    function renderCustomersPanelSummary() {
        const meta = state.customers.meta ?? buildFallbackMeta('customers', state.customers.items.length);
        const visibleCount = state.customers.items.length;
        const search = state.customers.search.trim();

        setPanelSummaryStat('customers', 'primary', `${visibleCount} ${visibleCount === 1 ? 'cliente' : 'clientes'}`, `${meta.total ?? visibleCount} no total`);
        setPanelSummaryStat(
            'customers',
            'secondary',
            search === '' ? 'Sem busca' : abbreviate(search, 22),
            search === '' ? `Pagina ${meta.current_page ?? 1} de ${meta.last_page ?? 1}` : (usesCustomerCpfSearch() ? 'Filtro por CPF ativo' : 'Busca textual ativa'),
        );
        setPanelSummaryStat('customers', 'tertiary', 'CPF mascarado', 'Reentrada exigida na edicao');
    }

    function renderSalesPanelSummary() {
        const meta = state.sales.meta ?? buildFallbackMeta('sales', state.sales.items.length);
        const visibleCount = state.sales.items.length;
        const net = state.sales.items.reduce((carry, sale) => carry + Number(sale.final_amount), 0);
        const completed = state.sales.items.filter((sale) => String(sale.status).toLowerCase() === 'completed').length;
        const status = state.sales.status ? labelForSaleStatus(state.sales.status) : 'Todos os status';

        setPanelSummaryStat('sales', 'primary', `${visibleCount} ${visibleCount === 1 ? 'venda' : 'vendas'}`, `Pagina ${meta.current_page ?? 1} de ${meta.last_page ?? 1}`);
        setPanelSummaryStat('sales', 'secondary', status, buildSalesPeriodLabel());
        setPanelSummaryStat('sales', 'tertiary', currency(net), `${completed} concluidas visiveis`);
    }

    function setPanelSummaryStat(entity, key, value, note) {
        const summary = panelSummaries[entity];

        if (!summary) {
            return;
        }

        summary[key].textContent = value;
        summary[`${key}Note`].textContent = note;
    }

    function patchVisibleSalesFromReference(type, item) {
        let changed = false;

        state.sales.items = state.sales.items.map((sale) => {
            if (type === 'product' && Number(sale.product_id) === Number(item.id)) {
                changed = true;

                return {
                    ...sale,
                    product: {
                        id: item.id,
                        name: item.name,
                    },
                };
            }

            if (type === 'customer' && Number(sale.customer_id) === Number(item.id)) {
                changed = true;

                return {
                    ...sale,
                    customer: {
                        id: item.id,
                        name: item.name,
                    },
                };
            }

            return sale;
        });

        if (changed) {
            renderEntityState('sales');
        }
    }

    function renderCollection(elementId, items, renderer) {
        const container = document.getElementById(elementId);

        if (items.length === 0) {
            container.innerHTML = renderEmptyState(elementId);
            bindEmptyStateActions(container);
            return;
        }

        container.innerHTML = items.map(renderer).join('');
        bindCardActions(container);
    }

    function buildSkeletonCards(count) {
        return Array.from({ length: count }, () => `
            <article class="entity-card entity-card-skeleton" aria-hidden="true">
                <div class="entity-card-top">
                    <span class="skeleton-block skeleton-chip"></span>
                    <span class="skeleton-block skeleton-chip skeleton-chip-short"></span>
                </div>
                <div class="skeleton-block skeleton-image"></div>
                <div class="entity-card-copy">
                    <span class="skeleton-block skeleton-title"></span>
                    <span class="skeleton-block skeleton-line"></span>
                    <span class="skeleton-block skeleton-line skeleton-line-short"></span>
                </div>
                <div class="skeleton-grid">
                    <span class="skeleton-block skeleton-metric"></span>
                    <span class="skeleton-block skeleton-metric"></span>
                    <span class="skeleton-block skeleton-metric"></span>
                    <span class="skeleton-block skeleton-metric"></span>
                </div>
                <div class="entity-card-actions">
                    <span class="skeleton-block skeleton-action"></span>
                    <span class="skeleton-block skeleton-action skeleton-action-short"></span>
                </div>
            </article>
        `).join('');
    }

    function renderProductCard(product) {
        const image = product.image
            ? `<img class="product-preview" src="${escapeHtml(product.image.url)}" alt="${escapeHtml(product.name)}">`
            : '<div class="product-preview product-preview-empty">Sem imagem</div>';
        const imageLabel = product.image ? 'Imagem anexada' : 'Sem imagem';

        return `
            <article class="entity-card">
                <div class="entity-card-top">
                    <span class="entity-chip">Produto</span>
                    <span class="entity-chip ${product.image ? 'is-positive' : ''}">${imageLabel}</span>
                </div>
                ${image}
                <div class="entity-card-copy">
                    <h3>${escapeHtml(product.name)}</h3>
                    <p class="entity-card-lead">${escapeHtml(product.description)}</p>
                </div>
                <dl>
                    <div><dt>Pre&ccedil;o</dt><dd>${currency(product.price)}</dd></div>
                    <div><dt>Status visual</dt><dd>${imageLabel}</dd></div>
                </dl>
                <div class="entity-card-actions">
                    <button class="button-inline" type="button" data-edit-entity="product" data-entity='${toJsonAttribute(product)}'>Editar</button>
                    <button class="button-inline is-danger" type="button" data-delete-entity="product" data-id="${product.id}">Excluir</button>
                </div>
            </article>
        `;
    }

    function renderCustomerCard(customer) {
        return `
            <article class="entity-card">
                <div class="entity-card-top">
                    <span class="entity-chip">Cliente</span>
                    <span class="entity-chip">CRM</span>
                </div>
                <div class="entity-card-copy">
                    <h3>${escapeHtml(customer.name)}</h3>
                    <p class="entity-card-lead">Cadastro pronto para relacionar vendas com e-mail &uacute;nico e CPF mascarado na resposta.</p>
                </div>
                <dl>
                    <div><dt>E-mail</dt><dd>${escapeHtml(customer.email)}</dd></div>
                    <div><dt>CPF</dt><dd>${escapeHtml(customer.cpf)}</dd></div>
                </dl>
                <div class="entity-card-actions">
                    <button class="button-inline" type="button" data-edit-entity="customer" data-entity='${toJsonAttribute(customer)}'>Editar</button>
                    <button class="button-inline is-danger" type="button" data-delete-entity="customer" data-id="${customer.id}">Excluir</button>
                </div>
            </article>
        `;
    }

    function renderSaleCard(sale) {
        const status = String(sale.status).toLowerCase();

        return `
            <article class="entity-card">
                <div class="entity-card-top">
                    <span class="entity-chip">Venda</span>
                    <span class="status-badge ${escapeHtml(status)}">${escapeHtml(labelForSaleStatus(status))}</span>
                </div>
                <div class="entity-card-copy">
                    <h3>${escapeHtml(sale.product?.name ?? `Produto #${sale.product_id}`)}</h3>
                    <p class="entity-card-lead">${escapeHtml(sale.customer?.name ?? `Cliente #${sale.customer_id}`)} | ${formatDateTime(sale.sold_at)}</p>
                </div>
                <dl>
                    <div><dt>Quantidade</dt><dd>${sale.quantity}</dd></div>
                    <div><dt>Pre&ccedil;o unit&aacute;rio</dt><dd>${currency(sale.unit_price)}</dd></div>
                    <div><dt>Valor bruto</dt><dd>${currency(sale.gross_amount)}</dd></div>
                    <div><dt>Valor final</dt><dd>${currency(sale.final_amount)}</dd></div>
                </dl>
                <div class="entity-card-actions">
                    <button class="button-inline" type="button" data-edit-entity="sale" data-entity='${toJsonAttribute(sale)}'>Editar</button>
                    <button class="button-inline is-danger" type="button" data-delete-entity="sale" data-id="${sale.id}">Excluir</button>
                </div>
            </article>
        `;
    }

    function bindCardActions(container) {
        container.querySelectorAll('[data-edit-entity]').forEach((button) => {
            button.addEventListener('click', () => {
                const entity = button.dataset.editEntity;
                const payload = JSON.parse(button.dataset.entity);

                if (entity === 'product') {
                    fillProductForm(payload);
                    activatePanel('products');
                    return;
                }

                if (entity === 'customer') {
                    fillCustomerForm(payload);
                    activatePanel('customers');
                    return;
                }

                fillSaleForm(payload);
                activatePanel('sales');
            });
        });

        container.querySelectorAll('[data-delete-entity]').forEach((button) => {
            button.addEventListener('click', async () => {
                const entity = button.dataset.deleteEntity;
                const id = button.dataset.id;

                const confirmed = await confirmDeletion(entity);

                if (!confirmed) {
                    return;
                }

                const releasePending = setElementPending(button, 'Excluindo...');
                const dismissPending = notify(
                    `Excluindo ${translateEntityName(entity)}`,
                    'Processando a remocao do registro.',
                    'info',
                    { duration: 0 },
                );

                try {
                    await apiRequest(`${config.apiBase}/${entity}s/${id}`, { method: 'DELETE' });

                    dismissPending();
                    removeEntityLocally(entity, id);
                    notify('Registro removido', `${translateEntityName(entity, true)} excluido com sucesso.`, 'success');
                } catch (error) {
                    dismissPending();
                    notify('Falha ao excluir', error.message, 'error');
                } finally {
                    releasePending();
                }
            });
        });
    }

    function bindEmptyStateActions(container) {
        container.querySelectorAll('[data-empty-action]').forEach((button) => {
            button.addEventListener('click', async () => {
                const action = button.dataset.emptyAction;
                const entity = button.dataset.emptyEntity;

                if (action === 'focus-form') {
                    focusEntityForm(entity);
                    return;
                }

                if (action === 'clear-filters') {
                    const releasePending = setElementPending(button, 'Limpando...');

                    try {
                        await clearEntityFilters(entity);
                    } finally {
                        releasePending();
                    }
                }
            });
        });
    }

    function removeEntityLocally(entity, id) {
        if (entity === 'product') {
            removeReferenceItem(entity, id);
            applyLocalMutation('products', { id }, 'delete', () => true);
            return;
        }

        if (entity === 'customer') {
            removeReferenceItem(entity, id);
            applyLocalMutation('customers', { id }, 'delete', () => true);
            return;
        }

        applyLocalMutation('sales', { id }, 'delete', () => true);
    }

    function confirmDeletion(entity) {
        if (!confirmModal.shell) {
            return Promise.resolve(true);
        }

        const entityName = translateEntityName(entity);

        confirmModal.title.textContent = `Excluir ${entityName}?`;
        confirmModal.description.textContent = `Esta acao remove permanentemente o ${entityName} selecionado do painel.`;
        confirmModal.confirm.textContent = `Excluir ${capitalize(entityName)}`;
        confirmModal.shell.classList.add('is-open');
        confirmModal.shell.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        window.setTimeout(() => {
            confirmModal.confirm?.focus();
        }, 0);

        return new Promise((resolve) => {
            confirmModalResolver = resolve;
        });
    }

    function closeConfirmModal(confirmed) {
        if (!confirmModal.shell || confirmModalResolver === null) {
            return;
        }

        confirmModal.shell.classList.remove('is-open');
        confirmModal.shell.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');

        const resolve = confirmModalResolver;
        confirmModalResolver = null;
        resolve(confirmed);
    }

    function fillProductForm(product) {
        const form = document.getElementById('product-form');
        form.elements.id.value = product.id;
        form.elements.name.value = product.name ?? '';
        form.elements.description.value = product.description ?? '';
        form.elements.price.value = product.price ?? '';
        form.elements.remove_image.checked = false;
        form.elements.image.value = '';
        setProductMeta(product);
        clearFormErrors('product-errors');
    }

    function fillCustomerForm(customer) {
        const form = document.getElementById('customer-form');
        const meta = document.getElementById('customer-meta');
        form.elements.id.value = customer.id;
        form.elements.name.value = customer.name ?? '';
        form.elements.email.value = customer.email ?? '';
        form.elements.cpf.value = '';
        form.elements.cpf.placeholder = 'Digite novamente o CPF para confirmar';
        meta.textContent = `Editando ${customer.name}. A API mascara o CPF nas respostas, entao digite o valor original para salvar as alteracoes.`;
        clearFormErrors('customer-errors');
    }

    function fillSaleForm(sale) {
        const form = document.getElementById('sale-form');
        form.elements.id.value = sale.id;
        form.elements.product_id.value = String(sale.product_id);
        form.elements.customer_id.value = String(sale.customer_id);
        form.elements.sold_at.value = toInputDateTime(sale.sold_at);
        form.elements.quantity.value = sale.quantity;
        form.elements.discount.value = sale.discount;
        form.elements.status.value = sale.status;
        clearFormErrors('sale-errors');
    }

    function resetForm(formId) {
        const form = document.getElementById(formId);
        form.reset();
        form.elements.id.value = '';
        clearFormErrors(`${formId.replace('-form', '')}-errors`);

        if (formId === 'product-form') {
            setProductMeta(null);
            form.elements.remove_image.checked = false;
        }

        if (formId === 'customer-form') {
            form.elements.cpf.placeholder = '529.982.247-25';
            document.getElementById('customer-meta').textContent = 'O CPF vem mascarado na API. Digite novamente ao atualizar um cliente.';
        }
    }

    async function clearEntityFilters(entity) {
        if (entity === 'products') {
            state.products.search = '';
            state.products.page = 1;
            document.querySelector('[data-search-input="products"]').value = '';
            await refreshProducts();
            return;
        }

        if (entity === 'customers') {
            state.customers.search = '';
            state.customers.page = 1;
            document.querySelector('[data-search-input="customers"]').value = '';
            await refreshCustomers();
            return;
        }

        state.sales.status = '';
        state.sales.sold_from = '';
        state.sales.sold_to = '';
        state.sales.page = 1;
        document.querySelector('[data-status-filter="sales"]').value = '';
        document.querySelector('[data-date-filter="sold_from"]').value = '';
        document.querySelector('[data-date-filter="sold_to"]').value = '';
        await refreshSales();
    }

    function setProductMeta(product) {
        const meta = document.getElementById('product-current-image');

        if (!product || !product.image) {
            meta.textContent = 'Nenhuma imagem vinculada no momento.';
            return;
        }

        meta.innerHTML = `Imagem atual: <a href="${escapeHtml(product.image.url)}" target="_blank" rel="noreferrer">${escapeHtml(product.image.path)}</a>`;
    }

    function hydrateSelect(select, items, placeholder, mapper) {
        const currentValue = select.value;
        const options = items.map((item) => mapper(item));

        select.innerHTML = [
            `<option value="">${escapeHtml(placeholder)}</option>`,
            ...options.map((option) => `<option value="${option.value}">${escapeHtml(option.label)}</option>`),
        ].join('');

        if (currentValue) {
            select.value = currentValue;
        }
    }

    function renderEmptyState(elementId) {
        const configByEntity = {
            products: buildProductEmptyState(),
            customers: buildCustomerEmptyState(),
            sales: buildSalesEmptyState(),
        };
        const entity = elementId.replace('-list', '');
        const config = configByEntity[entity] ?? {
            eyebrow: 'Sem registros',
            title: 'Nenhum registro encontrado',
            description: 'Ajuste os filtros ou crie um novo item para preencher esta lista.',
            actions: [{ entity, type: 'focus-form', label: 'Criar novo' }],
        };

        return `
            <article class="empty-state">
                <p class="empty-state-eyebrow">${config.eyebrow}</p>
                <h3>${config.title}</h3>
                <p>${config.description}</p>
                <div class="empty-state-actions">
                    ${config.actions.map((action) => `
                        <button
                            class="button ${action.type === 'clear-filters' ? 'button-ghost' : 'button-primary'}"
                            type="button"
                            data-empty-action="${action.type}"
                            data-empty-entity="${action.entity}"
                        >
                            ${action.label}
                        </button>
                    `).join('')}
                </div>
            </article>
        `;
    }

    function buildProductEmptyState() {
        if (state.products.search.trim() !== '') {
            return {
                eyebrow: 'Busca sem retorno',
                title: 'Nenhum produto encontrado',
                description: 'Revise o termo digitado ou limpe a busca para voltar ao cat&aacute;logo completo.',
                actions: [
                    { entity: 'products', type: 'clear-filters', label: 'Limpar busca' },
                    { entity: 'products', type: 'focus-form', label: 'Cadastrar produto' },
                ],
            };
        }

        return {
            eyebrow: 'Cat&aacute;logo vazio',
            title: 'Nenhum produto cadastrado ainda',
            description: 'Crie o primeiro item do cat&aacute;logo com nome, descri&ccedil;&atilde;o, pre&ccedil;o e imagem opcional.',
            actions: [{ entity: 'products', type: 'focus-form', label: 'Cadastrar produto' }],
        };
    }

    function buildCustomerEmptyState() {
        if (state.customers.search.trim() !== '') {
            return {
                eyebrow: 'Busca sem retorno',
                title: 'Nenhum cliente encontrado',
                description: 'A busca atual n&atilde;o retornou clientes. Limpe o termo ou cadastre um novo comprador.',
                actions: [
                    { entity: 'customers', type: 'clear-filters', label: 'Limpar busca' },
                    { entity: 'customers', type: 'focus-form', label: 'Cadastrar cliente' },
                ],
            };
        }

        return {
            eyebrow: 'CRM vazio',
            title: 'Nenhum cliente cadastrado ainda',
            description: 'Cadastre compradores com e-mail &uacute;nico e CPF estruturalmente v&aacute;lido para habilitar vendas.',
            actions: [{ entity: 'customers', type: 'focus-form', label: 'Cadastrar cliente' }],
        };
    }

    function buildSalesEmptyState() {
        const hasFilters = state.sales.status !== '' || state.sales.sold_from !== '' || state.sales.sold_to !== '';

        if (hasFilters) {
            return {
                eyebrow: 'Filtro sem retorno',
                title: 'Nenhuma venda encontrada',
                description: 'Os filtros aplicados n&atilde;o encontraram vendas. Limpe o recorte ou ajuste o per&iacute;odo e o status.',
                actions: [
                    { entity: 'sales', type: 'clear-filters', label: 'Limpar filtros' },
                    { entity: 'sales', type: 'focus-form', label: 'Registrar venda' },
                ],
            };
        }

        if (references.products.length === 0 || references.customers.length === 0) {
            return {
                eyebrow: 'Base incompleta',
                title: 'Cadastre clientes e produtos antes de vender',
                description: 'A primeira venda depende de um produto e de um cliente dispon&iacute;veis nos cadastros base.',
                actions: [{ entity: references.customers.length === 0 ? 'customers' : 'products', type: 'focus-form', label: references.customers.length === 0 ? 'Cadastrar cliente' : 'Cadastrar produto' }],
            };
        }

        return {
            eyebrow: 'Pipeline comercial',
            title: 'Nenhuma venda registrada ainda',
            description: 'Com a base pronta, registre a primeira transa&ccedil;&atilde;o para popular os indicadores financeiros.',
            actions: [{ entity: 'sales', type: 'focus-form', label: 'Registrar venda' }],
        };
    }

    function renderPagination(entity, elementId) {
        const meta = state[entity].meta;
        const container = document.getElementById(elementId);

        if (!meta || !meta.last_page || meta.last_page <= 1) {
            container.innerHTML = meta?.total && meta.from !== null && meta.to !== null
                ? `<span class="pagination-meta">Exibindo ${meta.from} a ${meta.to} de ${meta.total}</span>`
                : '';
            return;
        }

        const pages = buildPageList(meta.current_page, meta.last_page);

        container.innerHTML = `
            <span class="pagination-meta">Exibindo ${meta.from} a ${meta.to} de ${meta.total} | P&aacute;gina ${meta.current_page} de ${meta.last_page}</span>
            <div class="pagination-controls">
                <button class="page-pill" type="button" data-page-entity="${entity}" data-page-value="${meta.current_page - 1}" ${meta.current_page === 1 ? 'disabled' : ''}>Anterior</button>
                ${pages.map((page) => page === '...'
                    ? '<span class="page-pill" aria-hidden="true">...</span>'
                    : `<button class="page-pill ${page === meta.current_page ? 'is-active' : ''}" type="button" data-page-entity="${entity}" data-page-value="${page}">${page}</button>`).join('')}
                <button class="page-pill" type="button" data-page-entity="${entity}" data-page-value="${meta.current_page + 1}" ${meta.current_page === meta.last_page ? 'disabled' : ''}>Pr&oacute;xima</button>
            </div>
        `;

        container.querySelectorAll('[data-page-entity][data-page-value]').forEach((button) => {
            button.addEventListener('click', async () => {
                state[entity].page = Number(button.dataset.pageValue);
                await refreshEntity(entity);
            });
        });
    }

    function renderSalesSummary(items, meta) {
        const gross = items.reduce((carry, sale) => carry + Number(sale.gross_amount), 0);
        const discount = items.reduce((carry, sale) => carry + Number(sale.discount), 0);
        const net = items.reduce((carry, sale) => carry + Number(sale.final_amount), 0);
        const completed = items.filter((sale) => String(sale.status).toLowerCase() === 'completed').length;

        summaries.gross.textContent = currency(gross);
        summaries.discount.textContent = currency(discount);
        summaries.net.textContent = currency(net);
        summaries.completed.textContent = String(completed);
        summaries.range.textContent = buildSalesSummaryCaption(meta);
    }

    function buildSalesSummaryCaption(meta) {
        const fragments = [];

        fragments.push(state.sales.status ? `Status: ${labelForSaleStatus(state.sales.status)}` : 'Todos os status');
        fragments.push(state.sales.sold_from || state.sales.sold_to
            ? `Periodo: ${state.sales.sold_from || 'inicio'} ate ${state.sales.sold_to || 'hoje'}`
            : 'Todas as datas');

        if (meta.current_page && meta.last_page) {
            fragments.push(`Pagina visivel: ${meta.current_page}/${meta.last_page}`);
        }

        fragments.push('Os valores sao agregados a partir dos resultados visiveis na tela.');

        return fragments.join(' | ');
    }

    function buildSalesPeriodLabel() {
        if (!state.sales.sold_from && !state.sales.sold_to) {
            return 'Todas as datas';
        }

        return `${formatStaticDate(state.sales.sold_from) || 'Inicio'} ate ${formatStaticDate(state.sales.sold_to) || 'hoje'}`;
    }

    function buildPageList(currentPage, lastPage) {
        const pages = new Set([1, lastPage]);

        for (let page = currentPage - 1; page <= currentPage + 1; page += 1) {
            if (page > 1 && page < lastPage) {
                pages.add(page);
            }
        }

        const orderedPages = [...pages].sort((left, right) => left - right);
        const withEllipsis = [];

        orderedPages.forEach((page, index) => {
            const previousPage = orderedPages[index - 1];

            if (previousPage && page - previousPage > 1) {
                withEllipsis.push('...');
            }

            withEllipsis.push(page);
        });

        return withEllipsis;
    }

    async function apiRequest(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers ?? {}),
        };

        const requestOptions = {
            method: options.method ?? 'GET',
            headers,
        };

        if (options.json) {
            requestOptions.body = JSON.stringify(options.json);
            requestOptions.headers['Content-Type'] = 'application/json';
        }

        if (options.body) {
            requestOptions.body = options.body;
        }

        const response = await window.fetch(url, requestOptions);

        if (response.status === 204) {
            return null;
        }

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(payload.message ?? 'Request failed.');
            error.errors = payload.errors ?? {};
            throw error;
        }

        return payload;
    }

    function renderFormErrors(elementId, error) {
        const container = document.getElementById(elementId);
        const messages = [error.message, ...flattenErrors(error.errors ?? {})].filter(Boolean);

        if (messages.length === 0) {
            container.classList.remove('is-visible');
            container.textContent = '';
            return;
        }

        container.innerHTML = messages.map((message) => `<div>${escapeHtml(message)}</div>`).join('');
        container.classList.add('is-visible');
    }

    function clearFormErrors(elementId) {
        const container = document.getElementById(elementId);
        container.textContent = '';
        container.classList.remove('is-visible');
    }

    function flattenErrors(errors) {
        return Object.values(errors).flat().map((value) => String(value));
    }

    function updateMetric(entity, total) {
        if (metrics[entity]) {
            metrics[entity].textContent = String(total);
        }
    }

    function buildUrl(base, params) {
        const url = new URL(base, window.location.origin);

        Object.entries(params).forEach(([key, value]) => {
            if (value === '' || value === null || value === undefined) {
                return;
            }

            url.searchParams.set(key, String(value));
        });

        return url.toString();
    }

    function notify(title, message, type, options = {}) {
        const stack = document.getElementById('toast-stack');
        const toast = document.createElement('div');
        const duration = options.duration ?? 4200;

        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(message)}</span>`;
        stack.appendChild(toast);

        if (duration > 0) {
            window.setTimeout(() => {
                toast.remove();
            }, duration);
        }

        return () => {
            toast.remove();
        };
    }

    function activatePanel(panelName) {
        document.querySelector(`[data-panel-target="${panelName}"]`)?.click();
    }

    function focusEntityForm(entity) {
        activatePanel(entity);
        document.querySelector('.workspace')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });

        window.setTimeout(() => {
            const formMap = {
                products: '#product-form [name="name"]',
                customers: '#customer-form [name="name"]',
                sales: '#sale-form [name="product_id"]',
            };

            document.querySelector(formMap[entity])?.focus();
        }, 180);
    }

    function setFormPending(form, label) {
        const controls = [...form.querySelectorAll('input, textarea, select, button')];
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton?.textContent ?? '';

        form.setAttribute('aria-busy', 'true');
        controls.forEach((control) => {
            control.disabled = true;
        });

        if (submitButton) {
            submitButton.textContent = label;
            submitButton.classList.add('is-loading');
        }

        return () => {
            controls.forEach((control) => {
                control.disabled = false;
            });

            form.removeAttribute('aria-busy');

            if (submitButton) {
                submitButton.textContent = originalText;
                submitButton.classList.remove('is-loading');
            }
        };
    }

    function setElementPending(element, label) {
        const originalText = element.textContent ?? '';

        element.disabled = true;
        element.textContent = label;
        element.classList.add('is-loading');

        return () => {
            element.disabled = false;
            element.textContent = originalText;
            element.classList.remove('is-loading');
        };
    }

    function unwrapData(payload) {
        return payload?.data ?? null;
    }

    function matchesProductFilters(product) {
        const search = state.products.search.trim().toLowerCase();

        if (search === '') {
            return true;
        }

        return [product.name, product.description].some((value) => String(value ?? '').toLowerCase().includes(search));
    }

    function matchesCustomerFilters(customer) {
        const search = state.customers.search.trim().toLowerCase();

        if (search === '') {
            return true;
        }

        return [customer.name, customer.email].some((value) => String(value ?? '').toLowerCase().includes(search));
    }

    function matchesSaleFilters(sale) {
        const status = state.sales.status.trim().toLowerCase();

        if (status !== '' && String(sale.status).toLowerCase() !== status) {
            return false;
        }

        const soldAt = sale.sold_at ? new Date(sale.sold_at) : null;

        if (soldAt === null || Number.isNaN(soldAt.getTime())) {
            return false;
        }

        if (state.sales.sold_from) {
            const soldFrom = new Date(`${state.sales.sold_from}T00:00:00`);

            if (soldAt < soldFrom) {
                return false;
            }
        }

        if (state.sales.sold_to) {
            const soldTo = new Date(`${state.sales.sold_to}T23:59:59`);

            if (soldAt > soldTo) {
                return false;
            }
        }

        return true;
    }

    function usesCustomerCpfSearch() {
        return sanitizeDigits(state.customers.search) !== '';
    }

    function formatCpfInput(value) {
        const digits = sanitizeDigits(value).slice(0, 11);
        const parts = [];

        if (digits.length > 0) {
            parts.push(digits.slice(0, 3));
        }

        if (digits.length > 3) {
            parts.push(digits.slice(3, 6));
        }

        if (digits.length > 6) {
            parts.push(digits.slice(6, 9));
        }

        const prefix = parts.join('.');
        const suffix = digits.length > 9 ? digits.slice(9, 11) : '';

        return suffix === '' ? prefix : `${prefix}-${suffix}`;
    }

    function sanitizeDigits(value) {
        return String(value ?? '').replaceAll(/\D+/g, '');
    }

    function abbreviate(value, maxLength) {
        const normalized = String(value ?? '').trim();

        if (normalized.length <= maxLength) {
            return normalized;
        }

        return `${normalized.slice(0, maxLength - 1)}...`;
    }

    function toInputDateTime(dateTime) {
        if (!dateTime) {
            return '';
        }

        const date = new Date(dateTime);
        const localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));

        return localDate.toISOString().slice(0, 16);
    }

    function toApiDateTime(value) {
        if (!value) {
            return '';
        }

        return `${value.replace('T', ' ')}:00`;
    }

    function formatStaticDate(value) {
        if (!value) {
            return '';
        }

        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
        }).format(new Date(`${value}T00:00:00`));
    }

    function formatDateTime(value) {
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(value));
    }

    function currency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(Number(value));
    }

    function labelForSaleStatus(status) {
        const labels = {
            pending: 'Pendente',
            completed: 'Concluida',
            cancelled: 'Cancelada',
        };

        return labels[String(status).toLowerCase()] ?? String(status);
    }

    function translateEntityName(entity, lowerCase = false) {
        const labels = {
            product: 'produto',
            customer: 'cliente',
            sale: 'venda',
        };
        const label = labels[String(entity).toLowerCase()] ?? String(entity);

        return lowerCase ? label : capitalize(label);
    }

    function capitalize(value) {
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function toJsonAttribute(value) {
        return escapeHtml(JSON.stringify(value));
    }
})();
