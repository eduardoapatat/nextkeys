<?php

namespace App\Actions\Catalog;

use App\Enums\KeyImportRejectionReason;
use App\Enums\ProductKeyStatus;
use App\Models\Product;
use App\Models\ProductKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Imports the codes of a CSV file (one code per line, no header) as available
 * keys of a `digital_key` or `license` product. Valid codes are stored and
 * every other line is reported with its reason (docs/domain/catalog.md).
 */
final class ImportProductKeys
{
    public const int MAX_CODES = 5000;

    private const string CODE_FORMAT = '/^[A-Za-z0-9-]{5,100}$/';

    private const int INSERT_CHUNK = 1000;

    /**
     * @throws ValidationException
     */
    public function handle(Product $product, string $contents): KeyImportResult
    {
        if (! $product->type->isDeliveredByKey()) {
            throw ValidationException::withMessages([
                'product' => 'Solo los productos de tipo key digital o licencia tienen keys.',
            ]);
        }

        $lines = $this->codeLines($contents);

        if ($lines === []) {
            throw ValidationException::withMessages(['file' => 'El archivo no tiene códigos.']);
        }

        if (count($lines) > self::MAX_CODES) {
            throw ValidationException::withMessages([
                'file' => 'El archivo tiene '.count($lines).' códigos; el máximo es '.number_format(self::MAX_CODES, 0, ',', '.').' por archivo.',
            ]);
        }

        /** @var list<KeyImportRejection> $rejections */
        $rejections = [];
        /** @var array<string, array{line: int, code: string}> $candidates keyed by code hash */
        $candidates = [];

        foreach ($lines as $line => $code) {
            if (preg_match(self::CODE_FORMAT, $code) !== 1) {
                $rejections[] = new KeyImportRejection($line, KeyImportRejectionReason::InvalidFormat);

                continue;
            }

            $hash = ProductKey::hashCode($code);

            if (isset($candidates[$hash])) {
                $rejections[] = new KeyImportRejection($line, KeyImportRejectionReason::DuplicateInFile);

                continue;
            }

            $candidates[$hash] = ['line' => $line, 'code' => $code];
        }

        $insertedHashes = $this->insert($product, $candidates);

        foreach ($candidates as $hash => $candidate) {
            if (! isset($insertedHashes[$hash])) {
                $rejections[] = new KeyImportRejection($candidate['line'], KeyImportRejectionReason::AlreadyExists);
            }
        }

        usort($rejections, fn (KeyImportRejection $a, KeyImportRejection $b): int => $a->line <=> $b->line);

        return new KeyImportResult(count($insertedHashes), $rejections);
    }

    /**
     * The trimmed codes of the file keyed by their 1-based line number,
     * without blank lines or a first-line `code` header.
     *
     * @return array<int, string>
     */
    private function codeLines(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $codes = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $index => $rawLine) {
            $code = trim($rawLine);

            if ($code === '' || ($codes === [] && mb_strtolower($code) === 'code')) {
                continue;
            }

            $codes[$index + 1] = $code;
        }

        return $codes;
    }

    /**
     * Inserts the codes in one transaction and returns the hashes that were
     * stored. `ON CONFLICT DO NOTHING` skips a code that already exists, also
     * one committed by a concurrent import after the file was read, so the
     * unique `code_hash` never surfaces as a database error. Inserting a key
     * locks its product row (FOR KEY SHARE), which serializes the import with
     * DeleteProduct.
     *
     * @param  array<string, array{line: int, code: string}>  $candidates
     * @return array<string, true>
     */
    private function insert(Product $product, array $candidates): array
    {
        return DB::transaction(function () use ($product, $candidates): array {
            $inserted = [];
            $now = now();

            foreach (array_chunk($candidates, self::INSERT_CHUNK) as $chunk) {
                $bindings = [];

                foreach ($chunk as $candidate) {
                    $stored = ProductKey::storedCodeAttributes($candidate['code']);
                    array_push(
                        $bindings,
                        $product->id,
                        $product->type->value,
                        $stored['code'],
                        $stored['code_hash'],
                        ProductKeyStatus::Available->value,
                        $now,
                        $now,
                    );
                }

                $rows = implode(', ', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?)'));

                /** @var list<object{code_hash: string}> $returned */
                $returned = DB::select(
                    "insert into product_keys (product_id, product_type, code, code_hash, status, created_at, updated_at)
                     values {$rows}
                     on conflict (code_hash) do nothing
                     returning code_hash",
                    $bindings,
                );

                foreach ($returned as $row) {
                    $inserted[$row->code_hash] = true;
                }
            }

            return $inserted;
        });
    }
}
