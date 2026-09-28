<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrincipalEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('evaluation_forms') || !Schema::hasTable('evaluation_questions')) {
            $this->command?->warn('Principal evaluation form tables are not available. Run migrations first.');
            return;
        }

        $sections = [
            [
                'title' => 'I. INSTRUCTIONAL COMPETENCE (50%)',
                'questions' => [
                    'formulates objectives of lesson plan',
                    'prepares appropriate teaching aids',
                    'knowledge in teaching methods, strategies and techniques',
                    'utilizes the art of questioning to develop higher level of thinking',
                    'conveys ideas clearly',
                    "ensures students' participation",
                    'recognizes individual differences',
                    'shows enthusiasm in teaching',
                    'shows mastery of the subject matter',
                    "diagnoses learners' needs",
                    'knowledge in constructing test questions',
                    'maintains classroom conducive to learning',
                    "prepares and utilizes learners' records effectively",
                    "encourages parents' involvement in school programs and activities",
                    'exercises parental responsibility to the learners',
                ],
            ],
            [
                'title' => 'II. PROFESSIONAL/PERSONAL CHARACTERISTICS (30%)',
                'questions' => [
                    'Obedience',
                    'Honesty/integrity',
                    'Dedication/commitment',
                    'Initiative',
                    'Courtesy/politeness',
                    'Human relations',
                    'Leadership',
                    'Stress management',
                    'Cooperation',
                    'Proper attire/grooming',
                ],
            ],
            [
                'title' => 'III. PARTICIPATION (20%)',
                'questions' => [
                    'Punctuality',
                    'Attendance',
                ],
            ],
        ];

        DB::transaction(function () use ($sections): void {
            $now = now();

            if (Schema::hasTable('evaluation_questions')) {
                DB::table('evaluation_questions')->where('form_type', 'principal')->delete();
            }
            DB::table('evaluation_forms')->where('form_type', 'principal')->delete();

            $formId = DB::table('evaluation_forms')->insertGetId([
                'form_type' => 'principal',
                'title' => "Principal's Evaluation",
                'instructions' => 'On a scale of 1 to 5 (5 being the highest), input your rating on the box. 40% of the total rating of the faculty will come from this part.',
                'version' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('evaluation_rating_scales')) {
                foreach ([
                    ['value' => '5', 'label' => '5 - Highest'],
                    ['value' => '4', 'label' => '4'],
                    ['value' => '3', 'label' => '3'],
                    ['value' => '2', 'label' => '2'],
                    ['value' => '1', 'label' => '1 - Lowest'],
                ] as $index => $scale) {
                    DB::table('evaluation_rating_scales')->insert([
                        'form_id' => $formId,
                        'value' => $scale['value'],
                        'label' => $scale['label'],
                        'order_num' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            foreach ($sections as $sectionIndex => $section) {
                $sectionId = null;
                if (Schema::hasTable('evaluation_sections')) {
                    $sectionId = DB::table('evaluation_sections')->insertGetId([
                        'form_id' => $formId,
                        'title' => $section['title'],
                        'description' => null,
                        'order_num' => $sectionIndex + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($section['questions'] as $questionIndex => $question) {
                    $questionData = [
                        'form_type' => 'principal',
                        'category' => $section['title'],
                        'question' => $question,
                        'order_num' => $questionIndex + 1,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (Schema::hasColumn('evaluation_questions', 'form_id')) {
                        $questionData['form_id'] = $formId;
                    }
                    if ($sectionId && Schema::hasColumn('evaluation_questions', 'section_id')) {
                        $questionData['section_id'] = $sectionId;
                    }
                    if (Schema::hasColumn('evaluation_questions', 'type')) {
                        $questionData['type'] = 'likert';
                    }
                    if (Schema::hasColumn('evaluation_questions', 'is_required')) {
                        $questionData['is_required'] = 1;
                    }

                    DB::table('evaluation_questions')->insert($questionData);
                }
            }
        });

        $this->command?->info('Principal evaluation form replaced with the 27-item institutional rubric.');
    }
}