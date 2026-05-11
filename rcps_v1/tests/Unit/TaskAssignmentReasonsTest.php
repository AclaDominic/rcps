<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TaskComputationsService;
use Mockery;
use ReflectionMethod;

class TaskAssignmentReasonsTest extends TestCase
{
    /**
     * Test to check if the algorithm is capable of giving reasons why other users are not chosen.
     *
     * @return void
     */
    public function test_algorithm_is_capable_of_giving_reasons_why_others_not_chosen()
    {
        $service = new TaskComputationsService();

        // Create a generic mock for Developer A (Best fit: Senior, low workload, has skills)
        // We use a generic mock to avoid Eloquent's __set (setAttribute) issues in unit tests
        $developerA = Mockery::mock();
        $developerA->id = 1;
        $developerA->name = 'Developer A';
        $developerA->experience_years = 5;
        $developerA->availability = 1.0;
        $developerA->skills = ['PHP', 'Laravel'];
        $developerA->shouldReceive('hasRole')->with('senior')->andReturn(true);
        $developerA->shouldReceive('hasRole')->with('junior')->andReturn(false);

        // Create a generic mock for Developer B (Less fit: Junior, high workload, missing some skills)
        $developerB = Mockery::mock();
        $developerB->id = 2;
        $developerB->name = 'Developer B';
        $developerB->experience_years = 1;
        $developerB->availability = 1.0;
        $developerB->skills = ['PHP'];
        $developerB->shouldReceive('hasRole')->with('senior')->andReturn(false);
        $developerB->shouldReceive('hasRole')->with('junior')->andReturn(true);

        // Inputs for the algorithm
        $priority = 1; // High priority task
        $estimatedHours = 8;
        $skillRequirements = ['PHP', 'Laravel'];
        $isCritical = true;

        // Simulated current workloads
        $workloadA = 10; // Low workload
        $workloadB = 35; // High workload (near capacity for junior)

        // Call selectResponsibleUser directly (it is now public!)
        $result = $service->selectResponsibleUser(
            [$developerA, $developerB],
            [1 => $workloadA, 2 => $workloadB],
            $priority,
            $estimatedHours,
            $skillRequirements,
            $isCritical,
            [1 => 10, 2 => 1] // Experience counts
        );

        $selectedUser = $result['user'];
        $candidates = $result['candidates'];

        // Assert Developer A was chosen
        $this->assertEquals(1, $selectedUser->id, "Developer A should be chosen");
        $this->assertEquals('Developer A', $selectedUser->name);

        // Assert we have candidate data
        $this->assertCount(2, $candidates, "There should be 2 candidates");
        
        // Generate assessment (simulating what we do in ProjectResource)
        $assessment = "Chosen via algorithm.\n";
        foreach ($candidates as $candidate) {
            if ($candidate['user']->id !== $selectedUser->id) {
                $assessment .= "- {$candidate['user']->name}: Score {$candidate['score']} (Workload: {$candidate['workload']}h)\n";
            }
        }

        // Output the report
        echo "\n\n--- Algorithm Test Report ---\n";
        echo "Selected User: " . $selectedUser->name . "\n";
        echo "Assessment:\n" . $assessment;
        echo "-----------------------------\n\n";

        $this->assertStringContainsString('Developer B', $assessment, "Assessment should mention Developer B");
    }

    public function test_sequential_assignments_consider_previous_workload()
    {
        $service = new TaskComputationsService();

        // Create mocks for Developer A and B
        $developerA = Mockery::mock();
        $developerA->id = 1;
        $developerA->name = 'Developer A';
        $developerA->experience_years = 5;
        $developerA->availability = 1.0;
        $developerA->skills = ['PHP'];
        $developerA->shouldReceive('hasRole')->with('senior')->andReturn(true);
        $developerA->shouldReceive('hasRole')->with('junior')->andReturn(false);

        $developerB = Mockery::mock();
        $developerB->id = 2;
        $developerB->name = 'Developer B';
        $developerB->experience_years = 4; // Slightly less
        $developerB->availability = 1.0;
        $developerB->skills = ['PHP'];
        $developerB->shouldReceive('hasRole')->with('senior')->andReturn(true);
        $developerB->shouldReceive('hasRole')->with('junior')->andReturn(false);

        // Initial workloads are equal
        $cachedWorkloads = [1 => 10, 2 => 10];

        // Task 1: 10 hours
        $result1 = $service->selectResponsibleUser(
            [$developerA, $developerB],
            $cachedWorkloads,
            2, // Priority
            10, // Hours
            [],
            false,
            [1 => 5, 2 => 5]
        );

        $selectedUser1 = $result1['user'];
        // We expect Developer A to win because they have more experience (5 vs 4)
        $this->assertEquals(1, $selectedUser1->id, "Developer A should be chosen for task 1");

        // Update workload cache for Developer A
        $cachedWorkloads[$selectedUser1->id] += 10; // Now A has 20h, B has 10h

        // Task 2: 10 hours
        $result2 = $service->selectResponsibleUser(
            [$developerA, $developerB],
            $cachedWorkloads, // Pass updated workloads!
            2, // Priority
            10, // Hours
            [],
            false,
            [1 => 5, 2 => 5]
        );

        $selectedUser2 = $result2['user'];
        // We expect Developer B to win because Developer A now has more workload
        $this->assertEquals(2, $selectedUser2->id, "Developer B should be chosen for task 2");

        echo "\n\n--- Sequential Assignment Test Report ---\n";
        echo "Task 1 Assigned To: " . $selectedUser1->name . " (Workload became: " . $cachedWorkloads[$selectedUser1->id] . "h)\n";
        echo "Task 2 Assigned To: " . $selectedUser2->name . " (Workload stayed: " . $cachedWorkloads[$selectedUser2->id] . "h)\n";
        echo "-----------------------------------------\n\n";
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
