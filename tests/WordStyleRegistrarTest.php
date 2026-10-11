<?php
declare(strict_types=1);
namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Word\WordStyleRegistrar;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\TestCase;

final class WordStyleRegistrarTest extends TestCase
{
    public function testRegistersNamedParagraphAndCharacterStyles(): void
    {
        $document = new PhpWord();
        (new WordStyleRegistrar())->register($document, [
            'ReportTitle' => [
                'paragraph' => ['alignment' => 'center'],
                'font' => ['name' => 'Arial', 'size' => 22, 'bold' => true, 'color' => '#17365D'],
            ],
        ]);
        self::assertNotNull($document->getStyle('ReportTitle'));
        self::assertNotNull($document->getStyle('ReportTitle_font'));
    }

    public function testRejectsInvalidStyleDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new WordStyleRegistrar())->register(new PhpWord(), ['broken' => ['font' => 'invalid']]);
    }
}
