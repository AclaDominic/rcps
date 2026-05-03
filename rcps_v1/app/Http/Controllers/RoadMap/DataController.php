<?php

namespace App\Http\Controllers\RoadMap;

use App\Http\Controllers\Controller;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class DataController extends Controller
{

    /**
     * Get project epics data
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function data(Project $project): JsonResponse
    {
        $project = Project::where(function ($query) {
            return $query->where('owner_id', auth()->user()->id)
                ->orWhereHas('users', function ($query) {
                    return $query->where('users.id', auth()->user()->id);
                })
                ->orWhereHas('tickets', function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('owner_id', auth()->user()->id)
                                ->orWhere('responsible_id', auth()->user()->id);
                        });
                    });
        })->where('id', $project->id)->first();
        if (!$project) {
            return response()->json([]);
        }
        $epics = Epic::where('project_id', $project->id)->get();
        return response()->json($this->formatResponse($epics, $project));
    }

    /**
     * Format epics to JSON data
     *
     * @param Collection $epics
     * @return Collection
     */
    private function formatResponse(Collection $epics, Project $project): Collection
    {
        $isPrivileged = auth()->user()->hasRoleType(['CORE', 'MANAGER']);

        $results = collect();
        
        foreach ($epics->sortBy('id') as $epic) {
            $results->push(collect($this->epicObj($epic)));

            // Filtered tickets (only if user is owner or responsible)
            $tickets = $epic->tickets()
                ->when(!$isPrivileged, function ($query) {
                    $query->where(function ($q) {
                        $q->where('owner_id', auth()->id())
                        ->orWhere('responsible_id', auth()->id());
                    });
                })
                ->get();

            foreach ($tickets as $ticket) {
                $results->push(collect($this->ticketObj($epic, $ticket)));
            }
        }

        // Ungrouped tickets (no epic_id)
        $ungroupedTickets = Ticket::where('project_id', $project->id)
            ->whereNull('epic_id')
           ->when(!$isPrivileged, function ($query) {
                $query->where(function ($q) {
                    $q->where('owner_id', auth()->id())
                    ->orWhere('responsible_id', auth()->id());
                });
            })
            ->orderBy('id')
            ->get();

        foreach ($ungroupedTickets as $ticket) {
            $results->push(collect($this->ticketObj(null, $ticket)));
        }

        return $results;
    }

    /**
     * Format Epic object
     *
     * @param Epic $epic
     * @return array
     */
    private function epicObj(Epic $epic)
    {
        if($epic->sprint){
            $name = $epic->sprint->name .' ('.$epic->name.')';
        }else{
            if($epic->remarks){
                $name = $epic->name . ' (' . $epic->remarks . ')';
            }else{
                $name = $epic->name;
            }
        }

        $data = [
            "pID" => $epic->id,
            "pName" =>$name,
            "pStart" => $epic->starts_at->format('Y-m-d'),
            "pEnd" => $epic->ends_at->format('Y-m-d') . " 23:59:59",
            "pClass" => "g-custom-task",
            "pLink" => "",
            "pMile" => 0,
            "pRes" => "",
            "pComp" => "",
            "pGroup" => 1,
            "pParent" => 0,
            "pOpen" => 1,
            "pDepend" => $epic->parent_id ?? "",
            "pCaption" => "",
            "pNotes" => "",
            "pBarText" => "",
            "meta" => [
                "id" => $epic->id,
                "epic" => true,
                "parent" => null,
                "slug" => null
            ]
        ];
        return $data;
    }

    /**
     * Format Ticket object
     *
     * @param Epic $epic
     * @param Ticket $ticket
     * @return array
     */
    private function ticketObj(Epic|null $epic, Ticket $ticket)
    {
        $pComp = round($ticket->completudePercentage, 0);
        return [
            "pID" => ($epic?->id ?? "N") . $ticket->id,
            "pName" => $ticket->name,
            "pStart" => "",
            "pEnd" => "",
            "pClass" => "g-custom-task",
            "pLink" => "",
            "pMile" => 0,
            "pRes" => $ticket->responsible?->name ?? "",
            "pComp" => min($pComp, 100),
            "pGroup" => 0,
            "pParent" => $epic?->id ?? "",
            "pOpen" => 1,
            "pDepend" => "",
            "pCaption" => "",
            "pNotes" => "",
            "pBarText" => "",
            "meta" => [
                "id" => $ticket->id,
                "epic" => false,
                "parent" => $epic?->id ?? null,
                "slug" => $ticket->code
            ]
        ];
    }

}
