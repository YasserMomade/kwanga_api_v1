<?php

namespace App\Observers;

use App\Models\project;

class ProjectObserver
{
    /**
     * Handle the project "created" event.
     *
     * @param  \App\Models\project  $project
     * @return void
     */
    public function created(project $project) {}

    /**
     * Handle the project "updated" event.
     *
     * @param  \App\Models\project  $project
     * @return void
     */
    public function updated(project $project)
    {
        if ($project->isDirty('status')) {
            $monthlyGoal = $project->monthlyGoal;
            if ($monthlyGoal) {
                $monthlyGoal->updateStatusFromProjects();
            }
        }
    }

    /**
     * Handle the project "deleted" event.
     *
     * @param  \App\Models\project  $project
     * @return void
     */
    public function deleted(project $project)
    {
        $monthlyGoal = $project->monthlyGoal;
        if ($monthlyGoal) {
            $monthlyGoal->updateStatusFromProjects();
        }
    }

    /**
     * Handle the project "restored" event.
     *
     * @param  \App\Models\project  $project
     * @return void
     */
    public function restored(project $project)
    {
        //
    }

    /**
     * Handle the project "force deleted" event.
     *
     * @param  \App\Models\project  $project
     * @return void
     */
    public function forceDeleted(project $project)
    {
        //
    }
}
