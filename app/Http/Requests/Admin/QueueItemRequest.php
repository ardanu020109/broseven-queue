<?php

namespace App\Http\Requests\Admin;

use App\Enums\ResetPeriod;
use App\Models\QueueItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class QueueItemRequest extends FormRequest
{
    /** Panjang maksimum kolom queues.queue_number. */
    private const MAX_NUMBER_LENGTH = 40;

    /** Hanya token dikenal, huruf, angka, spasi, dan tanda - _ / . yang diizinkan. */
    private const FORMAT_REGEX = '/^(?:[A-Za-z0-9\-_\/. ]|\{(?:PREFIX|NUMBER|YYYY|YY|MM|DD)\})+$/';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** ID layanan yang sedang diubah (null saat menambah). */
    protected function ignoreId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'prefix' => Str::upper(trim((string) $this->input('prefix'))),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'prefix' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('queue_items', 'prefix')->whereNull('deleted_at')->ignore($this->ignoreId()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', Rule::in(QueueItem::ICON_CHOICES)],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo' => ['nullable', 'boolean'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'number_format' => ['required', 'string', 'max:50', 'regex:' . self::FORMAT_REGEX],
            'number_padding' => ['required', 'integer', 'between:1,8'],
            'reset_period' => ['required', Rule::in(ResetPeriod::values())],
            'hours_enabled' => ['nullable', 'boolean'],
            'hours_open' => ['nullable', 'required_if:hours_enabled,1', 'date_format:H:i'],
            'hours_close' => ['nullable', 'required_if:hours_enabled,1', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pemeriksaan lanjutan: format harus memuat {NUMBER}, dan hasil terpanjang
     * (urutan maksimum sesuai jumlah digit) tidak boleh melebihi kolom database.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['number_format', 'number_padding', 'prefix'])) {
                    return;
                }

                $format = (string) $this->input('number_format');

                if (! str_contains($format, '{NUMBER}')) {
                    $validator->errors()->add('number_format', 'Format nomor harus memuat token {NUMBER}.');

                    return;
                }

                $padding = (int) $this->input('number_padding');

                $probe = new QueueItem([
                    'prefix' => (string) $this->input('prefix'),
                    'number_format' => $format,
                    'number_padding' => $padding,
                ]);

                if (mb_strlen($probe->formatNumber((10 ** $padding) - 1, now())) > self::MAX_NUMBER_LENGTH) {
                    $validator->errors()->add(
                        'number_format',
                        'Hasil nomor antrean terlalu panjang (maksimal ' . self::MAX_NUMBER_LENGTH . ' karakter). Perpendek format, prefix, atau jumlah digit.'
                    );
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'prefix.regex' => 'Prefix hanya boleh berisi huruf kapital A-Z dan angka 0-9.',
            'prefix.unique' => 'Prefix sudah dipakai layanan lain.',
            'color.regex' => 'Warna harus berformat #RRGGBB.',
            'number_format.regex' => 'Format nomor hanya boleh berisi token {PREFIX}, {NUMBER}, {YYYY}, {YY}, {MM}, {DD}, huruf, angka, spasi, dan tanda - _ / .',
            'number_padding.between' => 'Jumlah digit harus antara 1 sampai 8.',
            'hours_open.required_if' => 'Jam buka wajib diisi jika jam operasional diaktifkan.',
            'hours_close.required_if' => 'Jam tutup wajib diisi jika jam operasional diaktifkan.',
            'hours_open.date_format' => 'Format jam buka harus HH:MM.',
            'hours_close.date_format' => 'Format jam tutup harus HH:MM.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'Nama layanan',
            'prefix' => 'Prefix',
            'description' => 'Deskripsi',
            'icon' => 'Ikon',
            'logo' => 'Logo',
            'color' => 'Warna',
            'number_format' => 'Format nomor',
            'number_padding' => 'Jumlah digit',
            'reset_period' => 'Periode reset',
            'hours_open' => 'Jam buka',
            'hours_close' => 'Jam tutup',
        ];
    }

    /** Data siap simpan ke QueueItem (tanpa logo; logo ditangani controller). */
    public function itemData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'prefix' => $data['prefix'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'],
            'number_format' => $data['number_format'],
            'number_padding' => (int) $data['number_padding'],
            'reset_period' => $data['reset_period'],
            'operating_hours' => [
                'enabled' => $this->boolean('hours_enabled'),
                'open' => $data['hours_open'] ?? '08:00',
                'close' => $data['hours_close'] ?? '17:00',
            ],
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
