<div class="space-y-4">

    {{-- Project selection --}}
    <div class="grid grid-cols-4 gap-4">
        <div>
            <label class=" text-sm font-medium">Select Project</label>
            <select wire:model="projectId" class="w-full border rounded px-3 py-2">
                <option value="">-- Select Project --</option>
                @foreach($projects as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filter type --}}
        <div>
            <label class=" text-sm font-medium">Filter Type</label>
            <select wire:model="filterType" class="w-full border rounded px-3 py-2">
                <option value="date_range">Date Range</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>

    {{-- Conditional Filters --}}
    @if($filterType === 'date_range')
            <div>
                <label>Date From</label>
                <input 
                    type="date" 
                    wire:model="dateFrom" 
                    class="w-full border rounded px-3 py-2" 
                    max="{{ $dateTo ?? now()->toDateString() }}" 
                />
            </div>
            <div>
                <label>Date To</label>
                <input 
                    type="date" 
                    wire:model="dateTo" 
                    class="w-full border rounded px-3 py-2" 
                    min="{{ $dateFrom }}" 
                    max="{{ now()->toDateString() }}" 
                />
            </div>
    @elseif($filterType === 'weekly')
            <div>
                <label>Month</label>
                <select wire:model="selectedMonth" class="w-full border rounded px-3 py-2">
                    @foreach($months as $month)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Week</label>
                <select wire:model="selectedWeek" class="w-full border rounded px-3 py-2">
                    <option value="1">Week 1</option>
                    <option value="2">Week 2</option>
                    <option value="3">Week 3</option>
                    <option value="4">Week 4</option>
                </select>
            </div>
    @elseif($filterType === 'monthly')
        <div>
            <label>Year</label>
            <select wire:model="selectedYear" class="w-full border rounded px-3 py-2">
                @foreach($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    @elseif($filterType === 'yearly')
        {{-- <div>
            <label>Year</label>
            <select wire:model="selectedYear" class="w-full border rounded px-3 py-2">
                @foreach($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div> --}}
    @endif
    <div class="flex items-end">
       <button type="button"
            wire:click="applyFilter"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Apply Filter
        </button>
    </div>
    </div>

</div>
