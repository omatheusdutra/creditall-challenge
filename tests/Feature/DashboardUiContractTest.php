<?php

declare(strict_types=1);

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class DashboardUiContractTest extends TestCase
{
    public function test_dashboard_exposes_panels_forms_and_navigation_hooks(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $xpath = $this->createXPath($response->getContent());

        $this->assertXPathCount($xpath, "//*[@data-panel-target='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-target='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-target='sales']", 1);

        $this->assertXPathCount($xpath, "//*[@data-panel='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel='sales']", 1);

        $this->assertXPathCount($xpath, "//*[@id='product-form']", 1);
        $this->assertXPathCount($xpath, "//*[@id='customer-form']", 1);
        $this->assertXPathCount($xpath, "//*[@id='sale-form']", 1);

        $this->assertXPathCount($xpath, "//*[@id='products-list']", 1);
        $this->assertXPathCount($xpath, "//*[@id='customers-list']", 1);
        $this->assertXPathCount($xpath, "//*[@id='sales-list']", 1);

        $this->assertXPathCount($xpath, "//*[@data-panel-jump='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-jump='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-jump='sales']", 1);
    }

    public function test_dashboard_exposes_search_filter_summary_and_feedback_contracts(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee("window.CreditallDashboard = { apiBase: '/api/v1', docsUrl: '/docs' };", false);

        $xpath = $this->createXPath($response->getContent());

        $this->assertXPathCount($xpath, "//*[@data-search-input='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-search-input='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-status-filter='sales']", 1);
        $this->assertXPathCount($xpath, "//*[@data-date-filter='sold_from']", 1);
        $this->assertXPathCount($xpath, "//*[@data-date-filter='sold_to']", 1);

        $this->assertXPathCount($xpath, "//*[@data-page-size='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-page-size='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-page-size='sales']", 1);

        $this->assertXPathCount($xpath, "//*[@data-panel-summary='products']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-summary='customers']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-summary='sales']", 1);

        $this->assertXPathCount($xpath, "//*[@data-panel-value='products-primary']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-value='customers-primary']", 1);
        $this->assertXPathCount($xpath, "//*[@data-panel-value='sales-primary']", 1);

        $this->assertXPathCount($xpath, "//*[@id='toast-stack']", 1);
        $this->assertXPathCount($xpath, "//*[@id='confirm-modal']", 1);
        $this->assertXPathCount($xpath, "//*[@id='confirm-modal-confirm']", 1);
        $this->assertXPathCount($xpath, "//*[@id='confirm-modal-cancel']", 1);
    }

    private function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function assertXPathCount(DOMXPath $xpath, string $query, int $expected): void
    {
        $result = $xpath->query($query);

        $this->assertNotFalse($result, sprintf('Invalid XPath query: %s', $query));
        $this->assertSame($expected, $result->length, sprintf('Unexpected number of nodes for query: %s', $query));
    }
}
