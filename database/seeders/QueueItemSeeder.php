<?php

namespace Database\Seeders;

use App\Models\QueueItem;
use Illuminate\Database\Seeder;

class QueueItemSeeder extends Seeder
{
    public function run(): void
    {
        $closedHours = ['enabled' => false, 'open' => '08:00', 'close' => '17:00'];

        $items = [
            ['Cireng Viral', 'CV', 5, '#F9C74F', 'bi-cup-hot', 'Antrean pembelian cireng viral.'],
            ['Layanan Pembayaran', 'LP', 3, '#F6EB61', 'bi-cash-coin', 'Pembayaran tagihan dan transaksi.'],
            ['Layanan Pengambilan Pesanan', 'PP', 3, '#A8A8A8', 'bi-box-seam', 'Pengambilan pesanan yang sudah dipesan.'],
            ['Customer Service', 'CS', 3, '#6D6D6D', 'bi-headset', 'Bantuan, keluhan, dan pertanyaan pelanggan.'],
            ['Konsultasi', 'KS', 3, '#F9C74F', 'bi-chat-dots', 'Konsultasi langsung dengan petugas.'],
            ['Layanan Informasi', 'LI', 3, '#A8A8A8', 'bi-info-circle', 'Informasi umum dan panduan layanan.'],
        ];

        foreach ($items as $index => [$name, $prefix, $padding, $color, $icon, $description]) {
            QueueItem::firstOrCreate(
                ['name' => $name],
                [
                    'prefix' => $prefix,
                    'description' => $description,
                    'icon' => $icon,
                    'color' => $color,
                    'number_format' => '{PREFIX}{NUMBER}',
                    'number_padding' => $padding,
                    'reset_period' => 'daily',
                    'operating_hours' => $closedHours,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
