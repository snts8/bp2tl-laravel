<?php

namespace App\Models;

use App\Models\Concerns\SerializesLegacyDates;
use Illuminate\Database\Eloquent\Model;

/** Data latih chatbot (tabel `chatbot_faq`). */
class ChatbotFaq extends Model
{
    use SerializesLegacyDates;

    protected $table = 'chatbot_faq';

    protected $fillable = ['pertanyaan', 'jawaban', 'kata_kunci'];
}
