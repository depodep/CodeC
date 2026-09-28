<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\NfcCard;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $search            = trim((string)$request->query('search', ''));
        $roleFilter        = trim((string)$request->query('role', 'faculty')); // Default to faculty if unspecified
        $statusFilter      = trim((string)$request->query('status', 'all'));
        $gradeFilter       = trim((string)$request->query('grade_level', ''));
        $strandFilter      = trim((string)$request->query('strand', ''));
        $sectionFilter     = trim((string)$request->query('section', ''));
        $designationFilter = trim((string)$request->query('designation', ''));
        $selectedGradeNumber = (int) preg_replace('/\D+/', '', $gradeFilter);
        if ($selectedGradeNumber >= 7 && $selectedGradeNumber <= 10) {
            $strandFilter = '';
        }

        $cols = Schema::getColumnListing('users');
        $hasRoleCol = in_array('role', $cols);

        $query = User::with(['role', 'nfcCard', 'classSchedules.subjectRecord', 'sections.classSchedules.subjectRecord', 'sections.classSchedules.teacher']);

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search, $cols) {
                if (in_array('first_name', $cols)) $q->where('first_name', 'like', "%{$search}%");
                if (in_array('last_name', $cols)) $q->orWhere('last_name', 'like', "%{$search}%");
                if (in_array('name', $cols)) $q->orWhere('name', 'like', "%{$search}%");
                if (in_array('email', $cols)) $q->orWhere('email', 'like', "%{$search}%");
                if (in_array('username', $cols)) $q->orWhere('username', 'like', "%{$search}%");
                if (in_array('id_number', $cols)) $q->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Role Tab Filter
        if ($roleFilter !== 'all' && !empty($roleFilter)) {
            if ($roleFilter === 'admin') {
                $query->where(function ($q) use ($hasRoleCol) {
                    $q->where('role_id', 1);
                    if ($hasRoleCol) $q->orWhere('role', 'admin');
                });
            } elseif ($roleFilter === 'faculty' || $roleFilter === 'teacher') {
                $query->where(function ($q) use ($hasRoleCol) {
                    $q->where('role_id', 2);
                    if ($hasRoleCol) $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
                });
            } elseif ($roleFilter === 'student' || $roleFilter === 'students') {
                $query->where(function ($q) use ($hasRoleCol) {
                    $q->where('role_id', 3);
                    if ($hasRoleCol) $q->orWhere('role', 'student');
                });
            } elseif ($roleFilter === 'director' || $roleFilter === 'management') {
                $query->where(function ($q) use ($hasRoleCol) {
                    $q->where('role_id', 4);
                    if ($hasRoleCol) $q->orWhere('role', 'director')->orWhere('role', 'management');
                });
            }
        }

        // Account Status Filter
        if ($statusFilter !== 'all' && in_array('is_active', $cols)) {
            if ($statusFilter === 'active') {
                $query->where('is_active', 1);
            } elseif ($statusFilter === 'inactive') {
                $query->where(function($q) {
                    $q->where('is_active', 0)->orWhereNull('is_active');
                });
            }
        }

        // Academic Placement Filters (For Students / Faculty)
        if (!empty($gradeFilter) && in_array('grade_level', $cols)) {
            $query->where('grade_level', $gradeFilter);
        }

        if (!empty($strandFilter) && in_array('strand', $cols)) {
            $query->where('strand', $strandFilter);
        }

        if (!empty($sectionFilter) && in_array('section', $cols)) {
            $query->where('section', $sectionFilter);
        }

        if (!empty($designationFilter)) {
            if ($designationFilter === 'adviser') {
                $query->whereNotNull('section')->where('section', '!=', '');
            } elseif ($designationFilter === 'subject_teacher') {
                $query->where(function($q) {
                    $q->whereNull('section')->orWhere('section', '');
                });
            }
        }

        // Sort alphabetically by last name and first name
        $users = $query->orderBy('last_name', 'asc')
                       ->orderBy('first_name', 'asc')
                       ->paginate(15)
                       ->withQueryString()
                       ->through(function ($user) {
                           if ($user->role_id == 3 && $user->sections->isNotEmpty()) {
                               $allSchedules = collect();
                               foreach ($user->sections as $section) {
                                   if ($section->classSchedules) {
                                       $allSchedules = $allSchedules->concat($section->classSchedules);
                                   }
                               }
                               // Bind it to the property expected by Alpine
                               $user->setRelation('classSchedules', $allSchedules->unique('id'));
                           }
                           return $user;
                       });

        // Account Counts
        $totalUsers = User::count();
        $adminCount = User::where(function ($q) use ($hasRoleCol) {
            $q->where('role_id', 1);
            if ($hasRoleCol) $q->orWhere('role', 'admin');
        })->count();

        $facultyCount = User::where(function ($q) use ($hasRoleCol) {
            $q->where('role_id', 2);
            if ($hasRoleCol) $q->orWhere('role', 'teacher')->orWhere('role', 'faculty');
        })->count();

        $studentCount = User::where(function ($q) use ($hasRoleCol) {
            $q->where('role_id', 3);
            if ($hasRoleCol) $q->orWhere('role', 'student');
        })->count();

        $managementCount = User::where(function ($q) use ($hasRoleCol) {
            $q->where('role_id', 4);
            if ($hasRoleCol) $q->orWhere('role', 'director')->orWhere('role', 'management');
        })->count();
        $directorCount = $managementCount;

        $activeUserCount = Schema::hasColumn('users', 'is_active')
            ? User::where('is_active', 1)->count()
            : $totalUsers;
        $inactiveUserCount = max(0, $totalUsers - $activeUserCount);
        $studentsWithSectionCount = User::where('role_id', 3)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->count();
        $studentsWithoutSectionCount = max(0, $studentCount - $studentsWithSectionCount);

        // --- Gender Demographic Counts for Charts ---
        $maleCount = User::where('role_id', 3)
            ->where(fn($q) => $q->where('gender', 'Male')->orWhere('gender', 'male'))
            ->count();

        $femaleCount = User::where('role_id', 3)
            ->where(fn($q) => $q->where('gender', 'Female')->orWhere('gender', 'female'))
            ->count();

        // Populate dynamic filter options from academic_sections & users
        $sections = Schema::hasTable('academic_sections') ? DB::table('academic_sections')->get() : collect();
        $gradeLevels = $sections->pluck('grade_level')
            ->concat(User::whereNotNull('grade_level')->pluck('grade_level'))
            ->map(fn($v) => ucwords(strtolower(trim((string)$v))))
            ->unique()
            ->filter()
            ->values();

        $strands = $sections->pluck('strand')
            ->concat(User::whereNotNull('strand')->pluck('strand'))
            ->map(fn($v) => strtoupper(trim((string)$v)))
            ->unique()
            ->filter()
            ->values();

        $matchingSections = $sections->when($selectedGradeNumber >= 7 && $selectedGradeNumber <= 12, function ($items) use ($selectedGradeNumber) {
            return $items->filter(fn($section) => (int) preg_replace('/\D+/', '', (string) $section->grade_level) === $selectedGradeNumber);
        });
        $sectionList = $matchingSections->pluck('section_name')
            ->concat(User::whereNotNull('section')
                ->when($selectedGradeNumber >= 7 && $selectedGradeNumber <= 12, fn($query) => $query->whereRaw("CAST(REGEXP_REPLACE(grade_level, '[^0-9]', '') AS UNSIGNED) = ?", [$selectedGradeNumber]))
                ->pluck('section'))
            ->map(fn($v) => trim((string)$v))
            ->unique()
            ->filter()
            ->values();

        return view('admin.users.index', compact(
            'users', 
            'totalUsers', 
            'adminCount', 
            'facultyCount', 
            'studentCount',
            'managementCount',
            'directorCount',
            'activeUserCount',
            'inactiveUserCount',
            'studentsWithSectionCount',
            'studentsWithoutSectionCount',
            'search', 
            'roleFilter', 
            'statusFilter',
            'gradeFilter',
            'strandFilter',
            'sectionFilter',
            'designationFilter',
            'maleCount', 
            'femaleCount',
            'gradeLevels',
            'strands',
            'sectionList',
            'sections'
        ));
    }

    public function export(Request $request)
    {
        $type = $request->query('type', 'all');
        $filename = "siatrack_users_{$type}_" . date('Y-m-d') . ".xlsx";

        return Excel::download(new UsersExport($type), $filename);
    }

    public function create()
    {
        $roles = Schema::hasTable('roles') ? Role::all() : collect([
            (object)['id' => 3, 'name' => 'student'],
            (object)['id' => 2, 'name' => 'teacher'],
            (object)['id' => 4, 'name' => 'management'],
        ]);

        $sections = Schema::hasTable('academic_sections') ? DB::table('academic_sections')->get() : collect();

        $gradeLevels = $sections->pluck('grade_level')
            ->map(fn($v) => ucwords(strtolower(trim($v))))
            ->unique()
            ->filter()
            ->values();

        $strands = $sections->pluck('strand')
            ->map(fn($v) => strtoupper(trim($v)))
            ->unique()
            ->filter()
            ->values();

        return view('admin.users.create', compact('roles', 'sections', 'gradeLevels', 'strands'));
    }

    public function store(Request $request)
    {
        $roleId = (int)$request->input('role_id', 3);
        $isStudent = ($roleId === 3);

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email'      => $isStudent ? ['nullable', 'string', 'max:255'] : ['required', 'email', 'max:255', 'unique:users,email'],
            'username'   => $isStudent ? ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'] : ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'id_number'  => $isStudent
                ? ['required', 'digits:12', Rule::unique('users', 'id_number')]
                : ['required', 'string', 'max:50', Rule::unique('users', 'id_number')],
            'student_id' => ['required', 'string', 'max:50'],
            'phone_number' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
            'gender' => $isStudent ? ['required', 'string', 'in:Male,Female'] : ['nullable', 'string'],
            'grade_level' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
            'section' => $isStudent ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'strand' => $isStudent && !$this->isJuniorHigh($request->input('grade_level'))
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],
            'parent_name' => $isStudent ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'parent_relationship' => $isStudent ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'parent_phone_number' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ];

        $request->validate($rules, [
            'id_number.unique' => 'This LRN / Student ID or Employee ID has already been registered.',
            'id_number.digits' => 'The LRN must contain exactly 12 digits.',
            'email.unique'     => 'This Email Address has already been registered.',
            'username.unique'  => 'This username has already been registered.',
        ]);

        try {
            $cols = Schema::getColumnListing('users');
            $roleString = match ($roleId) {
                1 => 'admin',
                2 => 'teacher',
                4 => 'management',
                default => 'student',
            };

            // Linisin ang grade_level para numero lang (e.g., "Grade 11" maging "11")
            $rawGrade = $request->input('grade_level');
            $cleanGrade = $rawGrade ? preg_replace('/[^0-9]/', '', $rawGrade) : null;
            $isJHS = in_array((int)$cleanGrade, [7, 8, 9, 10]);

            $userData = [];
            if (in_array('first_name', $cols)) $userData['first_name'] = $request->first_name;
            if (in_array('middle_name', $cols)) $userData['middle_name'] = $request->middle_name;
            if (in_array('last_name', $cols)) $userData['last_name'] = $request->last_name;
            if (in_array('name', $cols)) $userData['name'] = trim($request->first_name . ($request->middle_name ? ' ' . $request->middle_name : '') . ' ' . $request->last_name);
            if (in_array('email', $cols)) $userData['email'] = $isStudent ? null : strtolower(trim((string) $request->email));
            if (in_array('username', $cols)) {
                $userData['username'] = $isStudent
                    ? $this->studentUsername($request->input('username'), $request->id_number, $request->first_name)
                    : ($request->filled('username') ? strtolower(trim($request->username)) : null);
            }
            
            $userData['password'] = $request->password;
            
            if (in_array('role_id', $cols)) $userData['role_id'] = $roleId;
            if (in_array('is_principal', $cols)) $userData['is_principal'] = $roleId === 4 && $request->boolean('is_principal');
            if (in_array('academic_period_id', $cols)) {
                $userData['academic_period_id'] = DB::table('academic_periods')->where('is_active', 1)->value('id');
            }
            if (in_array('role', $cols)) $userData['role'] = $roleString;

            if (in_array('id_number', $cols)) $userData['id_number'] = $request->id_number;
            if (in_array('student_id', $cols)) $userData['student_id'] = $request->student_id;
            if (in_array('gender', $cols)) $userData['gender'] = $request->gender;
            if (in_array('phone_number', $cols)) $userData['phone_number'] = $request->phone_number;

            $teacherType = $request->input('teacher_type');
            $isAdviser = ($roleId === 2 && $teacherType === 'Adviser');

            if (in_array('grade_level', $cols)) {
                $userData['grade_level'] = ($isStudent || $isAdviser) ? $cleanGrade : null;
            }
            // JHS (Grade 7-10) has NO Strand! Only SHS (Grade 11-12) has Strand.
            if (in_array('strand', $cols)) {
                $userData['strand'] = ($isStudent && !$isJHS) ? $request->strand : null;
            }
            if (in_array('track', $cols)) {
                $userData['track'] = ($isStudent && !$isJHS) ? ($request->strand ?? $request->track) : null;
            }
            if (in_array('section', $cols)) {
                if ($isStudent || $isAdviser) {
                    $userData['section'] = $request->section;
                } elseif ($roleId === 4 || $roleId === 1) {
                    $userData['section'] = $request->input('department') ?? $request->input('section') ?? 'Executive Management';
                } else {
                    $userData['section'] = null;
                }
            }
            if (in_array('parent_name', $cols)) {
                $userData['parent_name'] = $isStudent ? $request->parent_name : null;
            }
            if (in_array('parent_relationship', $cols)) {
                $userData['parent_relationship'] = $isStudent ? $request->parent_relationship : null;
            }
            if (in_array('parent_phone_number', $cols)) {
                $userData['parent_phone_number'] = $isStudent ? $request->parent_phone_number : null;
            }

            $user = User::create($userData);

            if ($isStudent && $request->filled('nfc_tag_id') && Schema::hasTable('nfc_cards')) {
                NfcCard::updateOrCreate(
                    ['user_id' => $user->id],
                    ['tag_id' => strtoupper(trim($request->nfc_tag_id))]
                );
            }

            $redirectRole = match($roleId) { 2 => 'faculty', 3 => 'student', 4 => 'management', default => 'faculty' };
            return redirect()->route('admin.users.index', ['role' => $redirectRole])
                ->with('success', "User '{$request->first_name} {$request->last_name}' successfully added!");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $user = User::with('nfcCard')->findOrFail($id);
        
        $roles = Schema::hasTable('roles') ? Role::all() : collect([
            (object)['id' => 3, 'name' => 'student'],
            (object)['id' => 2, 'name' => 'teacher'],
            (object)['id' => 4, 'name' => 'management'],
        ]);

        $sections = Schema::hasTable('academic_sections') ? DB::table('academic_sections')->get() : collect();

        $gradeLevels = $sections->pluck('grade_level')
            ->map(fn($v) => ucwords(strtolower(trim($v))))
            ->unique()
            ->filter()
            ->values();

        $strands = $sections->pluck('strand')
            ->map(fn($v) => strtoupper(trim($v)))
            ->unique()
            ->filter()
            ->values();

        return view('admin.users.edit', compact('user', 'roles', 'sections', 'gradeLevels', 'strands'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $roleId = (int)$request->input('role_id', $user->role_id ?? 3);
        $isStudent = ($roleId === 3);
        $isTeacher = ($roleId === 2);
        
        $teacherType = $request->input('teacher_type');
        $isAdviser = ($isTeacher && $teacherType === 'Adviser');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => $isStudent ? ['nullable', 'string', 'max:255'] : ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username'   => $isStudent ? ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)] : ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'id_number'  => $isStudent
                ? ['required', 'digits:12', Rule::unique('users', 'id_number')->ignore($user->id)]
                : ['required', 'string', 'max:50', Rule::unique('users', 'id_number')->ignore($user->id)],
            'student_id' => ['required', 'string', 'max:50'],
            'phone_number' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
            'gender' => $isStudent ? ['required', 'string', 'in:Male,Female'] : ['nullable', 'string'],
            'grade_level' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
            'section' => $isStudent ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'strand' => $isStudent && !$this->isJuniorHigh($request->input('grade_level'))
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],
            'parent_name' => $isStudent ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'parent_relationship' => $isStudent ? ['required', 'string', 'max:50'] : ['nullable', 'string', 'max:50'],
            'parent_phone_number' => $isStudent ? ['required', 'string', 'max:20'] : ['nullable', 'string', 'max:20'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $request->validate($rules, [
            'id_number.unique' => 'This LRN / Student ID or Employee ID has already been registered to another account.',
            'email.unique'     => 'This Email Address has already been registered to another account.',
            'id_number.digits' => 'The LRN must contain exactly 12 digits.',
            'username.unique'  => 'This username has already been registered to another account.',
        ]);

        try {
            $cols = Schema::getColumnListing('users');
            $roleString = match ($roleId) {
                1 => 'admin',
                2 => 'teacher',
                4 => 'management',
                default => 'student',
            };

            // Linisin din ang grade_level para sa update method
            $rawGrade = $request->input('grade_level');
            $cleanGrade = $rawGrade ? preg_replace('/[^0-9]/', '', $rawGrade) : null;
            $isJHS = in_array((int)$cleanGrade, [7, 8, 9, 10]);

            if (in_array('first_name', $cols)) $user->first_name = $request->first_name;
            if (in_array('middle_name', $cols)) $user->middle_name = $request->middle_name;
            if (in_array('last_name', $cols)) $user->last_name = $request->last_name;
            if (in_array('name', $cols)) $user->name = trim($request->first_name . ($request->middle_name ? ' ' . $request->middle_name : '') . ' ' . $request->last_name);
            if (in_array('email', $cols)) $user->email = $isStudent ? null : strtolower(trim((string) $request->email));
            if (in_array('username', $cols)) {
                $user->username = $isStudent
                    ? $this->studentUsername($request->input('username'), $request->id_number, $request->first_name, $user->id)
                    : ($request->filled('username') ? strtolower(trim($request->username)) : null);
            }
            if (in_array('role_id', $cols)) $user->role_id = $roleId;
            if (in_array('is_principal', $cols)) $user->is_principal = $roleId === 4 && $request->boolean('is_principal');
            if (in_array('role', $cols)) $user->role = $roleString;
            if (in_array('id_number', $cols)) $user->id_number = $request->id_number;
            if (in_array('student_id', $cols)) $user->student_id = $request->student_id;
            if (in_array('gender', $cols)) $user->gender = $request->gender;
            if (in_array('phone_number', $cols)) $user->phone_number = $request->phone_number;
            
            if (in_array('grade_level', $cols)) {
                $user->grade_level = ($isStudent || $isAdviser) ? $cleanGrade : null;
            }

            // JHS (Grade 7-10) has NO Strand! Only SHS (Grade 11-12) has Strand.
            if (in_array('strand', $cols)) {
                $user->strand = ($isStudent && !$isJHS) ? $request->strand : null;
            }
            if (in_array('track', $cols)) {
                $user->track = ($isStudent && !$isJHS) ? ($request->strand ?? $request->track) : null;
            }
            if (in_array('section', $cols)) {
                if ($isStudent || $isAdviser) {
                    $user->section = $request->section;
                } elseif ($roleId === 4 || $roleId === 1) {
                    $user->section = $request->input('department') ?? $request->input('section') ?? 'Executive Management';
                } else {
                    $user->section = null;
                }
            }
            if (in_array('parent_name', $cols)) {
                $user->parent_name = $isStudent ? $request->parent_name : null;
            }
            if (in_array('parent_relationship', $cols)) {
                $user->parent_relationship = $isStudent ? $request->parent_relationship : null;
            }
            if (in_array('parent_phone_number', $cols)) {
                $user->parent_phone_number = $isStudent ? $request->parent_phone_number : null;
            }

            if ($request->boolean('reset_to_default_password')) {
                $defaultPass = match($roleId) {
                    2 => 'siafaculty@123',
                    4 => 'siamanagement@123',
                    default => 'onesia@123',
                };
                $user->password = $defaultPass;
            } elseif ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->save();

            if (Schema::hasTable('nfc_cards')) {
                if (!$isStudent || !$request->filled('nfc_tag_id')) {
                    NfcCard::where('user_id', $user->id)->delete();
                } else {
                    NfcCard::updateOrCreate(
                        ['user_id' => $user->id],
                        ['tag_id' => strtoupper(trim($request->nfc_tag_id))]
                    );
                }
            }

            $redirectRole = match($roleId) { 2 => 'faculty', 3 => 'student', 4 => 'management', default => 'faculty' };
            return redirect()->route('admin.users.index', ['role' => $redirectRole])
                ->with('success', "User '{$user->first_name} {$user->last_name}' updated successfully!");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Update Error: ' . $e->getMessage()]);
        }
    }   

    private function studentUsername(?string $requested, ?string $lrn, ?string $firstName, ?int $ignoreId = null): string
    {
        $base = trim((string) $requested);
        if ($base === '') {
            $base = Str::lower(preg_replace('/[^a-z0-9]/i', '', (string) $lrn)
                . preg_replace('/[^a-z0-9]/i', '', (string) $firstName));
        }
        $base = Str::lower(preg_replace('/[^a-z0-9_-]/i', '', $base));
        $base = $base !== '' ? $base : 'student';
        $username = $base;
        $suffix = 2;

        while (User::where('username', $username)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $username = $base . $suffix++;
        }

        return $username;
    }

    private function isJuniorHigh(?string $grade): bool
    {
        return in_array((int) preg_replace('/[^0-9]/', '', (string) $grade), [7, 8, 9, 10], true);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'first_name'         => ['required', 'string', 'max:255'],
            'last_name'          => ['required', 'string', 'max:255'],
            'email'              => ['required', 'string', 'max:255', 'unique:users,email,' . $user->id],
            'phone_number'       => ['nullable', 'string', 'max:20'],
            'password'           => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $cols = Schema::getColumnListing('users');

        if (in_array('first_name', $cols)) $user->first_name = $request->first_name;
        if (in_array('last_name', $cols)) $user->last_name = $request->last_name;
        if (in_array('name', $cols)) $user->name = trim($request->first_name . ' ' . $request->last_name);
        if (in_array('email', $cols)) $user->email = $request->email;

        if (in_array('phone_number', $cols)) {
            $user->phone_number = $request->phone_number;
        } elseif (in_array('contact_number', $cols)) {
            $user->contact_number = $request->phone_number;
        }

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();

        return back()->with('profile_success', 'Administrator profile updated successfully!');
    }

    public function toggleStatus($id)
    {
        if (auth()->id() == (int)$id) {
            return back()->with('error', 'You cannot disable your own active administrator account.');
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Account for '{$user->first_name} {$user->last_name}' successfully {$statusText}.");
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $request->password;
        $user->save();

        return back()->with('success', "Password for '{$user->first_name} {$user->last_name}' successfully reset.");
    }

    public function destroy($id)
    {
        if (auth()->id() == (int)$id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own active administrator account.');
        }

        $user = User::findOrFail($id);
        $name = "{$user->first_name} {$user->last_name}";
        
        if (Schema::hasTable('nfc_cards')) {
            NfcCard::where('user_id', $user->id)->delete();
        }
        
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User '{$name}' successfully removed!");
    }
}