<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Service;

use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Exception\BarcodeNotFoundException;
use ksfraser\FrontAccounting\InventoryCount\Exception\LocationNotSetException;
use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;

/**
 * Imports scanned counts from uploaded text/CSV files.
 *
 * Legacy behaviour: the file is a series of codes, one per line; each line
 * may optionally carry a count quantity as a second CSV column (default 1).
 *
 * @BABOK Related: FR-IC-002-001
 *
 * @since 1.0.0
 */
class ImportService
{
    /** @var FaApiInterface */
    protected $fa;

    /**
     * Constructor.
     *
     * @param FaApiInterface $fa FrontAccounting API wrapper for code resolution.
     * @since 1.0.0
     */
    public function __construct(FaApiInterface $fa)
    {
        $this->fa = $fa;
    }

    /**
     * Parse file content into [code, qty] tuples.
     *
     * @param string $content Raw file content.
     * @return array<int, array{code:string, qty:float}>
     * @throws \InvalidArgumentException When no valid lines are found.
     *
     * @since 1.0.0
     */
    public function parse(string $content): array
    {
        $tuples = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($content)) as $raw) {
            $line = trim($raw);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $fields = str_getcsv($line);
            $code = trim((string) $fields[0]);
            if ($code === '') {
                continue;
            }
            $qty = isset($fields[1]) && is_numeric(trim($fields[1]))
                ? (float) trim($fields[1])
                : 1.0;
            $tuples[] = ['code' => $code, 'qty' => $qty];
        }
        return $tuples;
    }

    /**
     * Import parsed content into the cart, resolving codes to master SKUs.
     *
     * Unresolvable codes are skipped and returned so the UI can report them.
     *
     * @param string   $content  Raw file content.
     * @param CountCart $cart    Destination cart (mutated).
     * @param string   $location Location being counted.
     * @return array<int, string> Codes that could not be resolved.
     * @throws LocationNotSetException When location is empty.
     * @throws \InvalidArgumentException When nothing importable found.
     *
     * @BABOK Related: FR-IC-002-001
     * @since 1.0.0
     */
    public function import(string $content, CountCart $cart, string $location): array
    {
        if ($location === '') {
            throw new LocationNotSetException('Select a location before importing counts.');
        }

        $tuples = $this->parse($content);
        if (count($tuples) === 0) {
            throw new \InvalidArgumentException('No importable lines found in file.');
        }

        $unresolved = [];
        foreach ($tuples as $tuple) {
            $candidates = $this->fa->resolveBarcode($tuple['code']);
            if (count($candidates) === 0) {
                $unresolved[] = $tuple['code'];
                continue;
            }
            // First candidate wins when already in cart, else first master SKU.
            foreach ($candidates as $stockId) {
                if ($cart->has($stockId)) {
                    $cart->addScan($stockId, $tuple['qty']);
                    break;
                }
                $cart->addScan($stockId, $tuple['qty'], $tuple['code']);
                break;
            }
        }
        return $unresolved;
    }
}
