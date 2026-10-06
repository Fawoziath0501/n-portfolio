<?php

namespace Tests\Unit;

use App\Support\Portfolio;
use PHPUnit\Framework\TestCase;

class PortfolioTest extends TestCase
{
    public function test_tx_picks_language_with_fallbacks(): void
    {
        $this->assertSame('Bonjour', Portfolio::tx(['fr' => 'Bonjour', 'en' => 'Hello']));
        $this->assertSame('Hello', Portfolio::tx(['fr' => 'Bonjour', 'en' => 'Hello'], 'en'));
        $this->assertSame('Bonjour', Portfolio::tx(['fr' => 'Bonjour', 'en' => ''], 'en'));
        $this->assertSame('Hello', Portfolio::tx(['fr' => '', 'en' => 'Hello']));
        $this->assertSame('texte', Portfolio::tx('texte'));
        $this->assertSame('', Portfolio::tx(null));
    }
}
