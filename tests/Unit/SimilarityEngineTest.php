<?php

namespace Tests\Unit;

use App\Services\Similarity\SimilarityEngine;
use PHPUnit\Framework\TestCase;

class SimilarityEngineTest extends TestCase
{
    private function entri(): array
    {
        return [
            ['code' => null, 'p' => 'Berapa biaya diklat ORU?', 'j' => 'Biaya ORU adalah A.', 'kk' => ''],
            ['code' => null, 'p' => 'Berapa biaya diklat ATT?', 'j' => 'Biaya ATT adalah B.', 'kk' => ''],
            ['code' => 'BP2TL-2026-AAAAAA', 'p' => 'Jam buka loket pelayanan', 'j' => 'Pukul 08.00-16.00.', 'kk' => ''],
        ];
    }

    public function test_pertanyaan_sama_persis_dijawab_langsung(): void
    {
        $h = (new SimilarityEngine())->cari('Jam buka loket pelayanan', $this->entri());

        $this->assertSame('match', $h['status']);
        $this->assertSame('Pukul 08.00-16.00.', $h['jawaban']);
        $this->assertSame('BP2TL-2026-AAAAAA', $h['code']);
    }

    public function test_kata_langka_membedakan_diklat_yang_mirip(): void
    {
        $h = (new SimilarityEngine())->cari('biaya diklat ORU', $this->entri());

        $this->assertSame('Biaya ORU adalah A.', $h['jawaban']);
    }

    public function test_pertanyaan_tak_terkait_tidak_ada_jawaban(): void
    {
        $h = (new SimilarityEngine())->cari('resep nasi goreng spesial', $this->entri());

        $this->assertSame('none', $h['status']);
    }

    public function test_teks_basa_basi_saja_tidak_menghasilkan_apa_apa(): void
    {
        $h = (new SimilarityEngine())->cari('halo permisi selamat pagi', $this->entri());

        $this->assertSame('none', $h['status']);
    }
}
