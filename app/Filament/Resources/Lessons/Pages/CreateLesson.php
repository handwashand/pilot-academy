<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * The first course chosen is the lesson's home (Lesson::saved() files it
     * there); the lesson then joins the others, at the end of each.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $courseIds = array_values(array_map('intval', (array) ($data['course_ids'] ?? [])));
        unset($data['course_ids']);

        $lesson = new Lesson(['course_id' => $courseIds[0] ?? null, ...$data]);
        $lesson->save();

        $lesson->courses()->syncWithoutDetaching($courseIds);

        return $lesson;
    }
}
