<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comparison extends Model
{
    protected $fillable = [
        'comparison_id',
        'greedy_project_id',
        'divide_conquer_project_id',
        'created_by',
        'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array'
    ];
    
    public function greedyProject()
    {
        return $this->belongsTo(Project::class, 'greedy_project_id');
    }
    
    public function divideConquerProject()
    {
        return $this->belongsTo(Project::class, 'divide_conquer_project_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}