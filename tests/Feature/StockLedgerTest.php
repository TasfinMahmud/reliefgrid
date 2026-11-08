<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Feature 5 – Warehouse and stock management.
 */
class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    private User $coordinator;

    private Warehouse $main;

    private Warehouse $hub;

    private Item $rice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = User::factory()->warehouseOfficer()->create();
        $this->coordinator = User::factory()->coordinator()->create();
        $this->main = Warehouse::factory()->create(['code' => 'WH-MAIN']);
        $this->hub = Warehouse::factory()->create(['code' => 'WH-HUB']);
        $this->rice = Item::factory()->create(['name' => 'Rice', 'unit' => 'kg']);
    }

    /** @return array<string,mixed> */
    private function receipt(array $overrides = []): array
    {
        return array_merge([
            'item_id' => $this->rice->id, 'batch_number' => 'RICE-001', 'quantity' => 1000,
            'expiry_date' => now()->addMonths(6)->toDateString(), 'received_at' => now()->toDateString(), 'supplier' => 'WFP',
        ], $overrides);
    }

    public function test_receiving_stock_creates_batch_and_receipt_ledger_entry(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt())
            ->assertRedirect("/warehouses/{$this->main->id}")->assertSessionHasNoErrors();

        $batch = StockBatch::first();
        $this->assertSame('RICE-001', $batch->batch_number);
        $this->assertEquals(1000, $batch->received_quantity);

        $movement = StockMovement::first();
        $this->assertSame(MovementType::Receipt, $movement->type);
        $this->assertEquals(1000, $movement->quantity);
        $this->assertEquals(1000, app(StockService::class)->batchBalance($batch));
    }

    public function test_receipt_validation_rejects_expired_future_dated_and_duplicate_batches(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['expiry_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('expiry_date');
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['received_at' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('received_at');
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['quantity' => 0]))
            ->assertSessionHasErrors('quantity');

        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt())->assertSessionHasNoErrors();
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt())->assertSessionHasErrors('batch_number');
    }

    public function test_only_warehouse_officers_and_admins_can_receive_stock(): void
    {
        $this->actingAs($this->coordinator)->post("/warehouses/{$this->main->id}/receive", $this->receipt())->assertForbidden();
        $this->actingAs(User::factory()->fieldOfficer()->create())->get("/warehouses/{$this->main->id}/receive")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post("/warehouses/{$this->main->id}/receive", $this->receipt())->assertSessionHasNoErrors();
    }

    public function test_issue_cannot_exceed_batch_balance(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['quantity' => 100]));
        $batch = StockBatch::first();

        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/issue", [
            'stock_batch_id' => $batch->id, 'quantity' => 100.01, 'note' => 'DP-1',
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/issue", [
            'stock_batch_id' => $batch->id, 'quantity' => 100, 'note' => 'DP-1',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(0, app(StockService::class)->batchBalance($batch));
        $this->assertSame(2, StockMovement::count());
        $this->assertEquals(-100, StockMovement::where('type', 'issue')->first()->quantity);
    }

    public function test_expired_batches_cannot_be_issued(): void
    {
        $batch = StockBatch::create([
            'warehouse_id' => $this->main->id, 'item_id' => $this->rice->id, 'batch_number' => 'OLD', 'expiry_date' => now()->subDay(),
            'received_quantity' => 50, 'received_at' => now()->subMonths(7), 'received_by' => $this->officer->id,
        ]);
        StockMovement::create(['warehouse_id' => $this->main->id, 'item_id' => $this->rice->id, 'stock_batch_id' => $batch->id, 'type' => 'receipt', 'quantity' => 50, 'created_by' => $this->officer->id, 'created_at' => now()]);

        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/issue", [
            'stock_batch_id' => $batch->id, 'quantity' => 10, 'note' => 'x',
        ])->assertSessionHasErrors('stock_batch_id');
    }

    public function test_ledger_entries_are_append_only(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt());
        $movement = StockMovement::first();

        try {
            $movement->update(['quantity' => 5]);
            $this->fail('Ledger update should have thrown.');
        } catch (LogicException) {
            $this->assertEquals(1000, $movement->fresh()->quantity);
        }

        try {
            $movement->delete();
            $this->fail('Ledger delete should have thrown.');
        } catch (LogicException) {
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_transfer_requires_approval_and_writes_entries_at_both_warehouses(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt());
        $batch = StockBatch::first();

        $this->actingAs($this->officer)->post('/transfers', [
            'stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->hub->id, 'quantity' => 400, 'reason' => 'Pre-position',
        ])->assertRedirect('/transfers')->assertSessionHasNoErrors();

        $transfer = StockTransfer::first();
        $this->assertSame(TransferStatus::Pending, $transfer->status);
        $this->assertSame(1, StockMovement::count(), 'No ledger entry until approval');

        // The requester cannot approve their own transfer; a field officer cannot approve at all.
        $this->actingAs($this->officer)->post("/transfers/{$transfer->id}/approve")->assertForbidden();
        $this->actingAs(User::factory()->fieldOfficer()->create())->post("/transfers/{$transfer->id}/approve")->assertForbidden();

        $this->actingAs($this->coordinator)->post("/transfers/{$transfer->id}/approve", ['review_note' => 'ok'])->assertRedirect()->assertSessionHasNoErrors();

        $transfer->refresh();
        $service = app(StockService::class);
        $this->assertSame(TransferStatus::Approved, $transfer->status);
        $this->assertSame(3, StockMovement::count());
        $this->assertEquals(600, $service->batchBalance($batch));

        $destination = StockBatch::where('warehouse_id', $this->hub->id)->first();
        $this->assertNotNull($destination);
        $this->assertSame('RICE-001', $destination->batch_number);
        $this->assertSame($batch->id, $destination->origin_batch_id);
        $this->assertEquals($batch->expiry_date, $destination->expiry_date);
        $this->assertEquals(400, $service->batchBalance($destination));
        $this->assertSame($destination->id, $transfer->destination_batch_id);

        $this->assertEquals(-400, $transfer->movements()->where('type', 'transfer_out')->first()->quantity);
        $this->assertEquals(400, $transfer->movements()->where('type', 'transfer_in')->first()->quantity);
    }

    public function test_transfer_quantity_cannot_exceed_available_stock_minus_pending_transfers(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['quantity' => 500]));
        $batch = StockBatch::first();

        $this->actingAs($this->officer)->post('/transfers', ['stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->hub->id, 'quantity' => 300])->assertSessionHasNoErrors();
        $this->actingAs($this->officer)->post('/transfers', ['stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->hub->id, 'quantity' => 300])->assertSessionHasErrors('quantity');
        $this->actingAs($this->officer)->post('/transfers', ['stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->main->id, 'quantity' => 10])->assertSessionHasErrors('to_warehouse_id');
    }

    public function test_rejected_transfer_leaves_ledger_untouched(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt());
        $batch = StockBatch::first();
        $this->actingAs($this->officer)->post('/transfers', ['stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->hub->id, 'quantity' => 100]);
        $transfer = StockTransfer::first();

        $this->actingAs($this->coordinator)->post("/transfers/{$transfer->id}/reject", ['review_note' => 'Not needed'])->assertRedirect();

        $this->assertSame(TransferStatus::Rejected, $transfer->fresh()->status);
        $this->assertSame(1, StockMovement::count());
        $this->actingAs($this->coordinator)->post("/transfers/{$transfer->id}/approve")->assertForbidden();
    }

    public function test_balances_are_derived_and_reconcile_exactly(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['quantity' => 1000]));
        $batch = StockBatch::first();
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/issue", ['stock_batch_id' => $batch->id, 'quantity' => 250.5, 'note' => 'DP-1']);
        $this->actingAs($this->officer)->post('/transfers', ['stock_batch_id' => $batch->id, 'to_warehouse_id' => $this->hub->id, 'quantity' => 100]);
        $this->actingAs($this->coordinator)->post('/transfers/'.StockTransfer::first()->id.'/approve');

        $service = app(StockService::class);
        $this->assertEquals(649.5, $service->batchBalance($batch));
        $this->assertEquals(649.5, $service->warehouseStock($this->main)->first()['balance']);

        $rows = $service->reconcile($this->main);
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['ok']);
        $this->assertEquals(1000, $rows[0]['receipts']);
        $this->assertEquals(250.5, $rows[0]['issues']);
        $this->assertEquals(100, $rows[0]['transfers_out']);
        $this->assertEquals(649.5, $rows[0]['balance']);

        $this->actingAs($this->coordinator)->get("/warehouses/{$this->main->id}/reconcile")->assertOk()->assertSee('reconcile exactly');
    }

    public function test_expiry_report_lists_batches_expiring_soon(): void
    {
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['batch_number' => 'SOON', 'expiry_date' => now()->addDays(10)->toDateString()]));
        $this->actingAs($this->officer)->post("/warehouses/{$this->main->id}/receive", $this->receipt(['batch_number' => 'LATER', 'expiry_date' => now()->addYear()->toDateString()]));

        $rows = app(StockService::class)->expiryAlerts(30);
        $this->assertCount(1, $rows);
        $this->assertSame('SOON', $rows[0]['batch']->batch_number);

        $this->actingAs($this->officer)->get('/warehouses')->assertOk(); // consume the flash from the last receipt
        $this->actingAs($this->officer)->get('/stock/expiry')->assertOk()->assertSee('SOON')->assertDontSee('LATER');
        $this->actingAs($this->officer)->get('/stock/ledger')->assertOk();
    }
}
