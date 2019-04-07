<?php return '<?php

namespace Modules\\Blog\\SuperTests\\Feature;

use Tests\\TestCase;
use Illuminate\\Foundation\\Testing\\WithFaker;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;

class EloquentPostRepositoryTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get(\'/\');

        $response->assertStatus(200);
    }
}
';
