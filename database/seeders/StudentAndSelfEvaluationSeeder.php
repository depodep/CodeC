<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentAndSelfEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('evaluation_forms') || !Schema::hasTable('evaluation_questions')) {
            $this->command?->warn('Evaluation form tables are not available. Run migrations first.');
            return;
        }

        $this->seedStudentForm();
        $this->seedSelfForm();
        $this->command?->info('Student and Self Evaluation forms replaced with the supplied institutional rubrics.');
    }

    private function seedStudentForm(): void
    {
        $sections = [
            'A. MASTERY OF SUBJECT MATTER (KAHUSAYAN SA PAKSANG ARALIN)' => [
                'Discusses/Elaborates/Explains the lesson thoroughly without directly reading from books.',
                'Provides adequate and relevant examples and demonstrations to illustrate concepts and skills in the subject matter.',
                'Cites, relates, ties up lessons with other disciplines/subjects, when applicable.',
                'Answers students’ questions/clarifications clearly.',
                'Discusses considerable coverage of topics per session within the learning capabilities of students.',
            ],
            'B. COMMUNICATION SKILLS (KASANAYAN SA PAKIKIPAG-UGNAY)' => [
                'Communicates in clear, correct and coherent language that is suited to the student’s level of understanding.',
                'Shifts to another language/the vernacular WHEN NECESSARY for clearer communication.',
                'Uses language that inspires students to listen.',
                'Speaks at appropriate speed and volume.',
                'Exhibits appropriate facial expressions and gestures and maintain eye contact with students when speaking.',
            ],
            'C. CLASSROOM MANAGEMENT (PAMAMAHALA SA SILID-ARALAN)' => [
                'Capable of maintaining classroom discipline.',
                'Begins the class on time and does not dismiss before time.',
                'Provides an environment that is pleasant and conducive to learning.',
                'Holds the attention/interest of students and is alert to respond to students’ reactions.',
                'Encourages student participation and interaction.',
            ],
            'D. TEACHING METHODOLOGY AND EVALUATING TECHNIQUES (PAMAMARAAN NG PAGTUTURO PAGSUSUKAT)' => [
                'Uses appropriate teaching strategies, aids, devices and/or technology to support instruction.',
                'Asks relevant questions that bring about judgment and critical thinking and distributes them fairly.',
                'Relates lessons to current situations and integrates values.',
                'Gives at least three quizzes every grading period, written assignments, and recitations.',
                'Recognizes student classroom participation.',
            ],
            'E. TEACHER PROFESSIONAL AND PERSONAL QUALITIES (MGA KATANGIANG PROFESYONAL AT PERSONAL)' => [
                'Always present for class in his/her prescribed school uniform.',
                'Respectable and dignified in his/her actions and words.',
                'Observes professional ethics in dealing with his/her students.',
                'Makes himself/herself available and ready for student consultation.',
                'Treats students fairly.',
            ],
        ];

        $this->replaceForm(
            'student',
            'Faculty Evaluation Scale / Panukatan sa Pagtataya ng FakultI',
            'To the student: This evaluation is designed to help improve instruction for you as well as that of your teacher. Your honest and objective rating for each item is important. Your identity and evaluation will be treated with confidentiality. Rate each item from 1 to 5 using the scale provided. You may write comments and general observations.',
            [
                ['value' => '5', 'label' => '5 - Outstanding'],
                ['value' => '4', 'label' => '4 - Very Satisfactory'],
                ['value' => '3', 'label' => '3 - Satisfactory'],
                ['value' => '2', 'label' => '2 - Fairly Satisfactory'],
                ['value' => '1', 'label' => '1 - Needs Improvement'],
            ],
            $sections,
            'likert'
        );
    }

    private function seedSelfForm(): void
    {
        $areas = [
            'AREA OF RESPONSIBILITY No. 1. - TEACHING SKILLS' => [
                'I create and maintain an atmosphere for learning.' => [
                    'I encourage pupils to express and examine their ideas, opinions and values.', 'I attempt to develop empathy among the members of the class.', 'I encourage a reasonable measure of humor in my class.', 'I encourage students with praise, commendation and constructive criticism.',
                ],
                'I provide a motivational environment for my students.' => [
                    'I approach my lessons and the class with enthusiasm.', 'I am conscious that certain aspects of teacher performance such as drama and tonality of voice affect student motivation.', 'I make use of desirable digressions and discussions on topics of student interest and current events.', 'I encourage students to develop the attitude that a job worth doing is worth doing well.',
                ],
                'I maintain a judicious balance between teacher-centered and student-centered activities.' => ['I endeavor to involve every student in the activity of each class.', 'I avoid excessive “teacher-talk.”'],
                'I use effective questioning techniques.' => ['I seldom have to interpret my questions or give additional information in order to elicit satisfactory responses.', 'The types of questions I ask require students to use a variety of cognitive processes in answering.', 'I use methods that effectively spread questions throughout the class.', 'I accept answers in such a way as to encourage further student participation.'],
                'I use techniques that make clear the purpose and content of each lesson.' => ['I use summaries, reviews and overviews to ensure that students are able to place units in perspective.', 'I emphasize clearly the important points in a lesson.', 'I ensure that an adequate summary is made at the end of each class or unit of work.'],
            ],
            'AREA OF RESPONSIBILITY No. 2. - TEACHING STRATEGIES' => [
                'I use varied and effective methods of presentation appropriate to the lesson content.' => ['In planning my lessons, consideration is given to relating my strategy to the objectives of the lesson.', 'I make use of Socratic questioning, group discussions, laboratory techniques, panels, demonstrations, lectures, role playing, team teaching, independent study, debates and simulation games where suitable.', 'I use audio visual aids and illustrative materials where available and appropriate.'],
                'I provide written and oral assignments requiring analytical and critical thinking.' => ['I recognize the necessity to individualize assignments.', 'I use assignment sheets and programmed learning materials when and where appropriate.', 'I use problem solving techniques where appropriate.', 'My assignments require students to comprehend ideas, apply these ideas, analyze, synthesize and evaluate information rather than simply memorize and reproduce facts.'],
                'I evaluate effectively, thereby improving both teaching and learning.' => ['I use student achievement as one measure of my teaching effectiveness.', 'Tests are used for both diagnosis of student problems and evaluation of their progress.', 'The evaluation methods which I use place emphasis on the growth of the individual toward specific goals and objectives.', 'The results of evaluation are used to determine the suitability of my objectives in planning further instruction.', 'My testing procedures are constantly modified and improved.', 'At the end of the year, I give students an opportunity to evaluate the program by means of constructive criticism.'],
                'I utilize community resources to enrich the classroom program.' => ['I invite, as guests of the school, members of the community who have expertise and/or special experience.', 'I make use of the environment of the school or area to enrich the regular classroom program, always ensuring that the objectives of each field trip have been clearly formulated and are understood.'],
            ],
            'AREA OF RESPONSIBILITY No. 3. - CLASSROOM MANAGEMENT' => [
                'My classroom procedures are designed to develop a positive learning environment.' => ['Each student is aware of the standards of behavior I expect to be followed in my classroom.', 'I encourage each student to develop self-discipline.', 'My disciplinary procedures are based on respect for the rights of others.', 'I avoid destructive criticism, ridicule and sarcasm and minimize the use of fear as a motivator.', 'I set and maintain a high standard of decent and courteous language.'],
                'I have an effective method for dealing with clerical matters.' => ['In addition to procedures outlined by the school or department, I have developed effective methods for distributing instructional materials and for recording student attendance and marks.', 'I keep accurate records of administrative matters and am prompt in replying to office requests.', 'I use school equipment in such a way as to give full consideration to other staff members.'],
            ],
            'AREA OF RESPONSIBILITY No. 4. - SUBJECT COMPETENCE AND PROFESSIONAL GROWTH' => [
                'I strive to upgrade my professional competence.' => ['Within the past year, I have participated in activities designed to improve myself and the educational system, such as additional university courses, subject councils, workshop, federation offices and committees.', 'I attempt to broaden my perspective through professional study, research, reading, writing, travel, and try to enrich my teaching through the experience gained.'],
                'I make use of available means of evaluation to improve my teaching.' => ['I am receptive to the suggestions of my colleagues.', 'I take part in inter-visitation programs with a view to exchanging ideas.'],
                'I take an active part in continuing curriculum development.' => ['I have attained a working knowledge of the new K to 12 BEC.', 'Through discussions with colleagues and through reading professional literature I am aware of curriculum innovations in my subject area.', 'I have been involved in the planning and updating of curriculum guides/maps in my subject area.', 'I evaluate the effectiveness of the modules that I teach with a sensitivity for student interest and relevance to the modern scene.'],
                'I recognize the major objectives to be achieved in my subject area and work towards their attainment.' => ['I have participated in staff and department discussions regarding philosophy and objectives.', 'I have established objectives for each course that I teach and they are consistent with the overall objectives of the department.', 'I question critically the methods, procedures and materials employed in terms of their value in achieving the objectives of the program.'],
            ],
            'AREA OF RESPONSIBILITY No. 5. - INTERPERSONAL RELATIONSHIPS' => [
                'I am consistently fair and impartial with students.' => ['I respect the dignity of each young person.', 'I respect the students’ point of view even though I may disagree with it.', 'I criticize in a discreet and private manner, concentrating on correcting the improper behavior.', 'I try to ensure that any rewards and punishments used are appropriate to the situation.'],
                'I recognize my responsibilities in helping students to mature socially and to achieve self-realization.' => ['I try to build self-confidence in each student.', 'I provide support and encouragement when students experience disappointment and failure.', 'I make it clear that I am concerned with habits, attitudes and values.', 'I try to understand the special needs and interests of each of my students.', 'I try to be APPROACHABLE: a person who is available with a sympathetic ear when needed.'],
                'I recognize that my attitude and efficiency in my work has an effect on other staff members.' => ['I share school equipment, facilities and ideas willingly.', 'I am considerate of the workload and feelings of secretarial, custodial and paraprofessional staff.', 'I conscientiously avoid action which could inconvenience others, such as detaining students at the conclusion of a class.'],
                'I try to promote a positive climate in the school.' => ['I make genuine effort to meet and help new staff members.', 'I participate in staff, social, and recreational activities.', 'I am discreet in discussing problems of a personal nature regarding students and staff.', 'I smile at all staff members once a week, even if it hurts!'],
                'I make use of my contacts with parents and other concerned adults to promote confidence and goodwill towards the school program and staff.' => ['I notify parents well in advance of student out-of-school activities.', 'I recognize the inter-dependence of the school and the parents in child development, and ensure that parents are informed of situations requiring special attention.', 'In my public statements, I present my school and the teaching profession in a positive light.'],
            ],
            'AREA OF RESPONSIBILITY No. 6. - CONTRIBUTION TO THE TOTAL SCHOOL EFFORT' => [
                'In addition to my regular duties as a classroom teacher, I accept willingly extra duties within the school.' => ['I participate in committee work in the school such as programs, assemblies, etc.', 'My colleagues know by my attitude that I am prepared to assist whenever necessary.', 'I make my time and talents available beyond the classroom helping students through extra academic assistance, coaching or managing of teams, clubs or other activities.'],
                'My concern for my students extends beyond the teacher-student relationship.' => ['I encourage the students to take action to improve the school environment, to respect the school property and that of others.', 'In all contacts with students in the halls, on the playing field or wherever, I encourage goodwill towards the school.', 'I show concern for the total welfare of my students rather than achievement in a particular subject.', 'I take corrective action outside of my classroom when necessary.'],
                'I accept administrative decisions in good faith and make use of the proper channels to suggest modifications to those decisions with which I disagree.' => ['I discourage harmful gossip and chronic complaining in the staff room.', 'I support the policies of the school and ensure that my students understand and adhere to these policies.', 'I bring student reaction to school policy to the attention of the administration, and suggest modification where applicable.'],
            ],
        ];

        $this->replaceForm(
            'self',
            'Teacher’s Self-Evaluation',
            'Each of the six areas of responsibility has been subdivided into major statements and substatements. Evaluate yourself on each substatement using the scale: 4 Excellent, 3 Good, 2 Fair, 1 Poor. Complete the summary chart and action summary for self-improvement.',
            [
                ['value' => '4', 'label' => '4 - Excellent'],
                ['value' => '3', 'label' => '3 - Good'],
                ['value' => '2', 'label' => '2 - Fair'],
                ['value' => '1', 'label' => '1 - Poor'],
            ],
            $areas,
            'likert'
        );
    }

    private function replaceForm(string $type, string $title, string $instructions, array $scales, array $sections, string $questionType): void
    {
        DB::transaction(function () use ($type, $title, $instructions, $scales, $sections, $questionType): void {
            $now = now();
            DB::table('evaluation_questions')->where('form_type', $type)->delete();
            DB::table('evaluation_forms')->where('form_type', $type)->delete();

            $formId = DB::table('evaluation_forms')->insertGetId([
                'form_type' => $type, 'title' => $title, 'instructions' => $instructions,
                'version' => 1, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);

            if (Schema::hasTable('evaluation_rating_scales')) {
                foreach ($scales as $index => $scale) {
                    DB::table('evaluation_rating_scales')->insert([
                        'form_id' => $formId, 'value' => $scale['value'], 'label' => $scale['label'],
                        'order_num' => $index + 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }

            $sectionOrder = 0;
            $questionOrder = 0;
            foreach ($sections as $sectionTitle => $items) {
                $sectionOrder++;
                $sectionId = Schema::hasTable('evaluation_sections') ? DB::table('evaluation_sections')->insertGetId([
                    'form_id' => $formId, 'title' => $sectionTitle, 'description' => null,
                    'order_num' => $sectionOrder, 'created_at' => $now, 'updated_at' => $now,
                ]) : null;

                $subheadings = is_array(reset($items)) ? $items : ['' => $items];
                $subOrder = 0;
                foreach ($subheadings as $subheadingTitle => $questions) {
                    $subOrder++;
                    $subheadingId = null;
                    if ($subheadingTitle !== '' && Schema::hasTable('evaluation_subheadings') && $sectionId) {
                        $subheadingId = DB::table('evaluation_subheadings')->insertGetId([
                            'section_id' => $sectionId, 'title' => $subheadingTitle, 'description' => null,
                            'order_num' => $subOrder, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }

                    foreach ($questions as $question) {
                        $questionOrder++;
                        $data = [
                            'form_type' => $type, 'category' => $sectionTitle, 'question' => $question,
                            'order_num' => $questionOrder, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                        ];
                        if (Schema::hasColumn('evaluation_questions', 'form_id')) $data['form_id'] = $formId;
                        if ($sectionId && Schema::hasColumn('evaluation_questions', 'section_id')) $data['section_id'] = $sectionId;
                        if ($subheadingId && Schema::hasColumn('evaluation_questions', 'subheading_id')) $data['subheading_id'] = $subheadingId;
                        if (Schema::hasColumn('evaluation_questions', 'type')) $data['type'] = $questionType;
                        if (Schema::hasColumn('evaluation_questions', 'is_required')) $data['is_required'] = 1;
                        DB::table('evaluation_questions')->insert($data);
                    }
                }
            }
        });
    }
}