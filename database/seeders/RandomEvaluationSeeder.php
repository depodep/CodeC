<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Carbon\Carbon;

class RandomEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('evaluation_cycles')) {
            $this->command?->warn('Evaluation cycle table is not available. Run migrations first.');
            return;
        }

        if (!DB::table('evaluation_cycles')->exists()) {
            $schoolYear = DB::table('academic_periods')->where('is_active', 1)->value('school_year')
                ?? date('Y') . '-' . (date('Y') + 1);
            $now = now();

            $cycleId = DB::table('evaluation_cycles')->insertGetId([
                'name' => 'Demo Evaluation Cycle',
                'school_year' => $schoolYear,
                'start_date' => $now->copy()->subDays(30)->toDateString(),
                'end_date' => $now->copy()->addDays(30)->toDateString(),
                'status' => 'active',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasColumn('evaluation_cycles', 'student_weight')) {
                DB::table('evaluation_cycles')->where('id', $cycleId)->update([
                    'student_weight' => 40,
                    'principal_weight' => 40,
                    'self_weight' => 10,
                    'peer_weight' => 10,
                ]);
            }
        }

        // 1. Identify Target Evaluation Cycle
        $cycle = DB::table('evaluation_cycles')->where('status', 'active')->first()
            ?? DB::table('evaluation_cycles')->first();

        if (!$cycle) {
            $this->command?->error('No evaluation cycle found in database. Please run cycle migrations/seeders first.');
            return;
        }

        $cycleId = $cycle->id;

        // Ensure cycle is active if desired
        DB::table('evaluation_cycles')->where('id', $cycleId)->update([
            'status' => 'active',
            'is_active' => 1,
            'updated_at' => now(),
        ]);

        // 2. Identify Academic Period
        $academicPeriodId = 1;
        if (Schema::hasTable('academic_periods')) {
            $academicPeriodId = DB::table('academic_periods')->where('is_active', 1)->value('id')
                ?? DB::table('academic_periods')->value('id')
                ?? 1;
        }

        // 3. Retrieve Teachers and Students
        $teachers = User::where('role_id', 2)->get();

        if ($teachers->isEmpty()) {
            $this->command?->warn('No teachers found in users table.');
            return;
        }

        $students = User::where('role_id', 3)->get();

        if ($students->isEmpty()) {
            $this->command?->warn('No students found in users table.');
            return;
        }

        // 4. Identify or Designate Principal Evaluator
        $principal = null;
        if (Schema::hasColumn('users', 'is_principal')) {
            $principal = User::where('is_principal', 1)->first();
        }
        if (!$principal) {
            $principal = User::where('role_id', 4)->first() // Executive Director
                ?? User::where('role_id', 1)->first();      // Admin
        }

        if ($principal && Schema::hasColumn('users', 'is_principal')) {
            User::where('id', $principal->id)->update(['is_principal' => 1]);
        }

        // 5. Fetch Active Questions for each evaluation form
        $studentQuestions = DB::table('evaluation_questions')
            ->where('form_type', 'student')
            ->where('is_active', 1)
            ->orderBy('order_num', 'asc')
            ->get();
        if ($studentQuestions->isEmpty()) {
            $studentQuestions = DB::table('evaluation_questions')
                ->where('form_type', 'student')
                ->orderBy('order_num', 'asc')
                ->get();
        }

        $peerQuestions = DB::table('evaluation_questions')
            ->where('form_type', 'peer')
            ->where('is_active', 1)
            ->orderBy('order_num', 'asc')
            ->get();
        if ($peerQuestions->isEmpty()) {
            $peerQuestions = DB::table('evaluation_questions')
                ->where('form_type', 'peer')
                ->orderBy('order_num', 'asc')
                ->get();
        }

        $selfQuestions = DB::table('evaluation_questions')
            ->where('form_type', 'self')
            ->where('is_active', 1)
            ->orderBy('order_num', 'asc')
            ->get();
        if ($selfQuestions->isEmpty()) {
            $selfQuestions = DB::table('evaluation_questions')
                ->where('form_type', 'self')
                ->orderBy('order_num', 'asc')
                ->get();
        }

        $principalQuestions = DB::table('evaluation_questions')
            ->where('form_type', 'principal')
            ->where('is_active', 1)
            ->orderBy('order_num', 'asc')
            ->get();
        if ($principalQuestions->isEmpty()) {
            $principalQuestions = DB::table('evaluation_questions')
                ->where('form_type', 'principal')
                ->orderBy('order_num', 'asc')
                ->get();
        }

        // 6. Clean up previous evaluations for this cycle to avoid duplicate chaos
        if (Schema::hasTable('evaluation_submissions')) {
            DB::table('evaluation_submissions')->where('evaluation_cycle_id', $cycleId)->delete();
        }
        if (Schema::hasTable('peer_evaluations')) {
            DB::table('peer_evaluations')->where('evaluation_cycle_id', $cycleId)->delete();
        }
        if (Schema::hasTable('self_evaluations')) {
            DB::table('self_evaluations')->where('evaluation_cycle_id', $cycleId)->delete();
        }

        // Helper closures for generating ratings and comments
        $pickRating = function(array $distribution): int {
            // Distribution format: [score => probabilityWeight]
            $rand = mt_rand(1, array_sum($distribution));
            $cumulative = 0;
            foreach ($distribution as $score => $weight) {
                $cumulative += $weight;
                if ($rand <= $cumulative) {
                    return (int)$score;
                }
            }
            return 4;
        };

        $generateAnswers = function($questions, array $distribution) use ($pickRating): array {
            $answers = [];
            foreach ($questions as $q) {
                $answers[$q->id] = $pickRating($distribution);
            }
            return $answers;
        };

        $computeAverage = function(array $answers): float {
            if (empty($answers)) return 0.0;
            return round(array_sum($answers) / count($answers), 2);
        };

        // Realistic Comment Banks
        $studentCommentsPool = [
            'Excellently discusses the lesson and gives clear practical examples.',
            'Very approachable and patient when answering student questions during and after class.',
            'Lessons are interactive, engaging, and easy to understand.',
            'Mabait at magaling magturo si Sir/Ma\'am, laging handa sa discussion.',
            'Always on time and well-prepared with presentation slides and activities.',
            'Very fair grading system and provides helpful feedback on our assignments.',
            'Encourages critical thinking and active student participation.',
            'Strict pero matututo ka talaga sa subject niya.',
            'Consistently organizes group discussions and practical laboratory exercises.',
            'Inspiring teacher who motivates us to strive for excellence in academics.',
            'Clear voice and very thorough explanation of complex topics.',
            'Always makes sure everyone in class understands before moving to the next chapter.',
            'Very accommodating to consultations and respects students\' ideas.',
            'Creates a conducive and respectful classroom environment.',
            null, // Realistic blank comments
            null,
        ];

        $peerCommentsPool = [
            'Consistently collaborates well during departmental meetings and curriculum planning.',
            'Shares innovative teaching materials and willingly assists other faculty members.',
            'Displays commendable professionalism, punctuality, and commitment to school activities.',
            'Very supportive colleague with exceptional dedication to student welfare and academic excellence.',
            'Actively participates in committee assignments and produces thorough reports promptly.',
            'Maintains harmonious relationships with colleagues, staff, and students.',
            'Shows great leadership and willingness to mentor junior faculty members.',
        ];

        $selfCommentsPool = [
            'Continuously striving to enhance instructional strategies and integrate modern multimedia tools into daily lessons.',
            'Aiming to further improve student engagement techniques and provide timely formative assessments.',
            'Dedicated to ongoing professional development and participating in academic seminars.',
            'Will work on improving pacing of complex curriculum modules to give students more practice time.',
            'Committed to maintaining open communication and supportive guidance for all my advisory students.',
        ];

        $principalCommentsPool = [
            'Demonstrates outstanding mastery of subject matter and highly effective pedagogy.',
            'Commendable classroom management and consistent positive rapport with students and peers.',
            'Meets institutional deadlines accurately and adheres closely to school policies and ethics.',
            'Exceptional dedication to instructional delivery and continuous learner development.',
            'Very cooperative with administrative directives and takes active leadership in school programs.',
        ];

        // Teacher rating profiles: give each teacher a slight personality for authentic analytics graphs!
        $teacherProfiles = [
            // Teacher 1: Strong, mostly 4s and 5s
            0 => ['weights' => [5 => 50, 4 => 38, 3 => 10, 2 => 2, 1 => 0]],
            // Teacher 2: Top Benchmark, mostly 5s
            1 => ['weights' => [5 => 68, 4 => 27, 3 => 5, 2 => 0, 1 => 0]],
            // Teacher 3: Good, diverse distribution
            2 => ['weights' => [5 => 38, 4 => 42, 3 => 16, 2 => 4, 1 => 0]],
        ];

        $now = now();
        $totalStudentSubs = 0;
        $totalPeerSubs = 0;
        $totalSelfSubs = 0;
        $totalPrincipalSubs = 0;

        // -------------------------------------------------------------
        // A. SEED STUDENT EVALUATIONS (All students evaluate teachers)
        // -------------------------------------------------------------
        if ($studentQuestions->isNotEmpty()) {
            foreach ($students as $sIdx => $student) {
                // Each student evaluates 2 or 3 teachers (or all teachers with 85% probability)
                foreach ($teachers as $tIdx => $teacher) {
                    // Let each student evaluate each teacher with high chance (~80%) or ensure at least 2 teachers
                    if (($sIdx + $tIdx) % 4 === 0 && count($teachers) > 1) {
                        continue; // Skip 1 teacher occasionally so counts vary naturally
                    }

                    $profile = $teacherProfiles[$tIdx % count($teacherProfiles)]['weights'];
                    $answers = $generateAnswers($studentQuestions, $profile);
                    $avgScore = $computeAverage($answers);
                    $comment = $studentCommentsPool[array_rand($studentCommentsPool)];

                    // Random timestamp within the last 5 days
                    $submittedAt = Carbon::now()
                        ->subDays(mt_rand(0, 5))
                        ->subHours(mt_rand(1, 12))
                        ->subMinutes(mt_rand(5, 55));

                    DB::table('evaluation_submissions')->insert([
                        'evaluation_cycle_id' => $cycleId,
                        'academic_period_id'  => $academicPeriodId,
                        'form_type'           => 'student',
                        'student_id'          => $student->id,
                        'evaluator_id'        => $student->id,
                        'teacher_id'          => $teacher->id,
                        'average_score'       => $avgScore,
                        'status'              => 'SUBMITTED',
                        'comments'            => $comment,
                        'answers'             => json_encode($answers),
                        'created_at'          => $submittedAt,
                        'updated_at'          => $submittedAt,
                    ]);

                    $totalStudentSubs++;
                }
            }
        }

        // -------------------------------------------------------------
        // B. SEED PEER EVALUATIONS (Teachers evaluate all other teachers)
        // -------------------------------------------------------------
        if ($peerQuestions->isNotEmpty()) {
            foreach ($teachers as $evaluatorIdx => $evaluator) {
                foreach ($teachers as $evaluateeIdx => $evaluatee) {
                    if ($evaluator->id === $evaluatee->id) {
                        continue; // Peer does not evaluate self here
                    }

                    $profile = $teacherProfiles[$evaluateeIdx % count($teacherProfiles)]['weights'];
                    $answers = $generateAnswers($peerQuestions, $profile);
                    $avgScore = $computeAverage($answers);
                    $comment = $peerCommentsPool[array_rand($peerCommentsPool)];

                    $submittedAt = Carbon::now()
                        ->subDays(mt_rand(1, 4))
                        ->subHours(mt_rand(2, 8))
                        ->subMinutes(mt_rand(10, 50));

                    DB::table('peer_evaluations')->insert([
                        'evaluation_cycle_id' => $cycleId,
                        'evaluator_id'        => $evaluator->id,
                        'evaluatee_id'        => $evaluatee->id,
                        'average_score'       => $avgScore,
                        'comments'            => $comment,
                        'answers'             => json_encode($answers),
                        'created_at'          => $submittedAt,
                        'updated_at'          => $submittedAt,
                    ]);

                    $totalPeerSubs++;
                }
            }
        }

        // -------------------------------------------------------------
        // C. SEED SELF EVALUATIONS (Each teacher evaluates themselves)
        // -------------------------------------------------------------
        if ($selfQuestions->isNotEmpty()) {
            foreach ($teachers as $tIdx => $teacher) {
                // Self evaluation rubric is typically 1 to 4 or 1 to 5
                $selfWeights = [4 => 45, 3 => 45, 2 => 10, 1 => 0];
                $answers = $generateAnswers($selfQuestions, $selfWeights);
                $avgScore = $computeAverage($answers);
                $comment = $selfCommentsPool[$tIdx % count($selfCommentsPool)];

                $submittedAt = Carbon::now()
                    ->subDays(mt_rand(1, 5))
                    ->subHours(mt_rand(1, 6));

                DB::table('self_evaluations')->insert([
                    'evaluation_cycle_id' => $cycleId,
                    'teacher_id'          => $teacher->id,
                    'average_score'       => $avgScore,
                    'comments'            => $comment,
                    'answers'             => json_encode($answers),
                    'created_at'          => $submittedAt,
                    'updated_at'          => $submittedAt,
                ]);

                $totalSelfSubs++;
            }
        }

        // -------------------------------------------------------------
        // D. SEED PRINCIPAL EVALUATIONS (Principal evaluates all teachers)
        // -------------------------------------------------------------
        if ($principalQuestions->isNotEmpty() && $principal) {
            foreach ($teachers as $tIdx => $teacher) {
                $principalProfile = [5 => 55, 4 => 38, 3 => 7, 2 => 0, 1 => 0];
                $answers = $generateAnswers($principalQuestions, $principalProfile);
                $avgScore = $computeAverage($answers);
                $comment = $principalCommentsPool[$tIdx % count($principalCommentsPool)];

                $submittedAt = Carbon::now()
                    ->subDays(mt_rand(0, 3))
                    ->subHours(mt_rand(1, 5));

                DB::table('evaluation_submissions')->insert([
                    'evaluation_cycle_id' => $cycleId,
                    'academic_period_id'  => $academicPeriodId,
                    'form_type'           => 'principal',
                    'student_id'          => null,
                    'evaluator_id'        => $principal->id,
                    'teacher_id'          => $teacher->id,
                    'average_score'       => $avgScore,
                    'status'              => 'SUBMITTED',
                    'comments'            => $comment,
                    'answers'             => json_encode($answers),
                    'created_at'          => $submittedAt,
                    'updated_at'          => $submittedAt,
                ]);

                $totalPrincipalSubs++;
            }
        }

        $this->command?->info("Successfully seeded random evaluations for Cycle #{$cycleId}:");
        $this->command?->info("  - Student evaluations : {$totalStudentSubs}");
        $this->command?->info("  - Peer evaluations    : {$totalPeerSubs}");
        $this->command?->info("  - Self evaluations    : {$totalSelfSubs}");
        $this->command?->info("  - Principal evaluations: {$totalPrincipalSubs}");
    }
}
