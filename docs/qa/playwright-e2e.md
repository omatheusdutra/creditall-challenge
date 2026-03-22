# Playwright E2E

Suíte E2E do dashboard administrativo usando Playwright.

## Arquivos principais

- `playwright.config.ts`
- `tests/E2E/dashboard-admin.spec.ts`
- `docs/qa/dashboard-smoke-checklist.md`

## Cobertura atual

- create, update e delete de customer pelo frontend
- create, update e delete de product pelo frontend
- create, update e delete de sale pelo frontend
- validação do modal de exclusão e dos toasts de feedback

## Execução com Docker

Pré-requisitos:

- `app` e `mysql` rodando
- dependências Node instaladas no volume da suíte E2E

Comandos:

```bash
docker compose up -d app
docker compose run --rm e2e npm ci
docker compose run --rm e2e npm run e2e:docker
```

## Execução local

```bash
npm install
npm run e2e:install
npm run e2e
```

## Observações

- o serviço `e2e` foi adicionado ao `docker-compose.yml` com a imagem oficial do Playwright
- o `node_modules` do fluxo Docker fica em volume nomeado, sem depender da máquina local
- a suíte usa `PLAYWRIGHT_BASE_URL` e por padrão aponta para `http://127.0.0.1:8000`
- no fluxo Docker, a base URL é `http://app:8000`
- relatórios HTML ficam em `playwright-report/`
- artefatos de falha ficam em `test-results/`
