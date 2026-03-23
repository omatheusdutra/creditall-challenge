# 🚀 Creditall Sales API

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker%20Compose-ready-2496ED?logo=docker&logoColor=white)
![Eloquent](https://img.shields.io/badge/Eloquent-ORM-FF2D20?logo=laravel&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-11-366488?logo=php&logoColor=white)
![Playwright](https://img.shields.io/badge/Playwright-E2E-2EAD33?logo=playwright&logoColor=white)
![OpenAPI](https://img.shields.io/badge/OpenAPI-3.1-6BA539?logo=openapiinitiative&logoColor=white)
![Swagger UI](https://img.shields.io/badge/Swagger-UI-85EA2D?logo=swagger&logoColor=black)
![Admin UI](https://img.shields.io/badge/Admin%20UI-Blade%20%2B%20Vanilla%20JS-11203B)
![Upload](https://img.shields.io/badge/Image%20Upload-enabled-C85F35)
![API](https://img.shields.io/badge/API-v1-0A7B83)

API REST em Laravel 11 para gerenciamento de **produtos**, **clientes** e **vendas**, com foco em legibilidade, regras de negócio explícitas, integridade relacional, testes automatizados e operação simples via Docker Compose.

Além da API, o projeto inclui um **frontend administrativo leve** consumindo a própria API, com CRUD visual, filtros, upload de imagem, modal de exclusão, summaries operacionais e smoke coverage via Playwright.

## ✨ Destaques

- CRUD completo de `products`, `customers` e `sales`
- API versionada em `/api/v1`
- Upload opcional de imagem para produto
- Form Requests, API Resources e Service Layer pragmática
- OpenAPI 3.1 + Swagger UI estático
- Collection Postman
- Admin UI em `Blade + JavaScript/CSS puros`
- Testes de unidade, feature, contrato do dashboard e E2E

## 🧱 Stack

### Backend

- PHP 8.3+
- Laravel 11
- Eloquent ORM
- MySQL 8.4

### Frontend administrativo

- Blade
- JavaScript vanilla
- CSS puro

### Qualidade

- PHPUnit
- Playwright
- Laravel Pint

### Documentação

- OpenAPI 3.1
- Swagger UI estático
- Postman Collection

### Infra

- Docker Compose

## 🧭 Contexto do repositório

Itens presentes no repositório, mas **não centrais** para a execução principal desta entrega:

- `tailwind.config.js` e `vite.config.js`
  - vêm do ecossistema padrão do Laravel, mas o admin atual **não depende** de Tailwind nem de build Vite para funcionar
- `laravel/sail`
  - está em `require-dev`, mas o fluxo documentado usa **Docker Compose**, não Sail
- `laravel/pail`
  - permanece como ferramenta auxiliar de desenvolvimento, não como parte do fluxo principal

Em outras palavras: o caminho recomendado para rodar e avaliar o projeto hoje é **Docker Compose + Laravel + Blade + JS/CSS puros**.

## 🏗️ Arquitetura

Fluxo principal:

```text
Route -> FormRequest -> Controller -> Service -> Model -> Resource
```

Princípios adotados:

- controllers finos, sem regra de negócio
- validação de entrada isolada em Form Requests
- regras de negócio concentradas em Services
- serialização isolada em API Resources
- cálculo monetário crítico isolado em `SaleAmountCalculator`
- upload isolado em `ProductImageService`
- tratamento de exceções centralizado em `bootstrap/app.php`

### Estrutura de pastas

```text
app/
  Data/
  Enums/
  Exceptions/
  Http/
    Controllers/Api/V1/
    Controllers/Concerns/
    Requests/
      Customers/
      Products/
      Sales/
    Resources/
  Models/
  Providers/
  Rules/
  Services/
  Support/
database/
  factories/
  migrations/
  seeders/
docker/
  app/
  mysql/init/
docs/
  postman/
  qa/
public/
  assets/
  docs/
resources/
  views/
routes/
  api.php
  web.php
tests/
  E2E/
  Feature/
  Unit/
```

## 🗃️ Modelagem de dados

### `products`

- `id`
- `name`
- `description`
- `price`
- `image_path` nullable
- `created_at`
- `updated_at`

Índices:

- índice em `name`
- índice em `price`

### `customers`

- `id`
- `name`
- `email` unique
- `cpf` unique, armazenado normalizado com 11 dígitos e mascarado nas responses
- `created_at`
- `updated_at`

Índices:

- índice em `name`

### `sales`

- `id`
- `product_id` FK
- `customer_id` FK
- `sold_at`
- `quantity`
- `discount`
- `status`
- `unit_price`
- `gross_amount`
- `final_amount`
- `created_at`
- `updated_at`

Índices:

- índice em `sold_at`
- índice em `status`
- índice composto em `customer_id, sold_at`
- índice composto em `product_id, sold_at`
- constraints para quantidade, preço, desconto e consistência do valor final

Justificativa para campos derivados em `sales`:

- `unit_price` preserva o preço praticado no momento da venda
- `gross_amount` e `final_amount` simplificam consultas e relatórios
- o histórico financeiro da venda deixa de depender de mutações posteriores do produto

## 📏 Regras de negócio

### Clientes

- nome obrigatório
- email obrigatório, válido e único
- CPF obrigatório, válido estruturalmente e único
- CPF armazenado sem máscara

### Produtos

- nome obrigatório
- descrição obrigatória
- preço obrigatório e maior que zero
- imagem opcional

### Vendas

- produto obrigatório e existente
- cliente obrigatório e existente
- data da venda obrigatória
- quantidade obrigatória e maior que zero
- desconto não pode ser negativo
- desconto não pode tornar o valor final menor que zero
- `status` aceitos: `pending`, `completed`, `cancelled`
- ao atualizar uma venda sem trocar o produto, o `unit_price` histórico é preservado
- ao trocar o produto da venda, o `unit_price` é recalculado com o preço atual do novo produto
- exclusão de produto ou cliente referenciado por venda retorna `409 Conflict`
- exclusão de venda é física para manter o CRUD simples do desafio

## 🔌 API

Prefixo base:

- `/api/v1`

### Produtos

- `GET /api/v1/products`
- `GET /api/v1/products/{id}`
- `POST /api/v1/products`
- `PUT /api/v1/products/{id}`
- `DELETE /api/v1/products/{id}`

Filtros:

- `search`
- `min_price`
- `max_price`
- `sort=name|price|created_at`
- `direction=asc|desc`
- `per_page`

### Clientes

- `GET /api/v1/customers`
- `GET /api/v1/customers/{id}`
- `POST /api/v1/customers`
- `PUT /api/v1/customers/{id}`
- `DELETE /api/v1/customers/{id}`

Filtros:

- `search`
- `sort=name|email|created_at`
- `direction=asc|desc`
- `per_page`

### Vendas

- `GET /api/v1/sales`
- `GET /api/v1/sales/{id}`
- `POST /api/v1/sales`
- `PUT /api/v1/sales/{id}`
- `DELETE /api/v1/sales/{id}`

Filtros:

- `status`
- `product_id`
- `customer_id`
- `sold_from` (`Y-m-d`, inclusivo)
- `sold_to` (`Y-m-d`, inclusivo até `23:59:59`)
- `sort=sold_at|created_at|final_amount|status`
- `direction=asc|desc`
- `per_page`

## 🖥️ Frontend administrativo

O projeto inclui uma interface administrativa em `http://localhost:8000/` com:

- CRUD visual para `products`, `customers` e `sales`
- modal customizado para exclusão
- máscara de CPF na digitação
- skeleton loading nas listagens
- summaries operacionais por painel
- filtros, busca e paginação
- upload de imagem de produto
- atalhos rápidos a partir do hero

Stack do frontend atual:

- Blade
- JavaScript vanilla
- CSS puro

## 🚀 Como rodar com Docker

### 1. Preparar ambiente

```bash
cp .env.example .env
docker compose build app
docker compose up -d mysql
docker compose run --rm app composer install --no-interaction --prefer-dist
docker compose run --rm app php artisan key:generate
docker compose up -d app
```

Observação:

- o serviço `app` executa `php artisan migrate --seed --force` no bootstrap
- em volume novo, o banco sobe já populado com a massa inicial
- para resetar manualmente o estado do banco, use:

```bash
docker compose run --rm app php artisan migrate:fresh --seed --force
```

### 2. URLs úteis

- Admin: `http://localhost:8000/`
- Swagger UI: `http://localhost:8000/docs/`
- API base: `http://localhost:8000/api/v1`
- MySQL: `localhost:33060`

## 💻 Como rodar sem Docker

> Fluxo opcional. O caminho recomendado para avaliação continua sendo Docker Compose.

Pré-requisitos:

- PHP 8.3+
- Composer 2+
- MySQL 8+

```bash
cp .env.local.example .env
cp .env.testing.example .env.testing
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Bancos esperados no MySQL local:

- `creditall`
- `creditall_testing`

## 🧪 Testes

### PHPUnit

```bash
docker compose run --rm app php artisan test
```

Executar toda a bateria de testes com um único comando:

```powershell
.\scripts\test-all.ps1
```

Alternativa em Unix-like:

```bash
./scripts/test-all.sh
```

Atalhos úteis:

```bash
docker compose run --rm app php artisan test --filter=SaleApiTest
docker compose run --rm app php artisan test --testsuite=Unit
docker compose run --rm app php artisan test --parallel
```

Otimizações aplicadas para reduzir tempo de execução:

- `schema:dump` do MySQL para evitar rebuild completo do schema a cada suíte
- `LazilyRefreshDatabase` nas suítes pesadas de API para migrar uma vez e isolar os testes via transação
- bootstrap condicional para permitir `--parallel` no Docker sem comando especial de credenciais

Observação pragmática:

- `--parallel` agora funciona, mas o melhor tempo depende da máquina, carga do Docker e custo de criação dos bancos temporários
- para TDD e revisão rápida, o maior ganho prático ainda costuma vir de `--filter` e `--testsuite`

Última validação registrada nesta entrega:

- `38 tests`
- `181 assertions`

Cobertura principal:

- CRUD de cliente, produto e venda
- email duplicado
- CPF duplicado e inválido
- preço inválido
- quantidade inválida
- desconto inválido
- upload de imagem
- cálculo monetário crítico
- contrato estrutural do dashboard

### Playwright E2E

Execução com Docker:

```bash
docker compose up -d app e2e
docker compose exec e2e npm ci
docker compose exec e2e npm run e2e:docker
```

Execução local:

```bash
npm install
npm run e2e:install
npm run e2e
```

Cobertura E2E atual:

- create, update e delete de `customer`
- create, update e delete de `product`
- create, update e delete de `sale`
- validação de modal de exclusão e toasts no admin

Observação:

- o primeiro run do serviço `e2e` pode demorar mais por causa do pull da imagem oficial do Playwright e da instalação dos browsers

## 📚 Documentação

- Admin: `http://localhost:8000/`
- Swagger UI: `http://localhost:8000/docs/`
- OpenAPI YAML: `public/docs/openapi.yaml`
- Postman Collection: `docs/postman/Creditall Challenge.postman_collection.json`
- Smoke checklist do dashboard: `docs/qa/dashboard-smoke-checklist.md`
- Guia E2E Playwright: `docs/qa/playwright-e2e.md`

## 📦 Exemplos rápidos

### Criar cliente

```http
POST /api/v1/customers
Content-Type: application/json

{
  "name": "Ana Martins",
  "email": "ana.martins@example.com",
  "cpf": "52998224725"
}
```

### Criar produto

```http
POST /api/v1/products
Content-Type: application/json

{
  "name": "Mechanical Keyboard",
  "description": "Hot-swappable 75% keyboard",
  "price": "499.90"
}
```

### Criar venda

```http
POST /api/v1/sales
Content-Type: application/json

{
  "product_id": 1,
  "customer_id": 1,
  "sold_at": "2026-03-20 10:30:00",
  "quantity": 2,
  "discount": "5.00",
  "status": "pending"
}
```

## 🔒 Segurança

- validação em todos os endpoints com Form Requests
- mass assignment controlado com `fillable`
- `exists`, `unique` e FK no banco para defesa em profundidade
- `CHECK constraints` para blindar quantidade, preço e consistência financeira
- respostas de erro padronizadas
- sem stack trace exposto por resposta da API
- upload com validação de tipo, extensão e limite de 2 MB
- disco `public` organizado por pasta `products/`
- CORS configurável por `CORS_ALLOWED_ORIGINS`
- rate limiting por IP via `API_RATE_LIMIT`

## ⚡ Performance

- paginação em listagens
- eager loading em `sales` para evitar N+1
- `select` explícito nas consultas principais
- índices em campos de busca, ordenação e relacionamento
- valores monetários calculados uma única vez na camada de serviço
- snapshot financeiro em `sales` para evitar recomputação histórica
- suíte PHP otimizada com `schema:dump` e `LazilyRefreshDatabase`
- suporte a `php artisan test --parallel` no Docker sem exigir export manual de credenciais

## 🧠 Decisões arquiteturais

- Arquitetura em camadas pragmática, sem abstrações artificiais.
- `Controller` recebe HTTP, `Request` valida, `Service` decide, `Model` persiste e `Resource` serializa.
- Regras críticas foram separadas por coesão: cálculo financeiro e upload não ficam no controller.
- Integridade relacional é reforçada em duas camadas: serviço e banco.
- A API foi versionada desde o início em `/api/v1`.

## 🧩 Aplicação prática de SOLID

- **Single Responsibility**
  - controllers apenas orquestram request e response
  - requests validam
  - services executam regras
  - resources serializam
- **Open/Closed**
  - novos filtros, novos status de venda ou nova estratégia de storage podem ser adicionados sem reescrever o fluxo inteiro
- **Liskov**
  - classes estendem contratos padrão do Laravel mantendo comportamento esperado pela framework
- **Interface Segregation**
  - interfaces genéricas artificiais foram evitadas
- **Dependency Inversion**
  - `SaleService` depende de `SaleAmountCalculator`
  - `ProductService` depende de `ProductImageService`

## 🛠️ Padrões de projeto utilizados

- **Service Layer**
  - encapsula casos de uso e protege controllers contra lógica de negócio
- **Factory Pattern**
  - factories de `Product`, `Customer` e `Sale` simplificam testes e seeders
- **Data Object**
  - `SaleAmountsData` deixa explícito o resultado do cálculo financeiro
- **Exception Handling centralizado**
  - respostas de erro uniformes e previsíveis em toda a API

Padrões deliberadamente não usados:

- **Repository Pattern**
  - Eloquent já entrega o valor necessário para este escopo
- **Strategy Pattern**
  - o comportamento atual de status ainda não exige essa abstração

## ⚙️ Preocupações com performance

- `with()` em `sales` evita N+1 na serialização de produto e cliente
- campos derivados em `sales` reduzem custo de leitura em relatórios e histórico
- índices foram pensados para listagens por período, status e relacionamento
- os controllers não fazem montagem de query complexa nem lógica repetida
- o admin usa atualização local otimista antes do refetch de backfill
- a suíte de testes evita recriação completa do banco em cada classe pesada
- o caminho paralelo ficou suportado, mas não foi promovido a padrão sem benchmark real por ambiente

## 🛡️ Preocupações com segurança

- toda entrada externa passa por validação explícita
- CPF é normalizado antes de persistir
- upload aceita apenas imagens válidas e limita tamanho
- a API retorna mensagens controladas para exceções conhecidas
- variáveis de ambiente separam configuração sensível do código
- dados sensíveis não são expostos integralmente nas responses do CRM

## ✅ Checklist de code review

- CRUD completo para `products`, `customers` e `sales`
- regras de negócio cobertas por validação defensiva e testes
- controllers finos e legíveis
- cálculo financeiro centralizado e testado
- integridade relacional garantida com FK, constraints e regras de exclusão
- paginação, filtros e ordenação implementados
- upload de imagem validado e serializado
- OpenAPI, Swagger UI e Postman entregues
- frontend administrativo funcional e integrado à API
- Docker Compose funcional com PHP 8.3, MySQL 8 e serviço E2E

## ⚖️ Trade-offs assumidos

- documentação OpenAPI é estática, não gerada por annotations
- autenticação e autorização não foram implementadas porque não faziam parte do escopo
- a exclusão de venda é física para manter aderência ao CRUD simples do desafio
- `tailwind.config.js`, `vite.config.js`, `laravel/sail` e `laravel/pail` permanecem no repositório como tooling do ecossistema Laravel, mas não são parte do runtime principal do admin

## 🔭 Melhorias futuras

- autenticação com Sanctum
- policies e escopos por perfil
- soft delete e auditoria para entidades transacionais
- observabilidade com métricas e tracing
- cache seletivo para consultas de leitura
- processamento assíncrono de imagens
- integração com S3 por troca de disk
- CI com pipeline de lint, testes PHP e Playwright
- análise estática com Larastan / PHPStan
- testes de contrato OpenAPI automatizados
