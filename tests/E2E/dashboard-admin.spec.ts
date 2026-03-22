import { expect, test, type Page } from '@playwright/test';

test.describe('Dashboard admin E2E', () => {
    test('manages a customer through create, update and delete', async ({ page }) => {
        const customer = buildCustomerFixture('crud');
        const updatedCustomer = buildCustomerFixture('edited');

        await gotoDashboard(page);
        await openPanel(page, 'customers');
        await createCustomer(page, customer);

        let card = findEntityCard(page, 'customers', customer.name);
        await expect(card).toContainText(customer.email);

        await editCustomer(page, customer.name, updatedCustomer);

        card = findEntityCard(page, 'customers', updatedCustomer.name);
        await expect(card).toContainText(updatedCustomer.email);

        await deleteEntity(page, 'customers', updatedCustomer.name, 'cliente');
        await expect(findEntityCard(page, 'customers', updatedCustomer.name)).toHaveCount(0);
    });

    test('manages a product through create, update and delete', async ({ page }) => {
        const product = buildProductFixture('crud');
        const updatedProduct = buildProductFixture('edited');

        await gotoDashboard(page);
        await openPanel(page, 'products');
        await createProduct(page, product);

        let card = findEntityCard(page, 'products', product.name);
        await expect(card).toContainText(formatCurrency(product.price));

        await editProduct(page, product.name, updatedProduct);

        card = findEntityCard(page, 'products', updatedProduct.name);
        await expect(card).toContainText(formatCurrency(updatedProduct.price));

        await deleteEntity(page, 'products', updatedProduct.name, 'produto');
        await expect(findEntityCard(page, 'products', updatedProduct.name)).toHaveCount(0);
    });

    test('manages a sale through create, update and delete', async ({ page }) => {
        const customer = buildCustomerFixture('sale');
        const product = buildProductFixture('sale');

        await gotoDashboard(page);
        await openPanel(page, 'customers');
        await createCustomer(page, customer);

        await openPanel(page, 'products');
        await createProduct(page, product);

        await openPanel(page, 'sales');
        await waitForEntityCollection(page, 'sales');
        await waitForSaleReference(page, '#sale-product-select', product.name);
        await waitForSaleReference(page, '#sale-customer-select', customer.name);

        await page.selectOption('#sale-product-select', await findOptionValue(page, '#sale-product-select', product.name));
        await page.selectOption('#sale-customer-select', await findOptionValue(page, '#sale-customer-select', customer.name));
        await page.locator('#sale-form [name="sold_at"]').fill('2026-03-22T15:30');
        await page.locator('#sale-form [name="quantity"]').fill('2');
        await page.locator('#sale-form [name="discount"]').fill('100.00');
        await page.locator('#sale-form [name="status"]').selectOption('completed');
        await page.locator('#sale-form button[type="submit"]').click();

        await waitForFormReady(page, '#sale-form');

        let card = findEntityCard(page, 'sales', product.name);
        await expect(card).toContainText(customer.name);
        await expect(card).toContainText('Concluida');

        await card.getByRole('button', { name: 'Editar' }).click();
        await page.locator('#sale-form [name="quantity"]').fill('3');
        await page.locator('#sale-form [name="discount"]').fill('150.00');
        await page.locator('#sale-form [name="status"]').selectOption('pending');
        await page.locator('#sale-form button[type="submit"]').click();

        await waitForFormReady(page, '#sale-form');
        card = findEntityCard(page, 'sales', product.name);
        await expect(card).toContainText('Pendente');

        await deleteEntity(page, 'sales', product.name, 'venda');
        await expect(findEntityCard(page, 'sales', product.name)).toHaveCount(0);

        await openPanel(page, 'customers');
        await deleteEntity(page, 'customers', customer.name, 'cliente');

        await openPanel(page, 'products');
        await deleteEntity(page, 'products', product.name, 'produto');
    });
});

async function gotoDashboard(page: Page): Promise<void> {
    await page.goto('/');
    await expect(page.locator('[data-panel-target="products"]')).toBeVisible();
    await waitForEntityCollection(page, 'products');
}

async function openPanel(page: Page, panel: 'products' | 'customers' | 'sales'): Promise<void> {
    await page.locator(`[data-panel-target="${panel}"]`).click();
    await expect(page.locator(`[data-panel="${panel}"]`)).toHaveClass(/is-active/);
    await waitForEntityCollection(page, panel);
}

async function waitForEntityCollection(page: Page, entity: 'products' | 'customers' | 'sales'): Promise<void> {
    await page.waitForFunction((targetEntity) => {
        const container = document.getElementById(`${targetEntity}-list`);

        return Boolean(container) && container.querySelector('.entity-card-skeleton') === null;
    }, entity);
}

function findEntityCard(page: Page, entity: 'products' | 'customers' | 'sales', marker: string) {
    return page.locator(`#${entity}-list .entity-card`, { hasText: marker }).first();
}

async function createCustomer(page: Page, customer: CustomerFixture): Promise<void> {
    await page.locator('#customer-form [name="name"]').fill(customer.name);
    await page.locator('#customer-form [name="email"]').fill(customer.email);
    await page.locator('#customer-form [name="cpf"]').fill(customer.cpf);
    await page.locator('#customer-form button[type="submit"]').click();
    await waitForFormReady(page, '#customer-form');
    await expect(findEntityCard(page, 'customers', customer.name)).toBeVisible();
}

async function editCustomer(page: Page, currentName: string, nextCustomer: CustomerFixture): Promise<void> {
    const card = findEntityCard(page, 'customers', currentName);

    await card.getByRole('button', { name: 'Editar' }).click();
    await page.locator('#customer-form [name="name"]').fill(nextCustomer.name);
    await page.locator('#customer-form [name="email"]').fill(nextCustomer.email);
    await page.locator('#customer-form [name="cpf"]').fill(nextCustomer.cpf);
    await page.locator('#customer-form button[type="submit"]').click();

    await waitForFormReady(page, '#customer-form');
    await expect(findEntityCard(page, 'customers', nextCustomer.name)).toBeVisible();
}

async function createProduct(page: Page, product: ProductFixture): Promise<void> {
    await page.locator('#product-form [name="name"]').fill(product.name);
    await page.locator('#product-form [name="description"]').fill(product.description);
    await page.locator('#product-form [name="price"]').fill(product.price);
    await page.locator('#product-form button[type="submit"]').click();
    await waitForFormReady(page, '#product-form');
    await expect(findEntityCard(page, 'products', product.name)).toBeVisible();
}

async function editProduct(page: Page, currentName: string, nextProduct: ProductFixture): Promise<void> {
    const card = findEntityCard(page, 'products', currentName);

    await card.getByRole('button', { name: 'Editar' }).click();
    await page.locator('#product-form [name="name"]').fill(nextProduct.name);
    await page.locator('#product-form [name="description"]').fill(nextProduct.description);
    await page.locator('#product-form [name="price"]').fill(nextProduct.price);
    await page.locator('#product-form button[type="submit"]').click();

    await waitForFormReady(page, '#product-form');
    await expect(findEntityCard(page, 'products', nextProduct.name)).toBeVisible();
}

async function deleteEntity(page: Page, entity: 'products' | 'customers' | 'sales', marker: string, label: 'produto' | 'cliente' | 'venda'): Promise<void> {
    const card = findEntityCard(page, entity, marker);

    await card.getByRole('button', { name: 'Excluir' }).click();
    await expect(page.locator('#confirm-modal')).toBeVisible();
    await expect(page.locator('#confirm-modal-title')).toContainText(new RegExp(`Excluir ${label}\\?`, 'i'));
    await page.locator('#confirm-modal-confirm').click();
    await expect(page.locator('#confirm-modal')).toBeHidden();
}

async function waitForSaleReference(page: Page, selector: string, marker: string): Promise<void> {
    await expect.poll(async () => {
        const options = await page.locator(`${selector} option`).allTextContents();

        return options.some((option) => option.includes(marker));
    }).toBeTruthy();
}

async function findOptionValue(page: Page, selector: string, marker: string): Promise<string> {
    const options = page.locator(`${selector} option`);
    const count = await options.count();

    for (let index = 0; index < count; index += 1) {
        const option = options.nth(index);
        const text = await option.textContent();

        if ((text ?? '').includes(marker)) {
            return (await option.getAttribute('value')) ?? '';
        }
    }

    throw new Error(`Option not found for marker: ${marker}`);
}

async function waitForFormReady(page: Page, selector: string): Promise<void> {
    await expect(page.locator(selector)).not.toHaveAttribute('aria-busy', 'true', { timeout: 60_000 });
}

function buildCustomerFixture(label: string): CustomerFixture {
    const suffix = uniqueSuffix(label);

    return {
        name: `Cliente ${suffix}`,
        email: `cliente.${suffix}@example.test`,
        cpf: generateValidCpf(suffix),
    };
}

function buildProductFixture(label: string): ProductFixture {
    const suffix = uniqueSuffix(label);

    return {
        name: `Produto ${suffix}`,
        description: `Descricao do produto ${suffix} para validacao do dashboard.`,
        price: label === 'edited' ? '4399.90' : '4599.90',
    };
}

function uniqueSuffix(label: string): string {
    return `${label}-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

function generateValidCpf(seed: string): string {
    const digits = seed.replaceAll(/\D+/g, '').padEnd(9, '7').slice(0, 9).split('').map(Number);
    const firstVerifier = calculateCpfVerifier(digits);
    const secondVerifier = calculateCpfVerifier([...digits, firstVerifier]);

    return [...digits, firstVerifier, secondVerifier].join('');
}

function calculateCpfVerifier(digits: number[]): number {
    const factor = digits.length + 1;
    const total = digits.reduce((carry, digit, index) => carry + (digit * (factor - index)), 0);
    const remainder = total % 11;

    return remainder < 2 ? 0 : 11 - remainder;
}

function formatCurrency(value: string): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
}

type CustomerFixture = {
    cpf: string;
    email: string;
    name: string;
};

type ProductFixture = {
    description: string;
    name: string;
    price: string;
};
