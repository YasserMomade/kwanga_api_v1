<?php

namespace App\Observers;

use App\Models\task;

class TaskObserver
{
    /**
     * Handle the task "created" event.
     *
     * @param  \App\Models\task  $task
     * @return void
     */
    public function created(task $task)
    {
        //
    }

    /**
     * Handle the task "updated" event.
     *
     * @param  \App\Models\task  $task
     * @return void
     */
    public function updated(task $task)
    {

        //Verifica se o status da tarafa mudou
        if ($task->isDirty('status')) {

            // Representa relacionamento entre task e project
            $project = $task->project;

            if ($project) {
                // Se todas as tarefas estiverem concluidas marca o projeto como completed

                if ($project->tasks()->where('status', '!=', 'completed')->count() === 0) {
                    $project->status = 'completed';
                    $project->save();
                } else {
                    if ($project->status === 'pending') {
                        $project->status = 'in_progress';
                        $project->save();
                    }
                }
            }
        }
    }

    /**
     * Handle the task "deleted" event.
     *
     * @param  \App\Models\task  $task
     * @return void
     */
    public function deleted(task $task)
    {
        //
    }

    /**
     * Handle the task "restored" event.
     *
     * @param  \App\Models\task  $task
     * @return void
     */
    public function restored(task $task)
    {
        //
    }

    /**
     * Handle the task "force deleted" event.
     *
     * @param  \App\Models\task  $task
     * @return void
     */
    public function forceDeleted(task $task)
    {
        //
    }
}


/**
 * Quando criamos o observer, depois devemos o registrar
 * 
 * no AppServiceProvider
 *
 * 
 */
