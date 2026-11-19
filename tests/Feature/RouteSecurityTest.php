<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Rute publik yang memang boleh menerima POST/PUT/PATCH/DELETE tanpa login. */
    private const PUBLIC_WRITE_ROUTES = ['login.store', 'queue.store'];

    /** Endpoint bawaan Reverb; tidak dipakai karena aplikasi hanya memakai channel publik. */
    private const FRAMEWORK_URIS = ['broadcasting/auth'];

    public function test_every_admin_route_has_auth_and_admin_middleware(): void
    {
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, $route->uri() . ' tidak memakai middleware auth');
            $this->assertContains('admin', $middleware, $route->uri() . ' tidak memakai middleware admin');
            $checked++;
        }

        $this->assertGreaterThan(30, $checked, 'Jumlah rute admin yang diperiksa terlalu sedikit.');
    }

    public function test_only_allowed_public_routes_can_change_data(): void
    {
        foreach (Route::getRoutes() as $route) {
            $writes = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

            if ($writes === [] || str_starts_with($route->uri(), 'admin')) {
                continue;
            }

            if (in_array($route->getName(), self::PUBLIC_WRITE_ROUTES, true) || in_array($route->uri(), self::FRAMEWORK_URIS, true)) {
                continue;
            }

            $this->assertContains(
                'auth',
                $route->gatherMiddleware(),
                'Rute pengubah data tanpa autentikasi: ' . implode('|', $writes) . ' ' . $route->uri()
            );
        }
    }

    public function test_guests_are_redirected_to_login_on_every_admin_route(): void
    {
        $login = route('login');

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin')) {
                continue;
            }

            $uri = '/' . preg_replace('/\{[^}]+\}/', '1', str_replace('{group}', 'general', $route->uri()));
            $method = in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];

            $response = $this->call($method, $uri);

            $this->assertSame(302, $response->getStatusCode(), "{$method} {$uri} tidak dialihkan untuk tamu");
            $this->assertSame($login, $response->headers->get('Location'), "{$method} {$uri} tidak mengarah ke login");
        }
    }

    public function test_queue_state_changes_are_not_reachable_without_authentication(): void
    {
        foreach (['queues/1/call', 'queues/1/skip', 'queues/1/restore', 'queues/1/complete'] as $path) {
            $this->post('/' . $path)->assertNotFound();
            $this->post('/admin/' . $path)->assertRedirect(route('login'));
        }
    }
}
