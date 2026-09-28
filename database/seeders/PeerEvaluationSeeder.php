<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PeerEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('evaluation_forms') || !Schema::hasTable('evaluation_questions')) {
            $this->command?->warn('Peer evaluation form tables are not available. Run migrations first.');
            return;
        }

        $questions = [
            'Shares relevant up-to-date ideas during faculty meetings',
            'Shares ideas with other members of the unit on how to teach a subject.',
            'Volunteers to participate in committee work.',
            'Accepts responsibilities willingly.',
            'Attends faculty meetings and other school activities regularly and promptly.',
            'Accomplishes assigned reports and other tasks accurately and promptly as member of a committee.',
            'Possesses pleasant disposition and helps maintain esprit de corps by relating harmoniously with colleagues, administrative staff, and students.',
            'Communicates ideas clearly and accurately.',
            'Shows open-mindedness by respecting the ideas of others.',
            'Shows intellectual honesty by giving due recognition to the works of others.',
            'Shows concern and sincerity in dealing with others.',
            'Respects dignity of others by not speaking ill of them.',
            'Helps maintain an academic atmosphere in the unit and school as a whole.',
            'Shows evidence of commitment to the mission of the school.',
            'In sum, tries to exhibit exemplary behavior of a true professional.',
        ];

        DB::transaction(function () use ($questions): void {
            $now = now();

            DB::table('evaluation_questions')->where('form_type', 'peer')->delete();
            DB::table('evaluation_forms')->where('form_type', 'peer')->delete();

            $formId = DB::table('evaluation_forms')->insertGetId([
                'form_type' => 'peer',
                'title' => 'Faculty Peer Evaluation Scale',
                'instructions' => 'The statements below reflect some behaviors of members of the academic community. Read each statement carefully and encircle the column which most nearly describes the colleague you are rating. Rate each item from 1 to 5, with 5 being the highest.',
                'version' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('evaluation_rating_scales')) {
                foreach ([
                    ['value' => '5', 'label' => '5 - High'],
                    ['value' => '4', 'label' => '4 - High'],
                    ['value' => '3', 'label' => '3 - Moderate'],
                    ['value' => '2', 'label' => '2 - Moderate'],
                    ['value' => '1', 'label' => '1 - Low'],
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

            $sectionId = null;
            if (Schema::hasTable('evaluation_sections')) {
                $sectionId = DB::table('evaluation_sections')->insertGetId([
                    'form_id' => $formId,
                    'title' => 'FACULTY PEER EVALUATION CRITERIA',
                    'description' => 'Total Score and Mean (Total Score divided by 15).',
                    'order_num' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($questions as $index => $question) {
                $questionData = [
                    'form_type' => 'peer',
                    'category' => 'Faculty Peer Collaboration & Professionalism',
                    'question' => $question,
                    'order_num' => $index + 1,
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
        });

        $this->command?->info('Peer evaluation form replaced with the 15-item faculty peer rubric.');
    }
}