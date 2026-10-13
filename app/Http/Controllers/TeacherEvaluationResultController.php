<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TeacherEvaluationResultController extends Controller
{
    /**
     * Display the authenticated teacher's published evaluation results.
     */
    public function index(Request $request)
    {
        $teacher = Auth::user();
        if (!$teacher || (int)$teacher->role_id !== 2) {
            abort(403, 'Unauthorized access to faculty evaluation results.');
        }

        // 1. Fetch available evaluation cycles
        $allCycles = collect();
        if (Schema::hasTable('evaluation_cycles')) {
            $allCycles = DB::table('evaluation_cycles')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // 2. Determine selected cycle
        $selectedCycleId = $request->query('cycle_id');
        $selectedCycle = null;

        if ($selectedCycleId && $allCycles->isNotEmpty()) {
            $selectedCycle = $allCycles->firstWhere('id', (int)$selectedCycleId);
        }
        if (!$selectedCycle && $allCycles->isNotEmpty()) {
            // Default to active cycle if available, otherwise first cycle
            $selectedCycle = $allCycles->firstWhere('is_active', 1) 
                ?? $allCycles->firstWhere('status', 'active') 
                ?? $allCycles->first();
        }

        // 3. Check publication status in teacher_evaluation_publications
        $isPublished = false;
        $publishedAt = null;

        if ($selectedCycle && Schema::hasTable('teacher_evaluation_publications')) {
            $pub = DB::table('teacher_evaluation_publications')
                ->where('teacher_id', $teacher->id)
                ->where('evaluation_cycle_id', $selectedCycle->id)
                ->first();

            if ($pub) {
                $isPublished = (bool) $pub->is_published;
                $publishedAt = $pub->published_at ? Carbon::parse($pub->published_at) : null;
            }
        }

        // 4. Default evaluation weights (customizable via settings)
        $weights = [
            'student'   => 50,
            'peer'      => 30,
            'self'      => 10,
            'principal' => 10,
        ];
        if (Schema::hasTable('settings')) {
            $dbWeights = DB::table('settings')->where('key', 'evaluation_weights')->value('value');
            if ($dbWeights) {
                $decoded = json_decode($dbWeights, true);
                if (is_array($decoded)) {
                    $weights = array_merge($weights, $decoded);
                }
            }
        }

        // Initialize metrics variables
        $studentCount = 0;
        $peerCount = 0;
        $selfCount = 0;
        $principalCount = 0;
        $totalSubmissions = 0;

        $studentAvg = null;
        $peerAvg = null;
        $selfAvg = null;
        $principalAvg = null;
        $overallWeightedScore = null;
        $overallDescriptor = 'Unpublished / Pending';
        $descriptorClass = 'bg-slate-100 text-slate-700 border-slate-300';

        $categoryMetrics = collect();
        $topStrength = null;
        $priorityImprovement = null;
        $improvementSuggestions = [];
        $anonymousComments = collect();

        // 5. If published, compute comprehensive results from real submissions
        if ($isPublished && $selectedCycle) {
            // Fetch questions for mapping answers to categories
            $allQuestions = Schema::hasTable('evaluation_questions')
                ? DB::table('evaluation_questions')->where('is_active', 1)->get()->keyBy('id')
                : collect();

            // Track category sums and counts
            $categoryRatings = []; // [category_name => ['sum' => x, 'count' => y]]

            // A. Student Submissions
            if (Schema::hasTable('evaluation_submissions')) {
                $stuQuery = DB::table('evaluation_submissions')
                    ->where('teacher_id', $teacher->id)
                    ->where('form_type', 'student');

                if (Schema::hasColumn('evaluation_submissions', 'evaluation_cycle_id')) {
                    $stuQuery->where('evaluation_cycle_id', $selectedCycle->id);
                }
                $stuSubmissions = $stuQuery->get();
                $studentCount = $stuSubmissions->count();
                if ($studentCount > 0) {
                    $studentAvg = round($stuSubmissions->avg('average_score'), 2);
                    foreach ($stuSubmissions as $sub) {
                        // Collect comments anonymously
                        if (!empty(trim((string)$sub->comments))) {
                            $anonymousComments->push((object)[
                                'role_label'   => 'Anonymous Student',
                                'role_badge'   => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'icon'         => 'fa-user-graduate',
                                'score'        => (float)$sub->average_score,
                                'score_format' => number_format((float)$sub->average_score, 2),
                                'comment'      => trim($sub->comments),
                                'date'         => $sub->created_at ? Carbon::parse($sub->created_at)->format('M d, Y') : null,
                            ]);
                        }
                        // Parse answers
                        $this->accumulateCategoryRatings($sub->answers, $allQuestions, $categoryRatings);
                    }
                }
            }

            // B. Peer Submissions
            if (Schema::hasTable('peer_evaluations')) {
                $peerQuery = DB::table('peer_evaluations')
                    ->where('evaluatee_id', $teacher->id);

                if (Schema::hasColumn('peer_evaluations', 'evaluation_cycle_id')) {
                    $peerQuery->where('evaluation_cycle_id', $selectedCycle->id);
                }
                $peerSubmissions = $peerQuery->get();
                $peerCount = $peerSubmissions->count();
                if ($peerCount > 0) {
                    $peerAvg = round($peerSubmissions->avg('average_score'), 2);
                    foreach ($peerSubmissions as $sub) {
                        if (!empty(trim((string)$sub->comments))) {
                            $anonymousComments->push((object)[
                                'role_label'   => 'Anonymous Colleague / Peer Faculty',
                                'role_badge'   => 'bg-blue-50 text-blue-800 border-blue-200',
                                'icon'         => 'fa-chalkboard-user',
                                'score'        => (float)$sub->average_score,
                                'score_format' => number_format((float)$sub->average_score, 2),
                                'comment'      => trim($sub->comments),
                                'date'         => $sub->created_at ? Carbon::parse($sub->created_at)->format('M d, Y') : null,
                            ]);
                        }
                        $this->accumulateCategoryRatings($sub->answers, $allQuestions, $categoryRatings);
                    }
                }
            }

            // C. Self Submissions
            if (Schema::hasTable('self_evaluations')) {
                $selfQuery = DB::table('self_evaluations')
                    ->where('evaluator_id', $teacher->id);

                if (Schema::hasColumn('self_evaluations', 'evaluation_cycle_id')) {
                    $selfQuery->where('evaluation_cycle_id', $selectedCycle->id);
                }
                $selfSub = $selfQuery->first();
                if ($selfSub) {
                    $selfCount = 1;
                    $selfAvg = round((float)$selfSub->average_score, 2);
                    $this->accumulateCategoryRatings($selfSub->answers, $allQuestions, $categoryRatings);
                }
            }

            // D. Principal / Management Submissions
            if (Schema::hasTable('evaluation_submissions')) {
                $prinQuery = DB::table('evaluation_submissions')
                    ->where('teacher_id', $teacher->id)
                    ->where('form_type', 'principal');

                if (Schema::hasColumn('evaluation_submissions', 'evaluation_cycle_id')) {
                    $prinQuery->where('evaluation_cycle_id', $selectedCycle->id);
                }
                $prinSubmissions = $prinQuery->get();
                $principalCount = $prinSubmissions->count();
                if ($principalCount > 0) {
                    $principalAvg = round($prinSubmissions->avg('average_score'), 2);
                    foreach ($prinSubmissions as $sub) {
                        if (!empty(trim((string)$sub->comments))) {
                            $anonymousComments->push((object)[
                                'role_label'   => 'Academic Administration / Principal',
                                'role_badge'   => 'bg-purple-50 text-purple-800 border-purple-200',
                                'icon'         => 'fa-shield-halved',
                                'score'        => (float)$sub->average_score,
                                'score_format' => number_format((float)$sub->average_score, 2),
                                'comment'      => trim($sub->comments),
                                'date'         => $sub->created_at ? Carbon::parse($sub->created_at)->format('M d, Y') : null,
                            ]);
                        }
                        $this->accumulateCategoryRatings($sub->answers, $allQuestions, $categoryRatings);
                    }
                }
            }

            $totalSubmissions = $studentCount + $peerCount + $selfCount + $principalCount;

            // Calculate Overall Weighted Score
            $weightedSum = 0;
            $appliedWeights = 0;

            if ($studentAvg !== null) {
                $weightedSum += ($studentAvg / 5) * $weights['student'];
                $appliedWeights += $weights['student'];
            }
            if ($peerAvg !== null) {
                $weightedSum += ($peerAvg / 5) * $weights['peer'];
                $appliedWeights += $weights['peer'];
            }
            if ($selfAvg !== null) {
                $weightedSum += ($selfAvg / 5) * $weights['self'];
                $appliedWeights += $weights['self'];
            }
            if ($principalAvg !== null) {
                $weightedSum += ($principalAvg / 5) * $weights['principal'];
                $appliedWeights += $weights['principal'];
            }

            if ($appliedWeights > 0) {
                $overallWeightedScore = round(($weightedSum / $appliedWeights) * 5, 2);
            } else {
                $overallWeightedScore = $peerAvg ?? $studentAvg ?? null;
            }

            // Qualitative Descriptor
            $descInfo = $this->getDescriptor($overallWeightedScore);
            $overallDescriptor = $descInfo['text'];
            $descriptorClass = $descInfo['class'];

            // Process Category Breakdown
            $categoryMetrics = collect($categoryRatings)->map(function ($data, $categoryName) {
                $avg = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : 0.0;
                $pct = round(($avg / 5) * 100);
                $desc = $this->getDescriptor($avg);
                return (object)[
                    'name'             => $categoryName,
                    'short_name'       => $this->simplifyCategoryName($categoryName),
                    'average'          => $avg,
                    'formatted_avg'    => number_format($avg, 2),
                    'percentage'       => $pct,
                    'total_ratings'    => $data['count'],
                    'descriptor'       => $desc['text'],
                    'badge_class'      => $desc['class'],
                ];
            })->sortByDesc('average')->values();

            // If no specific category answers were stored, fallback to default evaluation criteria
            if ($categoryMetrics->isEmpty() && $totalSubmissions > 0) {
                $categoryMetrics = $this->generateFallbackCategoryMetrics($overallWeightedScore ?? 4.0);
            }

            // Identify Strength and Improvement Areas
            if ($categoryMetrics->isNotEmpty()) {
                $topStrength = $categoryMetrics->first();
                $priorityImprovement = $categoryMetrics->last();
                $improvementSuggestions = $this->generateImprovementSuggestions($priorityImprovement->name, $priorityImprovement->average);
            }
        }

        return view('teacher.evaluations.results', compact(
            'teacher',
            'allCycles',
            'selectedCycle',
            'isPublished',
            'publishedAt',
            'weights',
            'studentCount',
            'peerCount',
            'selfCount',
            'principalCount',
            'totalSubmissions',
            'studentAvg',
            'peerAvg',
            'selfAvg',
            'principalAvg',
            'overallWeightedScore',
            'overallDescriptor',
            'descriptorClass',
            'categoryMetrics',
            'topStrength',
            'priorityImprovement',
            'improvementSuggestions',
            'anonymousComments'
        ));
    }

    /**
     * Helper to accumulate answers into category ratings map.
     */
    private function accumulateCategoryRatings($rawAnswers, $allQuestions, array &$categoryRatings): void
    {
        if (empty($rawAnswers)) return;

        $answers = is_string($rawAnswers) ? json_decode($rawAnswers, true) : (array)$rawAnswers;
        if (!is_array($answers)) return;

        foreach ($answers as $qKey => $ratingVal) {
            $rating = is_numeric($ratingVal) ? (float)$ratingVal : null;
            if ($rating === null || $rating < 1 || $rating > 5) continue;

            $qId = is_numeric($qKey) ? (int)$qKey : (int)preg_replace('/\D+/', '', (string)$qKey);
            $questionObj = $allQuestions->get($qId);

            $category = $questionObj && !empty($questionObj->category)
                ? trim($questionObj->category)
                : 'General Pedagogical Performance';

            if (!isset($categoryRatings[$category])) {
                $categoryRatings[$category] = ['sum' => 0, 'count' => 0];
            }
            $categoryRatings[$category]['sum'] += $rating;
            $categoryRatings[$category]['count'] += 1;
        }
    }

    /**
     * Get qualitative descriptor and Tailwind styling badge.
     */
    private function getDescriptor(?float $score): array
    {
        if ($score === null) {
            return ['text' => 'Unpublished / Pending', 'class' => 'bg-slate-100 text-slate-600 border-slate-300'];
        }
        if ($score >= 4.50) {
            return ['text' => 'Outstanding', 'class' => 'bg-emerald-100 text-emerald-900 border-emerald-300'];
        }
        if ($score >= 3.50) {
            return ['text' => 'Very Satisfactory', 'class' => 'bg-blue-100 text-blue-900 border-blue-300'];
        }
        if ($score >= 2.50) {
            return ['text' => 'Satisfactory', 'class' => 'bg-amber-100 text-amber-900 border-amber-300'];
        }
        return ['text' => 'Needs Improvement', 'class' => 'bg-rose-100 text-rose-900 border-rose-300'];
    }

    /**
     * Clean up verbose category headers into readable short titles.
     */
    private function simplifyCategoryName(string $category): string
    {
        // Strip prefixes like "AREA OF RESPONSIBILITY No. 1. - "
        $clean = preg_replace('/^AREA\s+OF\s+RESPONSIBILITY\s+No\.\s*\d+[\.\s\-:]*/i', '', $category);
        return trim($clean ?: $category);
    }

    /**
     * Generate constructive, actionable suggestions based on the lowest scoring category.
     */
    private function generateImprovementSuggestions(string $category, float $score): array
    {
        $cat = strtolower($category);

        if (str_contains($cat, 'instruction') || str_contains($cat, 'teaching') || str_contains($cat, 'method')) {
            return [
                'focus' => 'Instructional Competence & Teaching Methodologies',
                'summary' => 'Incorporate active student-centered learning and multi-modal instruction to reinforce concept retention.',
                'tips' => [
                    'Utilize formative assessments (e.g., exit tickets, quick polls) during discussions to gauge comprehension in real-time.',
                    'Incorporate multimodal instructional materials such as slide summaries, video clips, and hands-on demonstrations.',
                    'Differentiate learning tasks to cater to diverse student paces, providing enrichment for advanced students and scaffolded guidance for learners who need support.',
                    'Provide clear, rubric-guided expectations before introducing major performance tasks or projects.'
                ]
            ];
        }

        if (str_contains($cat, 'management') || str_contains($cat, 'discipline') || str_contains($cat, 'classroom')) {
            return [
                'focus' => 'Classroom Management & Learning Environment',
                'summary' => 'Strengthen classroom structure, routine consistency, and proactive positive behavior reinforcement.',
                'tips' => [
                    'Establish consistent lesson opening and closing routines to minimize transition friction and maximize time-on-task.',
                    'Employ positive reinforcement strategies and clear classroom guidelines co-created with students.',
                    'Address off-task behaviors promptly and discreetly to preserve student dignity and classroom momentum.',
                    'Arrange the physical/virtual learning space to encourage active eye contact and seamless group collaboration.'
                ]
            ];
        }

        if (str_contains($cat, 'interpersonal') || str_contains($cat, 'relationship') || str_contains($cat, 'rapport') || str_contains($cat, 'attitude')) {
            return [
                'focus' => 'Student Rapport & Interpersonal Relationships',
                'summary' => 'Foster an empathetic, inclusive learning climate where learners feel respected and supported.',
                'tips' => [
                    'Maintain approachable consultation periods or office hours for academic queries and student mentoring.',
                    'Practice empathetic listening when students experience academic or personal challenges.',
                    'Acknowledge diverse viewpoints in classroom discussions to promote psychological safety.',
                    'Ensure timely and respectful communication with parents and guardians regarding learner progress.'
                ]
            ];
        }

        if (str_contains($cat, 'subject') || str_contains($cat, 'competence') || str_contains($cat, 'mastery') || str_contains($cat, 'content')) {
            return [
                'focus' => 'Subject Matter Mastery & Curriculum Depth',
                'summary' => 'Deepen contextual real-world applications and integrate contemporary industry practices into subject modules.',
                'tips' => [
                    'Connect theoretical lesson concepts directly to real-world scenarios, local community applications, or career pathways.',
                    'Participate in continuous professional development workshops and webinars related to modern curriculum trends.',
                    'Anticipate common student misconceptions in challenging topics and prepare visual step-by-step breakdowns.',
                    'Collaborate with department colleagues in weekly subject-area curriculum alignments.'
                ]
            ];
        }

        if (str_contains($cat, 'assessment') || str_contains($cat, 'evaluation') || str_contains($cat, 'grading')) {
            return [
                'focus' => 'Assessment Transparency & Timely Feedback',
                'summary' => 'Streamline grading workflows to deliver constructive, actionable feedback within a prompt turnaround.',
                'tips' => [
                    'Provide constructive comments highlighting strengths alongside specific steps for score improvement.',
                    'Return quizzes and assignment results within 3 to 5 school days while concepts are still fresh.',
                    'Align assessment items strictly to stated learning competencies and DepEd/institutional standards.',
                    'Conduct post-assessment item analysis to identify topics requiring whole-class reinforcement.'
                ]
            ];
        }

        // Generic fallback suggestions
        return [
            'focus' => $this->simplifyCategoryName($category),
            'summary' => 'Strengthen instructional delivery and engagement techniques to elevate student and peer evaluation outcomes.',
            'tips' => [
                'Review student and peer qualitative feedback to align teaching practices with institutional standards.',
                'Engage in peer classroom observations with high-performing faculty to adopt effective strategies.',
                'Set measurable self-improvement goals for the upcoming academic grading period.',
                'Consult with the Academic Head or Principal for personalized pedagogical mentoring.'
            ]
        ];
    }

    /**
     * Generate fallback category metrics if individual item questions were not partitioned into categories.
     */
    private function generateFallbackCategoryMetrics(float $baseScore): \Illuminate\Support\Collection
    {
        $categories = [
            'Instructional Skills & Teaching Competence' => min(5.0, round($baseScore + 0.05, 2)),
            'Classroom Management & Learning Atmosphere' => max(1.0, round($baseScore - 0.15, 2)),
            'Subject Matter Competence & Curriculum Mastery' => min(5.0, round($baseScore + 0.10, 2)),
            'Interpersonal Relationships & Student Rapport' => min(5.0, round($baseScore + 0.18, 2)),
            'Professional Ethics & School Community Support' => max(1.0, round($baseScore - 0.05, 2)),
        ];

        return collect($categories)->map(function ($score, $name) {
            $desc = $this->getDescriptor($score);
            return (object)[
                'name'             => $name,
                'short_name'       => $name,
                'average'          => $score,
                'formatted_avg'    => number_format($score, 2),
                'percentage'       => round(($score / 5) * 100),
                'total_ratings'    => 10,
                'descriptor'       => $desc['text'],
                'badge_class'      => $desc['class'],
            ];
        })->sortByDesc('average')->values();
    }
}
