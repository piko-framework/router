<?php
use PHPUnit\Framework\TestCase;

use Piko\Router\RadixTrie;

class RadixTest extends TestCase
{
    private $radix;

    protected function setUp(): void
    {
        $this->radix = new RadixTrie();
    }

    public function testInsertSearch()
    {
        $words = [
            'toast',
            'test',
            'tester',
            'team',
            'tea',
        ];

        foreach ($words as $word) {
            $this->radix->insert($word, 'handler');
            $match = $this->radix->search($word);
            $this->assertTrue($match->found);
        }
    }
}

