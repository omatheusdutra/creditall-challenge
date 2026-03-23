<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Creditall Control Center</title>
    <meta name="theme-color" content="#b54f28">
    <meta
        name="description"
        content="Painel operacional de produtos, clientes e vendas construído sobre a Creditall Sales API."
    >
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/creditall-favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard.css') }}">
</head>
<body>
    <div class="page-shell">
        <main class="dashboard">
            <section class="hero-card">
                <div class="hero-copy">
                    <p class="eyebrow">Creditall Sales API</p>
                    <h1>Central de opera&ccedil;&otilde;es comerciais.</h1>
                    <p class="hero-text">
                        Uma superf&iacute;cie administrativa leve sobre a API Laravel. Gerencie o ciclo completo sem
                        sair do navegador.
                    </p>

                    <article class="hero-highlight">
                        <p class="eyebrow">Fluxo recomendado</p>
                        <h2>Comece pelo cadastro base e feche o ciclo comercial.</h2>
                        <ol class="hero-flow">
                            <li><strong>Clientes</strong><span>Cadastre compradores com e-mail &uacute;nico e CPF v&aacute;lido.</span></li>
                            <li><strong>Produtos</strong><span>Defina cat&aacute;logo, descri&ccedil;&otilde;es, pre&ccedil;os e imagem opcional.</span></li>
                            <li><strong>Vendas</strong><span>Registre a transa&ccedil;&atilde;o com quantidade, desconto e status.</span></li>
                        </ol>
                        <div class="hero-highlight-actions">
                            <button class="button button-ghost hero-jump" type="button" data-panel-jump="customers">Abrir clientes</button>
                            <button class="button button-ghost hero-jump" type="button" data-panel-jump="products">Abrir produtos</button>
                            <button class="button button-ghost hero-jump" type="button" data-panel-jump="sales">Abrir vendas</button>
                        </div>
                    </article>
                </div>

                <div class="hero-status">
                    <div class="hero-status-grid">
                        <div class="status-card status-card-api">
                            <span>Vers&atilde;o da API</span>
                            <strong>v1</strong>
                            <small>Contrato est&aacute;vel para integra&ccedil;&otilde;es e evolu&ccedil;&atilde;o incremental.</small>
                        </div>
                        <div class="status-card status-card-storage">
                            <span>Storage</span>
                            <strong>Disco p&uacute;blico local</strong>
                            <small>Estrutura organizada para m&iacute;dia local, com caminho claro para S3.</small>
                        </div>
                        <div class="status-card status-card-domain">
                            <span>Modelo de dom&iacute;nio</span>
                            <strong>Produtos, Clientes, Vendas</strong>
                            <small>N&uacute;cleo comercial enxuto, transacional e pronto para extens&otilde;es futuras.</small>
                        </div>
                    </div>

                    <div class="hero-command-deck">
                        <div class="hero-actions">
                            <a class="button button-primary" href="/docs">Abrir documenta&ccedil;&atilde;o</a>
                            <a class="button button-secondary" href="/api/v1/products" target="_blank" rel="noreferrer">
                                Ver JSON da API
                            </a>
                        </div>

                        <div class="hero-pill-row" aria-label="Highlights do painel">
                            <span class="hero-pill">CRUD completo</span>
                            <span class="hero-pill">Upload de imagem</span>
                            <span class="hero-pill">API versionada</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="metrics-grid" aria-label="Operational metrics">
                <article class="metric-card">
                    <span class="metric-label">Produtos</span>
                    <strong class="metric-value" data-metric="products">0</strong>
                    <p>Itens de cat&aacute;logo dispon&iacute;veis para venda.</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Clientes</span>
                    <strong class="metric-value" data-metric="customers">0</strong>
                    <p>Compradores registrados na base de CRM.</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Vendas</span>
                    <strong class="metric-value" data-metric="sales">0</strong>
                    <p>Transa&ccedil;&otilde;es comerciais registradas.</p>
                </article>
            </section>

            <section class="summary-band" aria-label="Current sales summary">
                <div class="summary-heading">
                    <div>
                        <p class="eyebrow">Sales intelligence</p>
                        <h2>Performance financeira da opera&ccedil;&atilde;o comercial.</h2>
                    </div>
                </div>

                <div class="summary-grid">
                    <article class="summary-card">
                        <span class="summary-label">Valor bruto</span>
                        <strong class="summary-value" data-summary="gross">R$ 0,00</strong>
                        <p>Soma das vendas vis&iacute;veis antes dos descontos.</p>
                    </article>
                    <article class="summary-card">
                        <span class="summary-label">Desconto</span>
                        <strong class="summary-value" data-summary="discount">R$ 0,00</strong>
                        <p>Total de abatimento aplicado na vis&atilde;o atual.</p>
                    </article>
                    <article class="summary-card">
                        <span class="summary-label">Valor l&iacute;quido</span>
                        <strong class="summary-value" data-summary="net">R$ 0,00</strong>
                        <p>Valor final arrecadado ap&oacute;s descontos.</p>
                    </article>
                    <article class="summary-card">
                        <span class="summary-label">Vendas conclu&iacute;das</span>
                        <strong class="summary-value" data-summary="completed">0</strong>
                        <p>Quantidade de vendas vis&iacute;veis com status conclu&iacute;do.</p>
                    </article>
                </div>
            </section>

            <section class="workspace">
                <aside class="workspace-nav" aria-label="Entity navigation">
                    <button class="nav-tab is-active" type="button" data-panel-target="products">
                        <span class="nav-index">01</span>
                        <span><strong>Produtos</strong><small>Cat&aacute;logo e gest&atilde;o de imagens</small></span>
                    </button>
                    <button class="nav-tab" type="button" data-panel-target="customers">
                        <span class="nav-index">02</span>
                        <span><strong>Clientes</strong><small>CRM e valida&ccedil;&atilde;o de CPF</small></span>
                    </button>
                    <button class="nav-tab" type="button" data-panel-target="sales">
                        <span class="nav-index">03</span>
                        <span><strong>Vendas</strong><small>Hist&oacute;rico e snapshot de pre&ccedil;o</small></span>
                    </button>
                </aside>

                <div class="workspace-panels">
                    <section class="panel is-active" data-panel="products">
                        <div class="panel-heading">
                            <div><p class="eyebrow">Cat&aacute;logo</p><h2>Produtos</h2></div>
                            <p>Mantenha o cat&aacute;logo de produtos, faixas de pre&ccedil;o e imagens opcionais.</p>
                        </div>
                        <div class="panel-mini-summary" data-panel-summary="products">
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Cat&aacute;logo vis&iacute;vel</span>
                                <strong class="panel-mini-value" data-panel-value="products-primary">0 itens</strong>
                                <small class="panel-mini-note" data-panel-note="products-primary">0 no total</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Faixa vis&iacute;vel</span>
                                <strong class="panel-mini-value" data-panel-value="products-secondary">Sem faixa</strong>
                                <small class="panel-mini-note" data-panel-note="products-secondary">Aguardando produtos</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Busca atual</span>
                                <strong class="panel-mini-value" data-panel-value="products-tertiary">Sem busca</strong>
                                <small class="panel-mini-note" data-panel-note="products-tertiary">P&aacute;gina 1 de 1</small>
                            </article>
                        </div>
                        <div class="panel-layout">
                            <form class="entity-form" id="product-form" enctype="multipart/form-data">
                                <div class="form-heading"><h3>Editor de produto</h3><p>Crie ou atualize itens do cat&aacute;logo.</p></div>
                                <input type="hidden" name="id">
                                <label><span>Nome</span><input name="name" type="text" maxlength="150" required></label>
                                <label><span>Descri&ccedil;&atilde;o</span><textarea name="description" rows="5" maxlength="5000" required></textarea></label>
                                <label><span>Pre&ccedil;o</span><input name="price" type="number" min="0.01" step="0.01" required></label>
                                <label><span>Imagem</span><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                                <label class="checkbox-field">
                                    <input name="remove_image" type="checkbox" value="1">
                                    <span>Remover imagem atual na atualiza&ccedil;&atilde;o</span>
                                </label>
                                <div class="form-meta" id="product-current-image"></div>
                                <div class="form-errors" id="product-errors" role="alert"></div>
                                <div class="form-actions">
                                    <button class="button button-primary" type="submit">Salvar produto</button>
                                    <button class="button button-ghost" type="button" data-reset-form="product-form">Limpar</button>
                                </div>
                            </form>

                            <div class="entity-browser">
                                <div class="browser-toolbar">
                                    <label class="search-field">
                                        <span>Buscar</span>
                                        <input type="search" placeholder="Buscar por nome ou descri&ccedil;&atilde;o" data-search-input="products">
                                    </label>
                                    <label class="compact-field">
                                        <span>Por p&aacute;gina</span>
                                        <select data-page-size="products">
                                            <option value="6" selected>6</option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                        </select>
                                    </label>
                                    <button class="button button-ghost toolbar-action" type="button" data-refresh-entity="products">Atualizar</button>
                                </div>
                                <div class="collection-grid" id="products-list"></div>
                                <div class="pagination" id="products-pagination"></div>
                            </div>
                        </div>
                    </section>

                    <section class="panel" data-panel="customers">
                        <div class="panel-heading">
                            <div><p class="eyebrow">CRM</p><h2>Clientes</h2></div>
                            <p>Cadastre compradores com dados normalizados, e-mail &uacute;nico e CPF estruturalmente v&aacute;lido.</p>
                        </div>
                        <div class="panel-mini-summary" data-panel-summary="customers">
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">CRM vis&iacute;vel</span>
                                <strong class="panel-mini-value" data-panel-value="customers-primary">0 clientes</strong>
                                <small class="panel-mini-note" data-panel-note="customers-primary">0 no total</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Consulta ativa</span>
                                <strong class="panel-mini-value" data-panel-value="customers-secondary">Sem busca</strong>
                                <small class="panel-mini-note" data-panel-note="customers-secondary">Busca textual</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Privacidade</span>
                                <strong class="panel-mini-value" data-panel-value="customers-tertiary">CPF mascarado</strong>
                                <small class="panel-mini-note" data-panel-note="customers-tertiary">Reentrada exigida na edi&ccedil;&atilde;o</small>
                            </article>
                        </div>
                        <div class="panel-layout">
                            <form class="entity-form" id="customer-form">
                                <div class="form-heading"><h3>Editor de cliente</h3><p>Crie e atualize registros de clientes.</p></div>
                                <input type="hidden" name="id">
                                <label><span>Nome</span><input name="name" type="text" maxlength="150" required></label>
                                <label><span>E-mail</span><input name="email" type="email" maxlength="150" required></label>
                                <label><span>CPF</span><input name="cpf" type="text" inputmode="numeric" maxlength="14" placeholder="529.982.247-25" data-cpf-mask required></label>
                                <div class="form-meta" id="customer-meta">O CPF vem mascarado na API. Digite novamente ao atualizar um cliente.</div>
                                <div class="form-errors" id="customer-errors" role="alert"></div>
                                <div class="form-actions">
                                    <button class="button button-primary" type="submit">Salvar cliente</button>
                                    <button class="button button-ghost" type="button" data-reset-form="customer-form">Limpar</button>
                                </div>
                            </form>

                            <div class="entity-browser">
                                <div class="browser-toolbar">
                                    <label class="search-field">
                                        <span>Buscar</span>
                                        <input type="search" placeholder="Buscar por nome, e-mail ou CPF" data-search-input="customers">
                                    </label>
                                    <label class="compact-field">
                                        <span>Por p&aacute;gina</span>
                                        <select data-page-size="customers">
                                            <option value="6" selected>6</option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                        </select>
                                    </label>
                                    <button class="button button-ghost toolbar-action" type="button" data-refresh-entity="customers">Atualizar</button>
                                </div>
                                <div class="collection-grid" id="customers-list"></div>
                                <div class="pagination" id="customers-pagination"></div>
                            </div>
                        </div>
                    </section>

                    <section class="panel" data-panel="sales">
                        <div class="panel-heading">
                            <div><p class="eyebrow">Transa&ccedil;&otilde;es</p><h2>Vendas</h2></div>
                            <p>Registre vendas com hist&oacute;rico de pre&ccedil;o preservado e totais por transa&ccedil;&atilde;o.</p>
                        </div>
                        <div class="panel-mini-summary" data-panel-summary="sales">
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Vendas vis&iacute;veis</span>
                                <strong class="panel-mini-value" data-panel-value="sales-primary">0 vendas</strong>
                                <small class="panel-mini-note" data-panel-note="sales-primary">P&aacute;gina 1 de 1</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">Recorte aplicado</span>
                                <strong class="panel-mini-value" data-panel-value="sales-secondary">Todos os status</strong>
                                <small class="panel-mini-note" data-panel-note="sales-secondary">Todas as datas</small>
                            </article>
                            <article class="panel-mini-card">
                                <span class="panel-mini-label">L&iacute;quido vis&iacute;vel</span>
                                <strong class="panel-mini-value" data-panel-value="sales-tertiary">R$ 0,00</strong>
                                <small class="panel-mini-note" data-panel-note="sales-tertiary">0 conclu&iacute;das vis&iacute;veis</small>
                            </article>
                        </div>
                        <div class="panel-layout">
                            <form class="entity-form" id="sale-form">
                                <div class="form-heading"><h3>Editor de venda</h3><p>Crie e atualize registros de venda.</p></div>
                                <input type="hidden" name="id">
                                <label><span>Produto</span><select name="product_id" id="sale-product-select" required></select></label>
                                <label><span>Cliente</span><select name="customer_id" id="sale-customer-select" required></select></label>
                                <label><span>Data da venda</span><input name="sold_at" type="datetime-local" required></label>
                                <div class="field-row">
                                    <label><span>Quantidade</span><input name="quantity" type="number" min="1" step="1" required></label>
                                    <label><span>Desconto</span><input name="discount" type="number" min="0" step="0.01" required></label>
                                </div>
                                <label>
                                    <span>Status</span>
                                    <select name="status" required>
                                        <option value="pending">Pendente</option>
                                        <option value="completed">Conclu&iacute;da</option>
                                        <option value="cancelled">Cancelada</option>
                                    </select>
                                </label>
                                <div class="form-errors" id="sale-errors" role="alert"></div>
                                <div class="form-actions">
                                    <button class="button button-primary" type="submit">Salvar venda</button>
                                    <button class="button button-ghost" type="button" data-reset-form="sale-form">Limpar</button>
                                </div>
                            </form>

                            <div class="entity-browser">
                                <div class="browser-toolbar">
                                    <label class="compact-field">
                                        <span>Filtrar status</span>
                                        <select data-status-filter="sales">
                                            <option value="">Todos</option>
                                            <option value="pending">Pendente</option>
                                            <option value="completed">Conclu&iacute;da</option>
                                            <option value="cancelled">Cancelada</option>
                                        </select>
                                    </label>
                                    <label class="compact-field">
                                        <span>De</span>
                                        <input type="date" data-date-filter="sold_from">
                                    </label>
                                    <label class="compact-field">
                                        <span>At&eacute;</span>
                                        <input type="date" data-date-filter="sold_to">
                                    </label>
                                    <label class="compact-field">
                                        <span>Por p&aacute;gina</span>
                                        <select data-page-size="sales">
                                            <option value="6" selected>6</option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                        </select>
                                    </label>
                                    <button class="button button-ghost toolbar-action" type="button" data-refresh-entity="sales">Atualizar</button>
                                </div>
                                <div class="collection-grid" id="sales-list"></div>
                                <div class="pagination" id="sales-pagination"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

    <div class="modal-shell" id="confirm-modal" aria-hidden="true">
        <div class="modal-backdrop" data-modal-close></div>
        <section class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
            <p class="eyebrow">Confirmar a&ccedil;&atilde;o</p>
            <h2 id="confirm-modal-title">Excluir registro?</h2>
            <p class="confirm-modal-text" id="confirm-modal-description">
                Esta a&ccedil;&atilde;o remove permanentemente o registro selecionado.
            </p>
            <div class="confirm-modal-actions">
                <button class="button button-ghost" type="button" id="confirm-modal-cancel">Cancelar</button>
                <button class="button button-primary confirm-modal-danger" type="button" id="confirm-modal-confirm">
                    Excluir
                </button>
            </div>
        </section>
    </div>

    <script>
        window.CreditallDashboard = { apiBase: '/api/v1', docsUrl: '/docs' };
    </script>
    <script src="{{ asset('assets/dashboard.js') }}" defer></script>
</body>
</html>
