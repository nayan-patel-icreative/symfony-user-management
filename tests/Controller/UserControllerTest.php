<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Users');
        self::assertSelectorExists('table');
    }

    public function testIndexWithSearch(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user?search=test');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="search"]');
        self::assertInputValueSame('input[name="search"]', 'test');
    }

    public function testIndexWithDateFilters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user?date_from=2024-01-01&date_to=2024-12-31');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="date_from"]');
        self::assertInputValueSame('input[name="date_from"]', '2024-01-01');
        self::assertSelectorExists('input[name="date_to"]');
        self::assertInputValueSame('input[name="date_to"]', '2024-12-31');
    }

    public function testNewUserPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Create User');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="user[name]"]');
        self::assertSelectorExists('input[name="user[email]"]');
        self::assertSelectorExists('input[name="user[age]"]');
        self::assertSelectorExists('input[name="user[avatarFile]"]');
    }

    public function testCreateUserWithInvalidData(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/new');

        $client->submitForm('form', [
            'user[name]' => '',
            'user[email]' => 'invalid-email',
            'user[age]' => -5,
        ]);

        // Should stay on form page with validation errors
        self::assertResponseIsSuccessful();
    }

    public function testShowUserWithInvalidId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/99999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditUserWithInvalidId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/99999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPagination(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user');

        self::assertResponseIsSuccessful();
        // Check if pagination elements exist (even if no data)
        self::assertSelectorExists('.pagination-info');
    }

    public function testSearchFunctionality(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user?search=John');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="search"]');
        self::assertInputValueSame('input[name="search"]', 'John');
    }

    public function testDateFilterFunctionality(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user?date_from=2024-06-01&date_to=2024-12-31');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="date_from"]');
        self::assertInputValueSame('input[name="date_from"]', '2024-06-01');
        self::assertSelectorExists('input[name="date_to"]');
        self::assertInputValueSame('input[name="date_to"]', '2024-12-31');
    }
}
