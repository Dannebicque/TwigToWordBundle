<?php
declare(strict_types=1);

namespace Davidannebicque\TwigToWordBundle\Word;

/**
 * PHPWord 1.3 omits w:styleId on named character styles in styles.xml.
 * Add the identifier required by w:rStyle references in document.xml.
 */
final class CharacterStyleDocxFixer
{
    /** @param array<string, string> $nextStyles */
    public function fix(string $path, array $nextStyles = []): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Cannot open generated DOCX for style correction.');
        }
        try {
            $xml = $zip->getFromName('word/styles.xml');
            if (!is_string($xml)) {
                throw new \RuntimeException('Generated DOCX is missing word/styles.xml.');
            }
            $dom = new \DOMDocument();
            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('Invalid Word styles XML.');
            }
            $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', $namespace);
            $changed = false;
            foreach ($xpath->query('//w:style[@w:type="character" and not(@w:styleId)]') as $style) {
                if (!$style instanceof \DOMElement) { continue; }
                $name = $xpath->query('./w:name', $style)->item(0);
                if (!$name instanceof \DOMElement) { continue; }
                $id = $name->getAttributeNS($namespace, 'val');
                if ($id === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $id)) {
                    continue;
                }
                $style->setAttributeNS($namespace, 'w:styleId', $id);
                $changed = true;
            }
            foreach ($nextStyles as $styleId => $nextId) {
                if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $styleId)
                    || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,99}$/D', $nextId)) {
                    throw new \InvalidArgumentException('Invalid Word style relation identifier.');
                }
                $matches = $xpath->query('//w:style[@w:type="paragraph" and @w:styleId="'.$styleId.'"]');
                $style = $matches->item(0);
                if (!$style instanceof \DOMElement) { continue; }
                $next = $xpath->query('./w:next', $style)->item(0);
                if (!$next instanceof \DOMElement) {
                    $next = $dom->createElementNS($namespace, 'w:next');
                    $style->appendChild($next);
                }
                $next->setAttributeNS($namespace, 'w:val', $nextId);
                $changed = true;
            }
            if ($changed) {
                $updated = $dom->saveXML();
                if (!is_string($updated) || !$zip->addFromString('word/styles.xml', $updated)) {
                    throw new \RuntimeException('Cannot update DOCX character styles.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
