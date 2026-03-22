# Dashboard Smoke Checklist

Roteiro manual para validar o frontend administrativo em `http://localhost:8000/` sem depender de stack extra de browser test.

## Pre-requisitos

- API e MySQL rodando via Docker Compose
- banco preparado com `docker compose run --rm app php artisan migrate:fresh --seed --force`
- dashboard acessível em `http://localhost:8000/`

## Fluxo 1: Customer

1. Abra a aba `Clientes`.
2. Cadastre:
   - Nome: `Maria Oliveira`
   - E-mail: `maria.oliveira@example.com`
   - CPF: `52998224725`
3. Valide:
   - o CPF é formatado como `529.982.247-25` durante a digitação
   - toast de sucesso aparece
   - card do cliente aparece na lista
   - mini resumo de clientes atualiza
4. Clique em `Editar` no card e altere:
   - Nome: `Maria Oliveira Santos`
   - E-mail: `maria.santos@example.com`
   - CPF: redigite `52998224725`
5. Valide persistência e atualização do card.
6. Clique em `Excluir`.
7. Valide:
   - modal estilizado abre
   - ação `Cancelar` fecha sem excluir
   - ação `Excluir` remove o registro e atualiza a lista

## Fluxo 2: Product

1. Abra a aba `Produtos`.
2. Cadastre:
   - Nome: `Notebook Dell Inspiron`
   - Descrição: `Notebook 16GB RAM, SSD 512GB`
   - Preço: `4599.90`
   - Imagem: opcional
3. Valide:
   - toast de sucesso
   - card do produto aparece
   - preview/indicador de imagem aparece corretamente
   - mini resumo de produtos atualiza
4. Edite o produto:
   - Preço: `4399.90`
   - Descrição: `Notebook 16GB RAM, SSD 512GB, tela 15.6`
5. Se houver imagem, valide substituição ou remoção via checkbox.
6. Exclua o produto e valide modal, toast e refresh local da lista.

## Fluxo 3: Sale

1. Garanta pelo menos 1 cliente e 1 produto cadastrados.
2. Abra a aba `Vendas`.
3. Cadastre:
   - Produto: produto criado
   - Cliente: cliente criado
   - Data da venda: data/hora atual
   - Quantidade: `2`
   - Desconto: `100.00`
   - Status: `Concluída`
4. Valide:
   - toast de sucesso
   - card da venda aparece
   - status badge correto
   - resumo financeiro superior atualiza
   - mini resumo de vendas atualiza
5. Edite a venda:
   - Quantidade: `3`
   - Desconto: `150.00`
   - Status: `Pendente`
6. Valide atualização do card e dos totais visíveis.
7. Exclua a venda e valide remoção com modal estilizado.

## Filtros e busca

1. Em `Produtos`, preencha o campo `Buscar`.
2. Valide:
   - skeleton loading no refresh inicial
   - empty state contextual quando a busca não retorna itens
   - botão `Limpar busca` restaura a listagem
3. Em `Clientes`, repita o fluxo com busca textual e por CPF.
4. Em `Vendas`, filtre por:
   - status
   - data inicial
   - data final
5. Valide mini resumo do painel e resumo financeiro superior com o recorte atual.

## Regressões críticas

- exclusão de cliente ou produto com venda vinculada deve falhar com feedback claro
- desconto não pode gerar valor final negativo
- quantidade da venda deve ser maior que zero
- upload inválido deve mostrar erro sem travar o formulário
- navegação pelos atalhos do hero deve abrir o painel correto

## Cobertura automatizada relacionada

- feature tests de API cobrem CRUD, validações e regras críticas
- `DashboardPageTest` cobre disponibilidade da home
- `DashboardUiContractTest` cobre contratos estruturais do dashboard, formulários, filtros, modal e hooks do frontend
