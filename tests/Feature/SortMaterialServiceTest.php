<?php

namespace Tests\Feature;

use App\Models\GradeCompany;
use App\Models\ParentGradeCompany;
use App\Models\SortMaterial;
use App\Models\User;
use App\Services\SortMaterial\SortMaterialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortMaterialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SortMaterialService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SortMaterialService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_process_internal_grading_success()
    {
        // 1. Setup parent and child grades
        $mangkok = ParentGradeCompany::create(['name' => 'Mangkok', 'stock' => 100.00]);
        $lempeng = ParentGradeCompany::create(['name' => 'Lempeng', 'stock' => 0.00]);
        $idm = ParentGradeCompany::create(['name' => 'IDM', 'stock' => 0.00]);
        
        $idmA = GradeCompany::create([
            'name' => 'IDM A',
            'parent_grade_company_id' => $idm->id
        ]);

        // Add a MASUK sort material so that getNetSortStock has backing records
        SortMaterial::create([
            'type' => SortMaterial::TYPE_MASUK,
            'parent_grade_company_id' => $mangkok->id,
            'weight' => 100.00,
            'sort_date' => now(),
        ]);
        
        $this->service->recalculateAllParentStocks();

        $data = [
            'source_parent_grade_company_id' => $mangkok->id,
            'total_weight' => 70.00,
            'process_date' => now()->toDateString(),
            'targets' => [
                [
                    'parent_grade_company_id' => $lempeng->id,
                    'grade_company_id' => null,
                    'weight' => 60.00,
                ],
                [
                    'parent_grade_company_id' => $idm->id,
                    'grade_company_id' => $idmA->id,
                    'weight' => 10.00,
                ]
            ]
        ];

        $result = $this->service->processInternalGrading($data);
        $this->assertTrue($result);

        // Check stocks
        // Mangkok should be 100 - 70 = 30
        $this->assertEquals(30.00, $this->service->getNetSortStock($mangkok->id));
        // Lempeng should be 0 + 60 = 60
        $this->assertEquals(60.00, $this->service->getNetSortStock($lempeng->id));
        // IDM should be 0 (as the target was a child grade, parent stock cache shouldn't change)
        $this->assertEquals(0.00, $this->service->getNetSortStock($idm->id));
        // IDM A child stock should be 10.00
        $this->assertEquals(10.00, $this->service->getSortStockByGrade($idmA->id));

        // Check if records have grading_source_parent_id
        $targets = SortMaterial::where('type', SortMaterial::TYPE_MASUK)
            ->where('grading_source_parent_id', $mangkok->id)
            ->get();
        $this->assertCount(2, $targets);
    }

    public function test_delete_child_grade_does_not_fail_due_to_parent_stock_cache()
    {
        // Setup
        $mangkok = ParentGradeCompany::create(['name' => 'Mangkok', 'stock' => 100.00]);
        $idm = ParentGradeCompany::create(['name' => 'IDM', 'stock' => 0.00]);
        $idmA = GradeCompany::create([
            'name' => 'IDM A',
            'parent_grade_company_id' => $idm->id
        ]);

        SortMaterial::create([
            'type' => SortMaterial::TYPE_MASUK,
            'parent_grade_company_id' => $mangkok->id,
            'weight' => 100.00,
            'sort_date' => now(),
        ]);
        $this->service->recalculateAllParentStocks();

        // Process grading: 10g to IDM A
        $data = [
            'source_parent_grade_company_id' => $mangkok->id,
            'total_weight' => 10.00,
            'process_date' => now()->toDateString(),
            'targets' => [
                [
                    'parent_grade_company_id' => $idm->id,
                    'grade_company_id' => $idmA->id,
                    'weight' => 10.00,
                ]
            ]
        ];
        $this->service->processInternalGrading($data);

        // Get IDM A record
        $idmARecord = SortMaterial::where('grade_company_id', $idmA->id)->first();
        $this->assertNotNull($idmARecord);

        // Delete the record. This shouldn't throw an error because the check on parent IDM (0 stock) is skipped.
        $deleteResult = $this->service->delete($idmARecord->id);
        $this->assertTrue($deleteResult);

        // Verify IDM A stock is now 0
        $this->assertEquals(0.00, $this->service->getSortStockByGrade($idmA->id));
        // Verify Mangkok stock went back to 100
        $this->assertEquals(100.00, $this->service->getNetSortStock($mangkok->id));
    }

    public function test_delete_parent_target_restores_stock_to_source_parent()
    {
        // Setup
        $mangkok = ParentGradeCompany::create(['name' => 'Mangkok', 'stock' => 100.00]);
        $lempeng = ParentGradeCompany::create(['name' => 'Lempeng', 'stock' => 0.00]);

        SortMaterial::create([
            'type' => SortMaterial::TYPE_MASUK,
            'parent_grade_company_id' => $mangkok->id,
            'weight' => 100.00,
            'sort_date' => now(),
        ]);
        $this->service->recalculateAllParentStocks();

        // Process grading: 60g to Lempeng
        $data = [
            'source_parent_grade_company_id' => $mangkok->id,
            'total_weight' => 60.00,
            'process_date' => now()->toDateString(),
            'targets' => [
                [
                    'parent_grade_company_id' => $lempeng->id,
                    'grade_company_id' => null,
                    'weight' => 60.00,
                ]
            ]
        ];
        $this->service->processInternalGrading($data);

        // Get Lempeng record
        $lempengRecord = SortMaterial::where('parent_grade_company_id', $lempeng->id)
            ->where('type', SortMaterial::TYPE_MASUK)
            ->first();
        $this->assertNotNull($lempengRecord);

        // Delete the Lempeng record.
        // It should restore 60g to Mangkok and reduce/delete the KELUAR record.
        $deleteResult = $this->service->delete($lempengRecord->id);
        $this->assertTrue($deleteResult);

        // Mangkok stock should be 100 again (30 + 60)
        $this->assertEquals(100.00, $this->service->getNetSortStock($mangkok->id));
        // Lempeng stock should be 0 again
        $this->assertEquals(0.00, $this->service->getNetSortStock($lempeng->id));

        // The KELUAR record for Mangkok should be deleted (since the weight of the only target was 60, bringing newKeluarWeight to 0)
        $keluarRecordCount = SortMaterial::where('parent_grade_company_id', $mangkok->id)
            ->where('type', SortMaterial::TYPE_KELUAR)
            ->count();
        $this->assertEquals(0, $keluarRecordCount);
    }
}
