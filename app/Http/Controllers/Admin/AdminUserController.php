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

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search', ''));
        $roleFilter = trim((string)$request->query('role', 'all'));
        $cols = Schema::getColumnListing('users');

        $query = User::with(['role', 'nfcCard', 'classSchedules']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search, $cols) {
                if (in_array('first_name', $cols)) $q->where('first_name', 'like', "%{$search}%");
                if (in_array('last_name', $cols)) $q->orWhere('last_name', 'like', "%{$search}%");
                if (in_array('name', $cols)) $q->orWhere('name', 'like', "%{$search}%");
                if (in_array('email', $cols)) $q->orWhere('email', 'like', "%{$search}%");
                if (in_array('id_number', $cols)) $q->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'all' && !empty($roleFilter)) {
            if ($roleFilter === 'admin') {
                $query->where(function ($q) {
                    $q->where('role_id', 1)->orWhere('role', 'admin');
                });
            } elseif ($roleFilter === 'faculty' || $roleFilter === 'teacher') {
                $query->where(function ($q) {
                    $q->where('role_id', 2)->orWhere('role', 'teacher')->orWhere('role', 'faculty');
                });
            } elseif ($roleFilter === 'student' || $roleFilter === 'students') {
                $query->where(function ($q) {
                    $q->where('role_id', 3)->orWhere('role', 'student');
                });
            } elseif ($roleFilter === 'director') {
                $query->where(function ($q) {
                    $q->where('role_id', 4)->orWhere('role', 'director');
                });
            }
        }

        // Sort alphabetically by last name and first name
        $users = $query->orderBy('last_name', 'asc')
                       ->orderBy('first_name', 'asc')
                       ->paginate(15)
                       ->withQueryString();

        $totalUsers = User::count();
        $adminCount = User::where(fn($q) => $q->where('role_id', 1)->orWhere('role', 'admin'))->count();
        $facultyCount = User::where(fn($q) => $q->where('role_id', 2)->orWhere('role', 'teacher')->orWhere('role', 'faculty'))->count();
        $studentCount = User::where(fn($q) => $q->where('role_id', 3)->orWhere('role', 'student'))->count();

        // --- Gender Demographic Counts for Charts ---
        $maleCount = User::where('role_id', 3)
            ->where(fn($q) => $q->where('gender', 'Male')->orWhere('gender', 'male'))
            ->count();

        $femaleCount = User::where('role_id', 3)
            ->where(fn($q) => $q->where('gender', 'Female')->orWhere('gender', 'female'))
            ->count();

        return view('admin.users.index', compact('users', 'totalUsers', 'adminCount', 'facultyCount', 'studentCount', 'search', 'roleFilter', 'maleCount', 'femaleCount'));
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
            (object)['id' => 4, 'name' => 'director'],
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

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'max:255', 'unique:users,email'], 
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $cols = Schema::getColumnListing('users');
            $roleString = match ($roleId) {
                1 => 'admin',
                2 => 'teacher',
                4 => 'director',
                default => 'student',
            };

            // Linisin ang grade_level para numero lang (e.g., "Grade 11" maging "11") para iwas 1366 Incorrect integer value error
            $rawGrade = $request->input('grade_level');
            $cleanGrade = $rawGrade ? preg_replace('/[^0-9]/', '', $rawGrade) : null;

            $userData = [];
            if (in_array('first_name', $cols)) $userData['first_name'] = $request->first_name;
            if (in_array('last_name', $cols)) $userData['last_name'] = $request->last_name;
            if (in_array('name', $cols)) $userData['name'] = trim($request->first_name . ' ' . $request->last_name);
            if (in_array('email', $cols)) $userData['email'] = $request->email;
            
            $userData['password'] = $request->password;
            
            if (in_array('role_id', $cols)) $userData['role_id'] = $roleId;
            if (in_array('academic_period_id', $cols)) {
                $userData['academic_period_id'] = DB::table('academic_periods')->where('is_active', 1)->value('id');
            }
            if (in_array('role', $cols)) $userData['role'] = $roleString;

            if (in_array('id_number', $cols)) $userData['id_number'] = $request->id_number;
            if (in_array('gender', $cols)) $userData['gender'] = $request->gender;
            if (in_array('phone_number', $cols)) $userData['phone_number'] = $request->phone_number;

            if (in_array('grade_level', $cols)) {
                $userData['grade_level'] = $isStudent ? $cleanGrade : null;
            }
            if (in_array('strand', $cols)) {
                $userData['strand'] = $isStudent ? $request->strand : null;
            }
            if (in_array('track', $cols)) {
                $userData['track'] = $isStudent ? ($request->strand ?? $request->track) : null;
            }
            if (in_array('section', $cols)) {
                $userData['section'] = $isStudent ? $request->section : null;
            }
            if (in_array('parent_name', $cols)) {
                $userData['parent_name'] = $isStudent ? $request->parent_name : null;
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

            return redirect()->route('admin.users.index')
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
            (object)['id' => 4, 'name' => 'director'],
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
            'email'      => ['required', 'string', 'max:255', 'unique:users,email,' . $user->id],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $request->validate($rules);

        try {
            $cols = Schema::getColumnListing('users');
            $roleString = match ($roleId) {
                1 => 'admin',
                2 => 'teacher',
                4 => 'director',
                default => 'student',
            };

            // Linisin din ang grade_level para sa update method
            $rawGrade = $request->input('grade_level');
            $cleanGrade = $rawGrade ? preg_replace('/[^0-9]/', '', $rawGrade) : null;

            if (in_array('first_name', $cols)) $user->first_name = $request->first_name;
            if (in_array('last_name', $cols)) $user->last_name = $request->last_name;
            if (in_array('name', $cols)) $user->name = trim($request->first_name . ' ' . $request->last_name);
            if (in_array('email', $cols)) $user->email = $request->email;
            if (in_array('role_id', $cols)) $user->role_id = $roleId;
            if (in_array('role', $cols)) $user->role = $roleString;
            if (in_array('id_number', $cols)) $user->id_number = $request->id_number;
            if (in_array('gender', $cols)) $user->gender = $request->gender;
            if (in_array('phone_number', $cols)) $user->phone_number = $request->phone_number;
            
            if (in_array('grade_level', $cols)) {
                $user->grade_level = ($isStudent || $isAdviser) ? $cleanGrade : null;
            }
            if (in_array('strand', $cols)) {
                $user->strand = $isStudent ? $request->strand : null;
            }
            if (in_array('track', $cols)) {
                $user->track = $isStudent ? ($request->strand ?? $request->track) : null;
            }
            if (in_array('section', $cols)) {
                $user->section = ($isStudent || $isAdviser) ? $request->section : null;
            }
            if (in_array('parent_name', $cols)) {
                $user->parent_name = $isStudent ? $request->parent_name : null;
            }
            if (in_array('parent_phone_number', $cols)) {
                $user->parent_phone_number = $isStudent ? $request->parent_phone_number : null;
            }

            if ($request->filled('password')) {
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

            return redirect()->route('admin.users.edit', $user->id)
                ->with('success', "User '{$user->first_name} {$user->last_name}' updated successfully!");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Update Error: ' . $e->getMessage()]);
        }
    }   

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'first_name'         => ['required', 'string', 'max:255'],
            'last_name'          => ['required', 'string', 'max:255'],
            'email'              => ['required', 'string', 'max:255', 'unique:users,email,' . $user->id],
            'phone_number'       => ['nullable', 'string', 'max:20'],
            'current_password'   => ['required', 'string'],
            'password'           => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->password !== $request->current_password) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match your current password.'
            ]);
        }

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