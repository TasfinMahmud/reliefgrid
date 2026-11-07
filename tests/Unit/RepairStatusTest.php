<?php

namespace Tests\Unit;

use App\Enums\RepairStatus;
use App\Services\ShelterRepairService;
use PHPUnit\Framework\TestCase;

/** Feature 12 – pure unit tests (no database, no framework boot). */
class RepairStatusTest extends TestCase
{
    public function test_normal_path_is_allowed_step_by_step(): void
    {
        $this->assertTrue(RepairStatus::Submitted->canTransitionTo(RepairStatus::Assessed));
        $this->assertTrue(RepairStatus::Assessed->canTransitionTo(RepairStatus::Approved));
        $this->assertTrue(RepairStatus::Approved->canTransitionTo(RepairStatus::MaterialsIssued));
        $this->assertTrue(RepairStatus::MaterialsIssued->canTransitionTo(RepairStatus::Completed));
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $this->assertFalse(RepairStatus::Submitted->canTransitionTo(RepairStatus::Approved));
        $this->assertFalse(RepairStatus::Submitted->canTransitionTo(RepairStatus::Completed));
        $this->assertFalse(RepairStatus::Assessed->canTransitionTo(RepairStatus::MaterialsIssued));
    }

    public function test_rejection_is_only_possible_before_approval_and_is_terminal(): void
    {
        $this->assertTrue(RepairStatus::Submitted->canTransitionTo(RepairStatus::Rejected));
        $this->assertTrue(RepairStatus::Assessed->canTransitionTo(RepairStatus::Rejected));
        $this->assertFalse(RepairStatus::Approved->canTransitionTo(RepairStatus::Rejected));
        $this->assertSame([], RepairStatus::Rejected->allowedNext());
        $this->assertTrue(RepairStatus::Rejected->isTerminal());
        $this->assertTrue(RepairStatus::Completed->isTerminal());
    }

    public function test_dms_to_decimal_handles_hemispheres_and_rationals(): void
    {
        $service = new ShelterRepairService();

        $this->assertEqualsWithDelta(21.2105, $service->dmsToDecimal(['21/1', '12/1', '378/10'], 'N'), 0.00001);
        $this->assertEqualsWithDelta(-33.5, $service->dmsToDecimal(['33/1', '30/1', '0/1'], 'S'), 0.00001);
        $this->assertNull($service->dmsToDecimal(['21/1', '12/1'], 'N'));
        $this->assertNull($service->dmsToDecimal(['21/1', 'x', '3/1'], 'N'));
    }

    public function test_coordinate_validation(): void
    {
        $service = new ShelterRepairService();

        $this->assertTrue($service->validCoordinates(21.2, 92.1));
        $this->assertFalse($service->validCoordinates(91, 0));
        $this->assertFalse($service->validCoordinates(0, 181));
        $this->assertFalse($service->validCoordinates('abc', 10));
    }
}
