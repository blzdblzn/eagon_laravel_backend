<?php

namespace App\Http\Controllers\Employee;

use App\Models\Department;
use App\Models\User;
use App\Models\Employee;
use App\Http\Requests\Auth\EmployeeRequest;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class EmployeeController extends Controller
{
    public function index($user) {
    $employee = Auth::user()->employee;
    $data = [
        'employee' => $employee
    ];
    return response()->json($data);
}


    // public function profile() {
    //     $data = [
    //         'employee' => Auth::user()->employee
    //     ];
    //     return view('employee.profile')->with($data);
    // }

    public function profile() {
        
        // Find the user based on the provided token
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Invalid token.'], 401);
        }
        // Retrieve the related employee profile using the user's ID
        $employeeData = Employee::where('user_id', $user->id)->first();
    
        // Check if employee data exists
        if (!$employeeData) {
            return response()->json(['error' => 'Employee profile not found.'], 404);
        }
    
        return response()->json([
            'employee' => $employeeData
        ], 200);
    }
   
    
    public function store(EmployeeRequest $request)
    {
        // Validate incoming request
        $validatedData = $request->validated();
        
        // Create new employee record
        $employee = new Employee();
        $employee->user_id = $validatedData['user_id'];
        $employee->first_name = $validatedData['first_name'];
        $employee->last_name = $validatedData['last_name'];
        $employee->dob = $validatedData['dob'];
        $employee->sex = $validatedData['sex'];
        $employee->desg = $validatedData['desg'];
        $employee->department_id = $validatedData['department_id'];
        $employee->join_date = $validatedData['join_date'];
        $employee->salary = $validatedData['salary'];
        $employee->save();
        
        // Return response
        return response()->json([
            'success' => true,
            'message' => 'Employee registered successfully.',
            'employee' => $employee
        ], 201);
    }


public function profile_edit($employee_id) {
        $data = [
            'employee' => Employee::findOrFail($employee_id),
            'departments' => Department::all(),
            'desgs' => ['Manager', 'Assistant Manager', 'Deputy Manager', 'Clerk']
        ];
        Gate::authorize('employee-profile-access', intval($employee_id));
        return view('employee.profile-edit')->with($data);
    }

    public function profile_update(Request $request, $employee_id) {
        Gate::authorize('employee-profile-access', intval($employee_id));
        $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'photo' => 'image|nullable'
        ]);
        $employee = Employee::findOrFail($employee_id);
        $employee->first_name = $request->first_name;
        $employee->last_name = $request->last_name;
        $employee->dob = $request->dob;
        $employee->sex = $request->gender;
        $employee->join_date = $request->join_date;
        $employee->desg = $request->desg;
        $employee->department_id = $request->department_id;
        if ($request->hasFile('photo')) {
            // Deleting the old image
            if ($employee->photo != 'user.png') {
                $old_filepath = public_path(DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'employee_photos'.DIRECTORY_SEPARATOR. $employee->photo);
                if(file_exists($old_filepath)) {
                    unlink($old_filepath);
                }    
            }
            // GET FILENAME
            $filename_ext = $request->file('photo')->getClientOriginalName();
            // GET FILENAME WITHOUT EXTENSION
            $filename = pathinfo($filename_ext, PATHINFO_FILENAME);
            // GET EXTENSION
            $ext = $request->file('photo')->getClientOriginalExtension();
            //FILNAME TO STORE
            $filename_store = $filename.'_'.time().'.'.$ext;
            // UPLOAD IMAGE
            // $path = $request->file('photo')->storeAs('public'.DIRECTORY_SEPARATOR.'employee_photos', $filename_store);
            // add new file name
            $image = $request->file('photo');
            $image_resize = Image::make($image->getRealPath());              
            $image_resize->resize(300, 300);
            $image_resize->save(public_path(DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'employee_photos'.DIRECTORY_SEPARATOR.$filename_store));
            $employee->photo = $filename_store;
        }
        $employee->save();
        $request->session()->flash('success', 'Your profile has been successfully updated!');
        return redirect()->route('employee.profile');
    }
}
