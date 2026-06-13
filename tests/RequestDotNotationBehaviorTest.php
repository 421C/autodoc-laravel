<?php declare(strict_types=1);

namespace AutoDoc\Laravel\Tests;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Characterization tests: documents how Laravel's typed Request helpers treat
 * dot-notation keys at runtime, so our analyzer fixtures can mirror it.
 */
class RequestDotNotationBehaviorTest extends TestCase
{
    private function request(): Request
    {
        return Request::create('/', 'POST', [
            'user' => [
                'name' => 'Ada',
                'email' => 'ada@example.com',
            ],
            'items' => [
                ['id' => 1],
                ['id' => 2],
            ],
        ]);
    }


    #[Test]
    public function single_key_input_reads_nested_value(): void
    {
        $value = $this->request()->input('user.name');

        $this->assertSame('Ada', $value);
    }


    #[Test]
    public function array_key_list_rebuilds_nested_structure_and_merges_siblings(): void
    {
        $value = $this->request()->array(['user.name', 'user.email']);

        $this->assertSame([
            'user' => [
                'name' => 'Ada',
                'email' => 'ada@example.com',
            ],
        ], $value);
    }


    #[Test]
    public function collect_key_list_rebuilds_nested_structure(): void
    {
        $value = $this->request()->collect(['user.name'])->all();

        $this->assertSame([
            'user' => [
                'name' => 'Ada',
            ],
        ], $value);
    }


    #[Test]
    public function array_key_list_with_wildcard_keeps_literal_star_segment(): void
    {
        $value = $this->request()->array(['items.*.id']);

        // Under the hood Request->array() reads via data_get (where `*` is a wildcard)
        // and writes via Arr::set (where `*` is just a literal key), so the wildcard
        // survives as a literal '*' segment in the result.
        $this->assertSame([
            'items' => [
                '*' => [
                    'id' => [1, 2],
                ],
            ],
        ], $value);
    }
}
