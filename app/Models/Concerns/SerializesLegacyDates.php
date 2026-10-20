<?php

namespace App\Models\Concerns;

use DateTimeInterface;

/**
 * Laravel secara bawaan mengubah kolom tanggal menjadi ISO-8601 UTC
 * ("2026-09-28T15:31:07.000000Z") saat model di-JSON-kan. Frontend lama
 * (api.js -> bp2tlFormatDate) mengharapkan format MySQL apa adanya
 * ("2026-09-28 22:31:07", waktu lokal), sama seperti yang dulu dikirim
 * PHP native. Trait ini menjaga format itu supaya JS tidak perlu diubah.
 */
trait SerializesLegacyDates
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
