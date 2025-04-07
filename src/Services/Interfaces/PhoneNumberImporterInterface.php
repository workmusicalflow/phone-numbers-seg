<?php

namespace App\Services\Interfaces;

use App\Models\PhoneNumber;

/**
 * Interface for phone number importer
 */
interface PhoneNumberImporterInterface
{
    /**
     * Import a phone number into the system
     * 
     * @param string $number Normalized phone number
     * @param array $fields Additional fields (civility, firstName, name, company, sector, notes)
     * @param bool $segment Whether to segment the number immediately
     * @return PhoneNumber|null The imported phone number or null if it already exists
     */
    public function importPhoneNumber(string $number, array $fields = [], bool $segment = true): ?PhoneNumber;

    /**
     * Import multiple phone numbers into the system
     * 
     * @param array $batch Array of phone numbers with additional fields
     * @param bool $segment Whether to segment the numbers immediately
     * @return array Import results
     */
    public function importBatch(array $batch, bool $segment = true): array;
}
