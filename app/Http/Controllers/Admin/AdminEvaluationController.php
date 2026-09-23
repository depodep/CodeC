<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class AdminEvaluationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $sectionFilter = $request->query('section');

        // Fetch active academic period or fallback
        $activePeriod = Schema::hasTable('academic_periods')
            ? DB::table('academic_periods')->where('is_active', 1)->first()
            : null;

        $activeSchoolYear = $activePeriod->school_year ?? '2026-2027';

        // Fetch sections for filters if table exists
        $sections = Schema::hasTable('academic_sections')
            ? DB::table('academic_sections')->pluck('section_name')->toArray()
            : [];

        // KPI metrics
        $totalFaculty = User::where('role_id', 2)->count(); // Role 2 = Faculty/Teacher
        $totalEvaluations = Schema::hasTable('peer_evaluations') ? DB::table('peer_evaluations')->count() : 0;
        
        $averageScore = Schema::hasTable('peer_evaluations') 
            ? DB::table('peer_evaluations')->avg('average_score') ?? 0.0 
            : 0.0;

        $totalStudents = User::where('role_id', 3)->count(); // Role 3 = Student
        $evalProgress = $totalStudents > 0 ? min(100, round(($totalEvaluations / max(1, $totalStudents * $totalFaculty)) * 100)) : 0;

        return view('admin.evaluations.index', compact(
            'activeSchoolYear',
            'sections',
            'totalEvaluations',
            'totalFaculty',
            'averageScore',
            'evalProgress'
        ));
    }

    public function seedOfficialQuestions($type = null)
    {
        $now = now();
        
        $typesToSeed = $type ? [$type] : ['principal', 'peer', 'student', 'self'];

        foreach ($typesToSeed as $t) {
            DB::table('evaluation_questions')->where('form_type', $t)->delete();
            $records = [];

            if ($t === 'principal') {
                $items = [
                    ['I. Instructional Competence (50% Weight)', 'formulates objectives of lesson plan'],
                    ['I. Instructional Competence (50% Weight)', 'prepares appropriate teaching aids'],
                    ['I. Instructional Competence (50% Weight)', 'knowledge in teaching methods, strategies and techniques'],
                    ['I. Instructional Competence (50% Weight)', 'utilizes the art of questioning to develop higher level of thinking'],
                    ['I. Instructional Competence (50% Weight)', 'conveys ideas clearly'],
                    ['I. Instructional Competence (50% Weight)', 'ensures students\' participation'],
                    ['I. Instructional Competence (50% Weight)', 'recognizes individual differences'],
                    ['I. Instructional Competence (50% Weight)', 'shows enthusiasm in teaching'],
                    ['I. Instructional Competence (50% Weight)', 'shows mastery of the subject matter'],
                    ['I. Instructional Competence (50% Weight)', 'diagnoses learners\' needs'],
                    ['I. Instructional Competence (50% Weight)', 'knowledge in constructing test questions'],
                    ['I. Instructional Competence (50% Weight)', 'maintains classroom conducive to learning'],
                    ['I. Instructional Competence (50% Weight)', 'prepares and utilizes learners\' records effectively'],
                    ['I. Instructional Competence (50% Weight)', 'encourages parents\' involvement in school programs and activities'],
                    ['I. Instructional Competence (50% Weight)', 'exercises parental responsibility to the learners'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Obedience to institutional policies and lawful directives'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Honesty & integrity in all professional dealings'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Dedication & commitment to teaching mission'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Initiative and self-direction in task fulfillment'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Courtesy and politeness to peers, students, and visitors'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Human relations and interpersonal rapport'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Leadership and positive influence in the school community'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Stress management and emotional stability'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Cooperation with administrative programs and committees'],
                    ['II. Professional & Personal Characteristics (30% Weight)', 'Proper attire and professional grooming'],
                    ['III. Participation & Attendance (20% Weight)', 'Punctuality in class reporting and school events'],
                    ['III. Participation & Attendance (20% Weight)', 'Regularity of attendance and prompt submission of school forms']
                ];
                foreach ($items as $i => $item) {
                    $records[] = ['form_type' => 'principal', 'category' => $item[0], 'question' => $item[1], 'order_num' => $i + 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
                }
            } elseif ($t === 'peer') {
                $items = [
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
                    'In sum, tries to exhibit exemplary behavior of a true professional.'
                ];
                foreach ($items as $i => $q) {
                    $records[] = ['form_type' => 'peer', 'category' => 'Faculty Peer Collaboration & Professionalism', 'question' => $q, 'order_num' => $i + 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
                }
            } elseif ($t === 'student') {
                $items = [
                    ['A. Mastery of Subject Matter', 'Discusses/Elaborates/Explains the lesson thoroughly without directly reading from books.'],
                    ['A. Mastery of Subject Matter', 'Provides adequate and relevant examples and demonstrations to illustrate concepts and skills.'],
                    ['A. Mastery of Subject Matter', 'Cites, relates, ties up lessons with other disciplines/subjects, when applicable.'],
                    ['A. Mastery of Subject Matter', 'Answers students\' questions/clarifications clearly.'],
                    ['A. Mastery of Subject Matter', 'Discusses considerable coverage of topics per session within learning capabilities.'],
                    ['B. Communication Skills', 'Communicates in clear, correct and coherent language suited to student level.'],
                    ['B. Communication Skills', 'Shifts to vernacular WHEN NECESSARY for clearer communication.'],
                    ['B. Communication Skills', 'Uses language that inspires students to listen.'],
                    ['B. Communication Skills', 'Speaks at appropriate speed and volume.'],
                    ['B. Communication Skills', 'Exhibits appropriate facial expressions, gestures, and eye contact.'],
                    ['C. Classroom Management', 'Capable of maintaining classroom discipline.'],
                    ['C. Classroom Management', 'Begins the class on time and does not dismiss before time.'],
                    ['C. Classroom Management', 'Provides an environment pleasant and conducive to learning.'],
                    ['C. Classroom Management', 'Holds the attention/interest of students and responds to reactions.'],
                    ['C. Classroom Management', 'Encourages student participation and interaction.'],
                    ['D. Teaching Methodology', 'Uses appropriate teaching strategies, aids, devices and/or technology.'],
                    ['D. Teaching Methodology', 'Asks relevant questions that bring about critical thinking.'],
                    ['D. Teaching Methodology', 'Relates lessons to current situations and integrates values.'],
                    ['D. Teaching Methodology', 'Gives quizzes, written assignments, and recitations regularly.'],
                    ['D. Teaching Methodology', 'Recognizes student classroom participation.'],
                    ['E. Teacher Professional & Personal Qualities', 'Always present for class in prescribed school uniform.'],
                    ['E. Teacher Professional & Personal Qualities', 'Respectable and dignified in actions and words.'],
                    ['E. Teacher Professional & Personal Qualities', 'Observes professional ethics in dealing with students.'],
                    ['E. Teacher Professional & Personal Qualities', 'Makes himself/herself available for student consultation.'],
                    ['E. Teacher Professional & Personal Qualities', 'Treats students fairly.']
                ];
                foreach ($items as $i => $item) {
                    $records[] = ['form_type' => 'student', 'category' => $item[0], 'question' => $item[1], 'order_num' => $i + 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
                }
            } elseif ($t === 'self') {
                $items = [
                    ['I. Teaching Performance & Delivery', 'I regularly reflect on my teaching effectiveness and instructional outcomes.'],
                    ['I. Teaching Performance & Delivery', 'I design lesson plans that clearly target learning competencies and student needs.'],
                    ['I. Teaching Performance & Delivery', 'I employ interactive teaching methods and relevant educational technology.'],
                    ['II. Professional Growth & Development', 'I actively engage in professional development activities, seminars, and training.'],
                    ['II. Professional Growth & Development', 'I continuously update my knowledge and skills in my field of specialization.'],
                    ['III. Professional Conduct & Ethics', 'I consistently adhere to institutional policies, core values, and ethical standards.'],
                    ['III. Professional Conduct & Ethics', 'I maintain positive, collaborative, and professional rapport with peers and superiors.'],
                    ['III. Professional Conduct & Ethics', 'I demonstrate punctuality, accountability, and dedication to institutional duties.']
                ];
                foreach ($items as $i => $item) {
                    $records[] = ['form_type' => 'self', 'category' => $item[0], 'question' => $item[1], 'order_num' => $i + 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
                }
            }

            if (!empty($records)) {
                DB::table('evaluation_questions')->insert($records);
            }
        }
    }

    public function periods(Request $request)
    {
        if (!Schema::hasTable('evaluation_questions')) {
            Schema::create('evaluation_questions', function ($table) {
                $table->id();
                $table->string('form_type')->default('principal');
                $table->string('category');
                $table->text('question');
                $table->integer('order_num')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            $this->seedOfficialQuestions();
        }

        $activeCycle = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->where('status', 'active')->where('is_active', 1)->orderBy('id', 'desc')->first()
            : null;

        $selectedType = $request->query('type', 'principal');

        $count = DB::table('evaluation_questions')->where('form_type', $selectedType)->count();
        if ($count < 3) {
            $this->seedOfficialQuestions($selectedType);
        }

        $allQuestions = DB::table('evaluation_questions')
            ->where('form_type', $selectedType)
            ->orderBy('order_num', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $groupedQuestions = $allQuestions->groupBy('category');

        $allCategories = DB::table('evaluation_questions')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        $counts = [
            'principal' => DB::table('evaluation_questions')->where('form_type', 'principal')->count(),
            'peer'      => DB::table('evaluation_questions')->where('form_type', 'peer')->count(),
            'student'   => DB::table('evaluation_questions')->where('form_type', 'student')->count(),
            'self'      => DB::table('evaluation_questions')->where('form_type', 'self')->count(),
        ];

        return view('admin.evaluations.periods', compact(
            'activeCycle', 
            'allQuestions', 
            'groupedQuestions', 
            'allCategories', 
            'selectedType', 
            'counts'
        ));
    }

    public function editForm($type)
    {
        $validTypes = ['principal', 'peer', 'student', 'self'];
        if (!in_array($type, $validTypes)) {
            $type = 'principal';
        }

        $formTypeNames = [
            'principal' => "Principal's Evaluation",
            'peer'      => "Peer Evaluation",
            'student'   => "Student Evaluation",
            'self'      => "Self Evaluation",
        ];
        $formTypeName = $formTypeNames[$type];

        // Ensure evaluation_forms table and default form row
        $form = DB::table('evaluation_forms')->where('form_type', $type)->where('is_active', 1)->first();
        if (!$form) {
            $formId = DB::table('evaluation_forms')->insertGetId([
                'form_type'    => $type,
                'title'        => $formTypeName . ' of Teaching Performance',
                'instructions' => 'Please evaluate the faculty member based on your experience using the rating scale provided below.',
                'version'      => 1,
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $form = DB::table('evaluation_forms')->where('id', $formId)->first();
        }

        // Ensure default rating scales for this form
        $scaleCount = DB::table('evaluation_rating_scales')->where('form_id', $form->id)->count();
        if ($scaleCount === 0) {
            $defaultScales = [
                ['value' => '5', 'label' => 'Always Manifested', 'order_num' => 1],
                ['value' => '4', 'label' => 'Often Manifested', 'order_num' => 2],
                ['value' => '3', 'label' => 'Sometimes Manifested', 'order_num' => 3],
                ['value' => '2', 'label' => 'Seldom Manifested', 'order_num' => 4],
                ['value' => '1', 'label' => 'Never Manifested', 'order_num' => 5],
                ['value' => 'N/A', 'label' => 'Not Applicable', 'order_num' => 6],
            ];
            foreach ($defaultScales as $s) {
                DB::table('evaluation_rating_scales')->insert([
                    'form_id'    => $form->id,
                    'value'      => $s['value'],
                    'label'      => $s['label'],
                    'order_num'  => $s['order_num'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $ratingScales = DB::table('evaluation_rating_scales')->where('form_id', $form->id)->orderBy('order_num', 'asc')->get();

        // Migrate existing questions into sections if no sections exist yet
        $sectionCount = DB::table('evaluation_sections')->where('form_id', $form->id)->count();
        if ($sectionCount === 0) {
            $existingQuestions = DB::table('evaluation_questions')->where('form_type', $type)->get();
            if ($existingQuestions->count() < 3) {
                $this->seedOfficialQuestions($type);
                $existingQuestions = DB::table('evaluation_questions')->where('form_type', $type)->get();
            }

            $grouped = $existingQuestions->groupBy('category');
            $secOrder = 1;
            foreach ($grouped as $catName => $qList) {
                $secId = DB::table('evaluation_sections')->insertGetId([
                    'form_id'     => $form->id,
                    'title'       => $catName,
                    'description' => null,
                    'order_num'   => $secOrder++,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $qOrder = 1;
                foreach ($qList as $qItem) {
                    DB::table('evaluation_questions')->where('id', $qItem->id)->update([
                        'form_id'    => $form->id,
                        'section_id' => $secId,
                        'order_num'  => $qOrder++,
                        'type'       => $qItem->type ?? 'likert',
                    ]);
                }
            }
        }

        // Retrieve structured sections, subheadings, and questions
        $sections = DB::table('evaluation_sections')
            ->where('form_id', $form->id)
            ->orderBy('order_num', 'asc')
            ->get()
            ->map(function ($sec) use ($form) {
                $sec->subheadings = DB::table('evaluation_subheadings')
                    ->where('section_id', $sec->id)
                    ->orderBy('order_num', 'asc')
                    ->get()
                    ->map(function ($sub) use ($form) {
                        $sub->questions = DB::table('evaluation_questions')
                            ->where('subheading_id', $sub->id)
                            ->orderBy('order_num', 'asc')
                            ->get();
                        return $sub;
                    });

                $sec->direct_questions = DB::table('evaluation_questions')
                    ->where('section_id', $sec->id)
                    ->whereNull('subheading_id')
                    ->orderBy('order_num', 'asc')
                    ->get();

                return $sec;
            });

        return view('admin.evaluations.edit-form', compact(
            'form', 'type', 'formTypeName', 'ratingScales', 'sections'
        ));
    }

    public function saveForm(Request $request, $type)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'form_data'    => 'required|string',
        ]);

        $payload = json_decode($request->form_data, true);
        if (!$payload) {
            return back()->with('error', 'Invalid form data payload format.');
        }

        DB::transaction(function () use ($request, $type, $payload) {
            $form = DB::table('evaluation_forms')->where('form_type', $type)->where('is_active', 1)->first();
            if (!$form) {
                $formId = DB::table('evaluation_forms')->insertGetId([
                    'form_type'    => $type,
                    'title'        => $request->title,
                    'instructions' => $request->instructions,
                    'version'      => 1,
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $formId = $form->id;
                DB::table('evaluation_forms')->where('id', $formId)->update([
                    'title'        => $request->title,
                    'instructions' => $request->instructions,
                    'version'      => $form->version + 1,
                    'updated_at'   => now(),
                ]);
            }

            // Save rating scales
            DB::table('evaluation_rating_scales')->where('form_id', $formId)->delete();
            if (isset($payload['rating_scales']) && is_array($payload['rating_scales'])) {
                foreach ($payload['rating_scales'] as $sIdx => $scale) {
                    if (!empty($scale['label'])) {
                        DB::table('evaluation_rating_scales')->insert([
                            'form_id'    => $formId,
                            'value'      => $scale['value'] ?? ($sIdx + 1),
                            'label'      => trim($scale['label']),
                            'order_num'  => $sIdx + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Save Sections, Subheadings, Questions
            $oldSecIds = DB::table('evaluation_sections')->where('form_id', $formId)->pluck('id')->toArray();
            DB::table('evaluation_subheadings')->whereIn('section_id', $oldSecIds)->delete();
            DB::table('evaluation_sections')->where('form_id', $formId)->delete();

            if (isset($payload['sections']) && is_array($payload['sections'])) {
                foreach ($payload['sections'] as $secIdx => $secData) {
                    if (empty($secData['title'])) continue;

                    $secId = DB::table('evaluation_sections')->insertGetId([
                        'form_id'     => $formId,
                        'title'       => trim($secData['title']),
                        'description' => $secData['description'] ?? null,
                        'order_num'   => $secIdx + 1,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    // Direct Questions under Section
                    if (isset($secData['direct_questions']) && is_array($secData['direct_questions'])) {
                        foreach ($secData['direct_questions'] as $qIdx => $qData) {
                            if (empty($qData['question'])) continue;
                            DB::table('evaluation_questions')->insert([
                                'form_id'       => $formId,
                                'section_id'    => $secId,
                                'subheading_id' => null,
                                'form_type'     => $type,
                                'category'      => trim($secData['title']),
                                'question'      => trim($qData['question']),
                                'type'          => $qData['type'] ?? 'likert',
                                'options'       => isset($qData['options']) ? json_encode($qData['options']) : null,
                                'is_required'   => isset($qData['is_required']) ? (bool)$qData['is_required'] : true,
                                'order_num'     => $qIdx + 1,
                                'is_active'     => 1,
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ]);
                        }
                    }

                    // Subheadings under Section
                    if (isset($secData['subheadings']) && is_array($secData['subheadings'])) {
                        foreach ($secData['subheadings'] as $subIdx => $subData) {
                            if (empty($subData['title'])) continue;

                            $subId = DB::table('evaluation_subheadings')->insertGetId([
                                'section_id'  => $secId,
                                'title'       => trim($subData['title']),
                                'description' => $subData['description'] ?? null,
                                'order_num'   => $subIdx + 1,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);

                            if (isset($subData['questions']) && is_array($subData['questions'])) {
                                foreach ($subData['questions'] as $qIdx => $qData) {
                                    if (empty($qData['question'])) continue;
                                    DB::table('evaluation_questions')->insert([
                                        'form_id'       => $formId,
                                        'section_id'    => $secId,
                                        'subheading_id' => $subId,
                                        'form_type'     => $type,
                                        'category'      => trim($secData['title']) . ' - ' . trim($subData['title']),
                                        'question'      => trim($qData['question']),
                                        'type'          => $qData['type'] ?? 'likert',
                                        'options'       => isset($qData['options']) ? json_encode($qData['options']) : null,
                                        'is_required'   => isset($qData['is_required']) ? (bool)$qData['is_required'] : true,
                                        'order_num'     => $qIdx + 1,
                                        'is_active'     => 1,
                                        'created_at'    => now(),
                                        'updated_at'    => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        });

        $formTypeNames = [
            'principal' => "Principal's Evaluation",
            'peer'      => "Peer Evaluation",
            'student'   => "Student Evaluation",
            'self'      => "Self Evaluation",
        ];
        $formTypeName = $formTypeNames[$type] ?? 'Evaluation';

        return back()->with('success', '✓ Evaluation form for ' . $formTypeName . ' updated successfully.');
    }

    public function resetQuestions(Request $request)
    {
        $type = $request->input('form_type', 'principal');
        $this->seedOfficialQuestions($type);
        return back()->with('success', 'Official SIA evaluation rubric has been restored successfully!');
    }

    public function startCycle(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'school_year' => 'required|string|max:50',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        if (Schema::hasTable('evaluation_cycles')) {
            // Deactivate previous active cycles
            DB::table('evaluation_cycles')
                ->where('status', 'active')
                ->update(['status' => 'completed', 'is_active' => 0, 'updated_at' => now()]);

            // Create new evaluation cycle
            $cycleId = DB::table('evaluation_cycles')->insertGetId([
                'name'        => trim($request->name),
                'school_year' => trim($request->school_year),
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
                'status'      => 'active',
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            Cache::put('evaluations_open', true);
            Cache::put('active_evaluation_cycle_id', $cycleId);
        }

        return back()->with('success', 'Evaluation "' . $request->name . '" for SY ' . $request->school_year . ' has been started successfully!');
    }

    public function endCycle($id)
    {
        if (Schema::hasTable('evaluation_cycles')) {
            $cycle = DB::table('evaluation_cycles')->where('id', $id)->first();
            if ($cycle) {
                DB::table('evaluation_cycles')
                    ->where('id', $id)
                    ->update([
                        'status'     => 'completed',
                        'is_active'  => 0,
                        'updated_at' => now()
                    ]);

                Cache::put('evaluations_open', false);
                Cache::forget('active_evaluation_cycle_id');

                return back()->with('success', 'Evaluation "' . $cycle->name . '" has been ended and permanently saved to Evaluation History!');
            }
        }

        return back()->with('error', 'Evaluation cycle not found.');
    }

    public function history(Request $request)
    {
        $search     = trim((string) $request->query('search'));
        $schoolYear = trim((string) $request->query('school_year'));
        $status     = trim((string) $request->query('status'));
        $formType   = trim((string) $request->query('form_type'));
        $section    = trim((string) $request->query('section'));

        $query = DB::table('evaluation_cycles');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($schoolYear) {
            $query->where('school_year', $schoolYear);
        }
        if ($status) {
            $query->where('status', strtolower($status));
        }

        $cycles = $query->orderBy('created_at', 'desc')->get()->map(function($c) {
            $peerCount = 0;
            $selfCount = 0;
            $studentCount = 0;
            $principalCount = 0;

            if (Schema::hasTable('peer_evaluations')) {
                $peerCount = DB::table('peer_evaluations')->where('evaluation_cycle_id', $c->id)->count();
            }
            if (Schema::hasTable('self_evaluations')) {
                $selfCount = DB::table('self_evaluations')->where('evaluation_cycle_id', $c->id)->count();
            }
            if (Schema::hasTable('evaluation_submissions')) {
                $studentCount = DB::table('evaluation_submissions')->where('evaluation_cycle_id', $c->id)->where('form_type', 'student')->count();
                $principalCount = DB::table('evaluation_submissions')->where('evaluation_cycle_id', $c->id)->where('form_type', 'principal')->count();
            }

            $c->peer_count = $peerCount;
            $c->self_count = $selfCount;
            $c->student_count = $studentCount;
            $c->principal_count = $principalCount;
            $c->response_count = $peerCount + $selfCount + $studentCount + $principalCount;
            return $c;
        });

        $schoolYears = DB::table('evaluation_cycles')->distinct()->pluck('school_year')->filter()->toArray();
        $sections = Schema::hasTable('academic_sections') ? DB::table('academic_sections')->pluck('section_name')->filter()->toArray() : [];

        return view('admin.evaluations.history', compact(
            'cycles', 'schoolYears', 'sections', 'search', 'schoolYear', 'status', 'formType', 'section'
        ));
    }

    public function toggleTeacherPublish(Request $request)
    {
        $request->validate([
            'evaluation_cycle_id' => 'required|integer',
            'teacher_id'          => 'required|integer',
            'is_published'        => 'required|boolean'
        ]);

        $cycleId   = $request->evaluation_cycle_id;
        $teacherId = $request->teacher_id;
        $published = $request->is_published;

        if (Schema::hasTable('teacher_evaluation_publications')) {
            $existing = DB::table('teacher_evaluation_publications')
                ->where('evaluation_cycle_id', $cycleId)
                ->where('teacher_id', $teacherId)
                ->first();

            if ($existing) {
                DB::table('teacher_evaluation_publications')
                    ->where('id', $existing->id)
                    ->update([
                        'is_published' => $published ? 1 : 0,
                        'published_at' => $published ? now() : null,
                        'updated_at'   => now()
                    ]);
            } else {
                DB::table('teacher_evaluation_publications')->insert([
                    'evaluation_cycle_id' => $cycleId,
                    'teacher_id'          => $teacherId,
                    'is_published'        => $published ? 1 : 0,
                    'published_at'        => $published ? now() : null,
                    'created_at'          => now(),
                    'updated_at'          => now()
                ]);
            }
        }

        return back()->with('success', 'Teacher result publishing status updated successfully!');
    }

    public function savePeriod(Request $request)
    {
        return $this->startCycle($request);
    }

    public function toggleStatus(Request $request)
    {
        $currentStatus = Cache::get('evaluations_open', false);
        $newStatus = !$currentStatus;

        Cache::put('evaluations_open', $newStatus);

        return back()->with('success', 'Evaluation window status successfully toggled to ' . ($newStatus ? 'OPEN' : 'CLOSED') . '!');
    }

    public function storeQuestion(Request $request)
    {
        $request->validate([
            'form_type' => 'required|string|in:principal,peer,student,self',
            'category'  => 'required|string|max:255',
            'question'  => 'required|string',
        ]);

        $maxOrder = DB::table('evaluation_questions')->where('form_type', $request->form_type)->max('order_num') ?? 0;

        DB::table('evaluation_questions')->insert([
            'form_type'  => $request->form_type,
            'category'   => trim($request->category),
            'question'   => trim($request->question),
            'order_num'  => $maxOrder + 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Indicator added successfully to ' . strtoupper($request->form_type) . ' rubric!');
    }

    public function updateQuestion(Request $request, $id)
    {
        $request->validate([
            'form_type' => 'sometimes|string|in:principal,peer,student,self',
            'category'  => 'required|string|max:255',
            'question'  => 'required|string',
        ]);

        $updateData = [
            'category'   => trim($request->category),
            'question'   => trim($request->question),
            'updated_at' => now(),
        ];

        if ($request->has('form_type')) {
            $updateData['form_type'] = $request->form_type;
        }

        DB::table('evaluation_questions')->where('id', $id)->update($updateData);

        return back()->with('success', 'Indicator updated successfully!');
    }

    public function destroyQuestion($id)
    {
        DB::table('evaluation_questions')->where('id', $id)->delete();
        return back()->with('success', 'Question deleted successfully!');
    }

    public function downloadTemplate(Request $request)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="evaluation_rubrics_template.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['form_type', 'category', 'question']);

            $samples = [
                ['principal', 'I. Instructional Competence (50% Weight)', 'Formulates objectives of lesson plan accurately.'],
                ['principal', 'II. Professional & Personal Characteristics (30% Weight)', 'Obedience to institutional policies and lawful directives.'],
                ['peer', 'Faculty Peer Collaboration & Professionalism', 'Shares relevant up-to-date ideas during faculty meetings.'],
                ['peer', 'Faculty Peer Collaboration & Professionalism', 'Volunteers to participate in committee work.'],
                ['student', 'A. Mastery of Subject Matter', 'Discusses/Elaborates/Explains the lesson thoroughly.'],
                ['student', 'B. Communication Skills', 'Communicates in clear, correct and coherent language.'],
                ['self', 'I. Teaching Performance & Delivery', 'I regularly reflect on my teaching effectiveness and instructional outcomes.'],
                ['self', 'II. Professional Growth & Development', 'I actively engage in professional development activities.']
            ];

            foreach ($samples as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importQuestions(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:2048',
            'import_mode' => 'required|in:add,replace',
        ]);

        $file = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to open uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty or invalid.');
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        $formTypeIdx = array_search('form_type', $header);
        $categoryIdx = array_search('category', $header);
        $questionIdx = array_search('question', $header);

        if ($categoryIdx === false || $questionIdx === false) {
            fclose($handle);
            return back()->with('error', 'CSV must contain "category" and "question" columns (and optional "form_type").');
        }

        $mode = $request->import_mode;
        $rows = [];
        $validFormTypes = ['principal', 'peer', 'student', 'self'];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $rawFormType = ($formTypeIdx !== false && isset($row[$formTypeIdx])) ? strtolower(trim($row[$formTypeIdx])) : 'principal';
            $formType = in_array($rawFormType, $validFormTypes) ? $rawFormType : 'principal';
            $category = isset($row[$categoryIdx]) ? trim($row[$categoryIdx]) : '';
            $question = isset($row[$questionIdx]) ? trim($row[$questionIdx]) : '';

            if (!empty($category) && !empty($question)) {
                $rows[] = [
                    'form_type' => $formType,
                    'category'  => $category,
                    'question'  => $question,
                ];
            }
        }
        fclose($handle);

        if (empty($rows)) {
            return back()->with('error', 'No valid indicator questions found in CSV file.');
        }

        $importedCount = 0;
        $skippedCount = 0;
        $now = now();

        if ($mode === 'replace') {
            $targetTypes = array_unique(array_column($rows, 'form_type'));
            DB::table('evaluation_questions')->whereIn('form_type', $targetTypes)->delete();
        }

        foreach ($rows as $item) {
            if ($mode === 'add') {
                $exists = DB::table('evaluation_questions')
                    ->where('form_type', $item['form_type'])
                    ->where('question', $item['question'])
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }
            }

            $maxOrder = DB::table('evaluation_questions')->where('form_type', $item['form_type'])->max('order_num') ?? 0;

            DB::table('evaluation_questions')->insert([
                'form_type'  => $item['form_type'],
                'category'   => $item['category'],
                'question'   => $item['question'],
                'order_num'  => $maxOrder + 1,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $importedCount++;
        }

        $msg = "Successfully imported {$importedCount} indicator questions.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} duplicate items skipped).";
        }

        return back()->with('success', $msg);
    }

    public function results(Request $request)
    {
        $search  = trim((string) $request->query('search'));
        $cycleId = $request->query('cycle_id');

        $allCycles = Schema::hasTable('evaluation_cycles')
            ? DB::table('evaluation_cycles')->orderBy('id', 'desc')->get()
            : collect([]);

        $selectedCycle = null;
        if ($cycleId) {
            $selectedCycle = $allCycles->firstWhere('id', (int)$cycleId);
        }
        if (!$selectedCycle) {
            $selectedCycle = $allCycles->firstWhere('status', 'active') ?? $allCycles->first();
        }

        $query = User::where('role_id', 2);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $allFaculty = $query->orderBy('last_name', 'asc')->get();

        $publications = ($selectedCycle && Schema::hasTable('teacher_evaluation_publications'))
            ? DB::table('teacher_evaluation_publications')->where('evaluation_cycle_id', $selectedCycle->id)->get()->keyBy('teacher_id')
            : collect([]);

        $totalSubmissions = 0;
        $facultyMetrics = $allFaculty->map(function($teacher) use (&$totalSubmissions, $selectedCycle, $publications) {
            $peerQuery = Schema::hasTable('peer_evaluations')
                ? DB::table('peer_evaluations')->where('evaluatee_id', $teacher->id)
                : null;

            if ($peerQuery && $selectedCycle) {
                $peerQuery->where('evaluation_cycle_id', $selectedCycle->id);
            }

            $peerEvals = $peerQuery ? $peerQuery->get() : collect([]);

            $peerCount = $peerEvals->count();
            $totalSubmissions += $peerCount;
            $peerAvg = $peerCount > 0 ? round($peerEvals->avg('average_score'), 2) : null;
            $comments = $peerEvals->whereNotNull('comments')->pluck('comments')->filter()->values();

            $descriptor = 'Pending';
            $badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';

            if ($peerAvg !== null) {
                if ($peerAvg >= 4.50) {
                    $descriptor = 'Outstanding';
                    $badgeClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                } elseif ($peerAvg >= 3.50) {
                    $descriptor = 'Very Satisfactory';
                    $badgeClass = 'bg-blue-50 text-blue-800 border-blue-200';
                } elseif ($peerAvg >= 2.50) {
                    $descriptor = 'Satisfactory';
                    $badgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                } else {
                    $descriptor = 'Needs Improvement';
                    $badgeClass = 'bg-red-50 text-red-800 border-red-200';
                }
            }

            $pub = $publications->get($teacher->id);
            $isPublished = $pub ? (bool)$pub->is_published : false;

            return (object) [
                'id'           => $teacher->id,
                'name'         => 'Prof. ' . $teacher->first_name . ' ' . $teacher->last_name,
                'short_name'   => $teacher->last_name . ', ' . substr($teacher->first_name, 0, 1) . '.',
                'first_name'   => $teacher->first_name,
                'last_name'    => $teacher->last_name,
                'email'        => $teacher->email,
                'id_number'    => $teacher->id_number ?? 'N/A',
                'peer_count'   => $peerCount,
                'peer_avg'     => $peerAvg ? (float)$peerAvg : null,
                'descriptor'   => $descriptor,
                'badge_class'  => $badgeClass,
                'comments'     => $comments,
                'is_published' => $isPublished,
            ];
        });

        $totalFaculty = $facultyMetrics->count();
        $totalEvaluated = $facultyMetrics->filter(fn($f) => $f->peer_count > 0)->count();
        $completionRate = $totalFaculty > 0 ? round(($totalEvaluated / $totalFaculty) * 100, 1) : 0;
        
        $scoredTeachers = $facultyMetrics->filter(fn($f) => $f->peer_avg !== null);
        $overallInstMean = $scoredTeachers->count() > 0 ? number_format($scoredTeachers->avg('peer_avg'), 2) : '0.00';
        $highestScore = $scoredTeachers->count() > 0 ? number_format($scoredTeachers->max('peer_avg'), 2) : '--';
        $lowestScore = $scoredTeachers->count() > 0 ? number_format($scoredTeachers->min('peer_avg'), 2) : '--';

        $distOutstanding = $facultyMetrics->filter(fn($f) => $f->peer_avg >= 4.50)->count();
        $distVerySat = $facultyMetrics->filter(fn($f) => $f->peer_avg >= 3.50 && $f->peer_avg < 4.50)->count();
        $distSat = $facultyMetrics->filter(fn($f) => $f->peer_avg >= 2.50 && $f->peer_avg < 3.50)->count();
        $distNeedsImp = $facultyMetrics->filter(fn($f) => $f->peer_avg !== null && $f->peer_avg < 2.50)->count();
        $distPending = $totalFaculty - $totalEvaluated;

        $barLabels = $facultyMetrics->pluck('short_name')->toArray();
        $barScores = $facultyMetrics->map(fn($f) => $f->peer_avg ?? 0)->toArray();
        $distributionValues = [$distOutstanding, $distVerySat, $distSat, $distNeedsImp, $distPending];

        return view('admin.evaluations.results', compact(
            'selectedCycle', 'allCycles', 'facultyMetrics', 'totalFaculty', 'totalEvaluated', 'completionRate',
            'overallInstMean', 'highestScore', 'lowestScore', 'totalSubmissions',
            'barLabels', 'barScores', 'distributionValues', 'search'
        ));
    }
}