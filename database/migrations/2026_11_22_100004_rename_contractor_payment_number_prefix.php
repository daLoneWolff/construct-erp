<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renamePrefix('TKP-', 'CTP-');
    }

    public function down(): void
    {
        $this->renamePrefix('CTP-', 'TKP-');
    }

    private function renamePrefix(string $from, string $to): void
    {
        DB::table('contractor_payments')
            ->select(['id', 'payment_no'])
            ->where('payment_no', 'like', $from.'%')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($from, $to): void {
                foreach ($payments as $payment) {
                    $renamedNumber = $to.substr($payment->payment_no, strlen($from));
                    $conflictExists = DB::table('contractor_payments')
                        ->where('payment_no', $renamedNumber)
                        ->where('id', '!=', $payment->id)
                        ->exists();

                    if ($conflictExists) {
                        throw new RuntimeException('Cannot rename contractor payment numbers because the target number already exists.');
                    }

                    DB::table('contractor_payments')
                        ->where('id', $payment->id)
                        ->update(['payment_no' => $renamedNumber]);
                }
            });
    }
};
