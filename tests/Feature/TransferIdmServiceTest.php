<?php

namespace Tests\Feature;

use App\Models\GradeCompany;
use App\Models\IdmDetail;
use App\Models\IdmManagement;
use App\Models\IdmTransfer;
use App\Models\IdmTransferDetail;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\ParentGradeCompany;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Idm\TransferIdmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferIdmServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransferIdmService $service;
    protected User $user;
    protected Supplier $supplier;
    protected GradeCompany $gradeCompany;
    protected Location $location;
    protected IdmManagement $management;
    protected IdmDetail $detail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TransferIdmService::class);
        $this->user    = User::factory()->create();
        $this->actingAs($this->user);

        $parent              = ParentGradeCompany::create(['name' => 'IDM', 'stock' => 0]);
        $this->supplier      = Supplier::create(['name' => 'Hengki']);
        $this->gradeCompany  = GradeCompany::create(['name' => 'IDM A W2', 'parent_grade_company_id' => $parent->id]);
        $this->location      = Location::create(['name' => 'Gudang Utama']);

        $this->management = IdmManagement::create([
            'supplier_id'      => $this->supplier->id,
            'grade_company_id' => $this->gradeCompany->id,
            'initial_weight'   => 1000.00,
            'shrinkage'        => 0,
            'grading_date'     => now()->toDateString(),
        ]);

        $this->detail = IdmDetail::create([
            'idm_management_id' => $this->management->id,
            'grade_idm_name'    => 'Perutan',
            'weight'            => 1000.00,
        ]);
    }

    // Buat transfer + IDM_TRANSFER_OUT transaction (simulasi transfer sudah dibuat)
    private function makeTransfer(float $weight = 300.0): array
    {
        $transfer = IdmTransfer::create([
            'transfer_date' => now()->toDateString(),
            'transfer_code' => 'test-' . uniqid(),
            'sum_goods'     => 1,
        ]);

        IdmTransferDetail::create([
            'idm_transfer_id' => $transfer->id,
            'idm_detail_id'   => $this->detail->id,
            'item_name'       => 'Perutan',
            'grade_idm_name'  => 'Perutan',
            'weight'          => $weight,
        ]);

        $outTx = InventoryTransaction::create([
            'transaction_date'      => now(),
            'grade_company_id'      => $this->gradeCompany->id,
            'location_id'           => $this->location->id,
            'supplier_id'           => $this->supplier->id,
            'quantity_change_grams' => -$weight,
            'transaction_type'      => 'IDM_TRANSFER_OUT',
            'reference_id'          => $transfer->id,
            'created_by'            => $this->user->id,
        ]);

        return [$transfer, $outTx];
    }

    // deleteTransfer harus buat IDM_TRANSFER_REVERT dengan supplier_id
    public function test_delete_transfer_creates_revert_transaction_with_supplier_id()
    {
        [$transfer] = $this->makeTransfer(300.0);

        $this->service->deleteTransfer($transfer->id);

        $revert = InventoryTransaction::where('transaction_type', 'IDM_TRANSFER_REVERT')
            ->where('reference_id', $transfer->id)
            ->first();

        $this->assertNotNull($revert, 'IDM_TRANSFER_REVERT harus ada setelah hapus transfer');
        $this->assertEquals($this->supplier->id, $revert->supplier_id, 'supplier_id harus tersalin ke REVERT');
        $this->assertEquals($this->gradeCompany->id, $revert->grade_company_id);
        $this->assertEquals($this->location->id, $revert->location_id);
        $this->assertEquals(300.0, $revert->quantity_change_grams, 'quantity REVERT harus positif');
    }

    // IDM_TRANSFER_OUT dan transfer harus ter-soft-delete
    public function test_delete_transfer_soft_deletes_out_transaction_and_transfer()
    {
        [$transfer, $outTx] = $this->makeTransfer(300.0);

        $this->service->deleteTransfer($transfer->id);

        $this->assertSoftDeleted('inventory_transactions', ['id' => $outTx->id]);
        $this->assertSoftDeleted('idm_transfers', ['id' => $transfer->id]);
    }

    // Net stok setelah hapus harus 0 (OUT + REVERT saling hapus)
    public function test_delete_transfer_net_stock_is_zero()
    {
        [$transfer] = $this->makeTransfer(500.0);

        $this->service->deleteTransfer($transfer->id);

        $net = InventoryTransaction::withTrashed()
            ->where('reference_id', $transfer->id)
            ->whereIn('transaction_type', ['IDM_TRANSFER_OUT', 'IDM_TRANSFER_REVERT'])
            ->sum('quantity_change_grams');

        $this->assertEquals(0.0, (float) $net, 'OUT (-500) + REVERT (+500) = 0');
    }

    // generateTransferCode tidak boleh pakai ulang kode yang sudah di-soft-delete
    public function test_generate_transfer_code_skips_soft_deleted_codes()
    {
        $date     = '2026-06-27';
        $baseCode = 'jun-2726';

        $transfer = IdmTransfer::create([
            'transfer_date' => $date,
            'transfer_code' => $baseCode,
            'sum_goods'     => 0,
        ]);
        $transfer->delete();

        $newCode = $this->service->generateTransferCode($date);

        $this->assertNotEquals($baseCode, $newCode, 'Kode yang sudah dihapus tidak boleh dipakai ulang');
        $this->assertStringStartsWith($baseCode . '-', $newCode, 'Kode baru harus pakai counter suffix');
    }

    // Item yang sudah ditransfer sebagian harus tetap muncul di getAvailableIdmDetails
    public function test_available_details_includes_partially_transferred_item()
    {
        $this->makeTransfer(300.0);

        $available = $this->service->getAvailableIdmDetails();

        $this->assertCount(1, $available, 'Item dengan sisa berat harus muncul');
        $this->assertEquals(700.0, (float) $available->first()->remaining_weight, 'Sisa harus 700g');
    }

    // Item yang sudah ditransfer penuh harus hilang dari getAvailableIdmDetails
    public function test_available_details_excludes_fully_transferred_item()
    {
        $this->makeTransfer(1000.0);

        $available = $this->service->getAvailableIdmDetails();

        $this->assertCount(0, $available, 'Item yang sudah habis tidak boleh muncul');
    }

    // Supplier dengan item partial transfer harus muncul di halaman filter create
    public function test_create_page_filter_shows_supplier_with_partially_transferred_item()
    {
        $this->makeTransfer(300.0); // partial — sisa 700g

        $response = $this->get(route('barang.keluar.transfer-idm.create'));

        $response->assertStatus(200);

        $suppliers = $response->viewData('suppliers');
        $this->assertTrue(
            $suppliers->contains('id', $this->supplier->id),
            'Supplier dengan sisa berat harus tetap muncul di dropdown filter'
        );
    }

    // Supplier yang item-nya habis tidak boleh muncul di filter create
    public function test_create_page_filter_hides_supplier_with_fully_transferred_item()
    {
        $this->makeTransfer(1000.0); // full — tidak ada sisa

        $response = $this->get(route('barang.keluar.transfer-idm.create'));

        $response->assertStatus(200);

        $suppliers = $response->viewData('suppliers');
        $this->assertFalse(
            $suppliers->contains('id', $this->supplier->id),
            'Supplier tanpa sisa berat tidak boleh muncul di dropdown filter'
        );
    }
}
