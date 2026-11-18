<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_employee_login_page_is_available(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk sebagai Pegawai');
    }

    public function test_employee_master_data_requires_employee_login(): void
    {
        $this->get('/employee/pelanggan')
            ->assertRedirect('/login');
    }

    public function test_owner_dashboard_redirects_to_owner_login(): void
    {
        $this->get('/owner/dashboard')
            ->assertRedirect('/owner/login');
    }

    public function test_owner_reports_require_owner_login(): void
    {
        $this->get('/owner/reports/sales')
            ->assertRedirect('/owner/login');

        $this->get('/owner/reports/purchases')
            ->assertRedirect('/owner/login');
    }
}
