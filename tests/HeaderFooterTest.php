<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Tests;

use Davidannebicque\TwigToWordBundle\Html\HtmlDocumentInterpreter;
use PhpOffice\PhpWord\IOFactory;
use PHPUnit\Framework\TestCase;

final class HeaderFooterTest extends TestCase
{
    public function testFirstPageAndTotalPageFieldsAreSerialized(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<p>Cover</p>', [
            'document' => ['differentFirstPage' => true],
            'header' => ['text' => 'Cover header', 'first_page' => true],
            'footer' => ['text' => 'Page', 'page_numbers' => true, 'total_pages' => true],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'word_first_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \\ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $document = $zip->getFromName('word/document.xml');
                $footer = $zip->getFromName('word/footer1.xml');
                self::assertIsString($document);
                self::assertIsString($footer);
                self::assertStringContainsString('NUMPAGES', $footer);
                self::assertStringContainsString('PAGE', $footer);
                self::assertStringContainsString('first', $document);
                self::assertStringContainsString('titlePg', $document);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }

    public function testHeaderFooterAndPageFieldAreWrittenToDocx(): void
    {
        $word = (new HtmlDocumentInterpreter())->fromHtml('<p>Body</p>', [
            'header' => ['text' => 'Institution', 'alignment' => 'right'],
            'footer' => ['text' => 'Page', 'alignment' => 'center', 'page_numbers' => true],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'word_hf_');
        self::assertNotFalse($path);
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path) === true);
            try {
                $header = $zip->getFromName('word/header1.xml');
                $footer = $zip->getFromName('word/footer1.xml');
                self::assertIsString($header);
                self::assertIsString($footer);
                self::assertStringContainsString('Institution', $header);
                self::assertStringContainsString('Page', $footer);
                self::assertStringContainsString('PAGE', $footer);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }
}
