<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private function loadSpec(): array
    {
        return json_decode(
            file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    public function test_swagger_json_includes_protected_endpoints_and_security_schemes(): void
    {
        $spec = $this->loadSpec();

        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertArrayHasKey('X-Auth-Token', $spec['components']['securitySchemes']);
        $this->assertArrayHasKey('BearerAuth', $spec['components']['securitySchemes']);

        $requiredPaths = [
            '/notifications',
            '/notifications/{id}',
            '/notifications/test-email',
            '/analytics/stats',
            '/analytics/charts',
            '/analytics/export/pdf',
            '/analytics/export/excel',
            '/admin/users',
            '/admin/equipment',
            '/admin/rooms',
            '/tickets/search',
        ];

        foreach ($requiredPaths as $path) {
            $this->assertArrayHasKey($path, $spec['paths'], "Missing Swagger path: {$path}");
        }

        foreach ([
            '/notifications',
            '/notifications/{id}',
            '/notifications/test-email',
            '/analytics/stats',
            '/analytics/charts',
            '/analytics/export/pdf',
            '/analytics/export/excel',
            '/admin/users',
            '/admin/equipment',
            '/admin/rooms',
            '/tickets/search',
        ] as $path) {
            foreach ($spec['paths'][$path] as $method => $operation) {
                $this->assertArrayHasKey('security', $operation, "Missing security block for {$method} {$path}");
                $this->assertNotEmpty($operation['security'], "Empty security block for {$method} {$path}");
            }
        }

        $this->assertArrayHasKey('/admin/equipment/{id}', $spec['paths']);
        $this->assertArrayHasKey('/admin/rooms/{id}', $spec['paths']);
        $this->assertArrayHasKey('/admin/rooms/{id}/inactive', $spec['paths']);
    }


    public function test_swagger_ui_route_is_available(): void
    {
        $this->get('/api/documentation')->assertOk();
        $this->get('/docs/openapi')->assertRedirect('/api/documentation');
    }
}
