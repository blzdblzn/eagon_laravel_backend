<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Rules\DateRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Location;

class AttendanceController extends Controller
{
    private $sharedVariable;
    
    public function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        $this->sharedVariable = $ip;
                        return response()->json($ip);
                    }
                }
            }
        }
    }
    
    public function location(Request $request) {
        $response = Http::get('https://nominatim.openstreetmap.org/reverse?format=geojson&lat='.$request->lat.'&lon='.$request->lon);
        $display_name = $response->json()['properties']['display_name'];
        return response()->json($display_name);
    }

    public function create() {
        try {
            $employee = Auth::User();
            if (!$employee) {
                throw new \Exception('Unauthorized access. User not found.');
            }
    
            $employeeData = Employee::where('user_id', $employee->id)->first();
            if (!$employeeData) {
                throw new \Exception('Employee data not found.');
            }
    
            $data = [
                'employee' => $employee,
                'attendance' => null,
                'registered_attendance' => null
            ];
    
            $last_attendance = $employee->attendance()->latest()->first();
            if ($last_attendance !== null) {
                if ($last_attendance->created_at->format('d') == now()->format('d')) {
                    $data['attendance'] = $last_attendance;
                    if ($last_attendance->registered) {
                        $data['registered_attendance'] = 'yes';
                }
            }
        }
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function store(Request $request, $employee_id) {
        $attendance = new Attendance([
                'employee_id' => $employee_id,
                'entry_ip' => $request->ip(),
                'entry_location' => $request->entry_location
        ]);
        $attendance->save();
        $request->session()->flash('success', 'Attendance entry successfully logged');
        return response()->json(['success' => 'Attendance entry successfully logged']);
    }

    public function tester(){
        $testing = Auth::user();
        $data = [
            'employee' => $testing,
            'theip' => $this->sharedVariable
        ];
        return response()->json($data);
    }

    public function update(Request $request, $attendance_id) {
        $attendance = Attendance::findOrFail($attendance_id);
        $attendance->exit_ip = $request->ip();
        $attendance->exit_location = $request->exit_location;
        $attendance->registered = 'yes';
        $attendance->save();
        $request->session()->flash('success', 'Attendance exit successfully logged');
        return response()->json(['success' => 'Attendance exit successfully logged']);
    }

    public function getUserIP()
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        return response()->json($ip);
    }

    public function index() {
        $employee = Auth::user()->employee;
        $attendances = $employee->attendance;
        $filter = false;
        if(request()->all()) {
            $this->validate(request(), ['date_range' => new DateRange]);
            if($attendances) {
                [$start, $end] = explode(' - ', request()->input('date_range'));
                $start = Carbon::parse($start);
                $end = Carbon::parse($end)->addDay();
                $filtered_attendances = $this->attendanceOfRange($attendances, $start, $end);
                $leaves = $this->leavesOfRange($employee->leave, $start, $end);
                $holidays = $this->holidaysOfRange(Holiday::all(), $start, $end);
                $attendances = collect();
                $count = $filtered_attendances->count();
                if($count) {
                    $first_day = $filtered_attendances->first()->created_at->dayOfYear;
                    $attendances = $this->get_filtered_attendances($start, $end, $filtered_attendances, $first_day, $count, $leaves, $holidays);
                }
                else{
                    while($start->lessThan($end)) {
                        $attendances->add($this->attendanceIfNotPresent($start, $leaves, $holidays));
                        $start->addDay();
                    }
                }
                $filter = true;
            }   
        }
        if ($attendances)
            $attendances = $attendances->reverse()->values();
        $data = [
            'employee' => $employee,
            'attendances' => $attendances,
            'filter' => $filter
        ];
        return response()->json($data);
    }

    public function get_filtered_attendances($start, $end, $filtered_attendances, $first_day, $count, $leaves, $holidays) {
        $found_start = false;
        $key = 1;
        $attendances = collect();
        while($start->lessThan($end)) {
            if (!$found_start) {
                if($first_day == $start->dayOfYear()) {
                    $found_start = true;
                    $attendances->add($filtered_attendances->first());
                } else {
                    $attendances->add($this->attendanceIfNotPresent($start, $leaves, $holidays));
                }
            } else {
                if ($key < $count) {
                    if($start->dayOfYear() != $filtered_attendances->get($key)->created_at->dayOfYear) {
                        $attendances->add($this->attendanceIfNotPresent($start, $leaves, $holidays));
                    }
                    else {
                        $attendances->add($filtered_attendances->get($key));
                        $key++;
                    }
                }
                else {
                    $attendances->add($this->attendanceIfNotPresent($start, $leaves, $holidays));
                }
            }
            $start->addDay();
        }

        return response()->json($attendances);
    }

    public function checkLeave($leaves, $date) {
        if ($leaves->count() != 0) {
            $leaves = $leaves->filter(function($leave, $key) use ($date) {
                if($leave->end_date) {
                    $condition1 = intval($date->dayOfYear) >= intval($leave->start_date->dayOfYear);
                    $condition2 = intval($date->dayOfYear) <= intval($leave->end_date->dayOfYear);
                    return $condition1 && $condition2;
                }
                return $date->dayOfYear == $leave->start_date->dayOfYear;
            });
        }
        return $leaves->count();
    }

    public function checkHoliday($holidays, $date) {
        if ($holidays->count() != 0) {
            $holidays = $holidays->filter(function($holiday, $key) use ($date) {
                if($holiday->end_date) {
                    $condition1 = intval($date->dayOfYear) >= intval($holiday->start_date->dayOfYear);
                    $condition2 = intval($date->dayOfYear) <= intval($holiday->end_date->dayOfYear);
                    return $condition1 && $condition2;
                }
                return $date->dayOfYear == $holiday->start_date->dayOfYear;
            });
        }
        return $holidays->count();
    }

    public function attendanceIfNotPresent($start, $leaves, $holidays) {
        $attendance = new Attendance();
        $attendance->created_at = $start;
        if($this->checkHoliday($holidays, $start)) {
            $attendance->registered = 'holiday';
        } elseif($start->dayOfWeek == 0) {
            $attendance->registered = 'sun';
        } elseif($this->checkLeave($leaves, $start)) {
            $attendance->registered = 'leave';
        } else {
            $attendance->registered = 'no';
        }

        return $attendance;
    }

    public function leavesOfRange($leaves, $start, $end) {
        return $leaves->filter(function($leave, $key) use ($start, $end) {
            $condition1 = (intval($start->dayOfYear) <= intval($leave->start_date->dayOfYear)) && (intval($end->dayOfYear) >= intval($leave->start_date->dayOfYear));
            $condition2 = false;
            if($leave->end_date)
                $condition2 = (intval($start->dayOfYear) <= intval($leave->end_date->dayOfYear)) && (intval($end->dayOfYear) >= intval($leave->end_date->dayOfYear));
            $condition3 = $leave->status == 'approved';
            return  ($condition1 || $condition2) && $condition3;
        });
    }

    public function attendanceOfRange($attendances, $start, $end) {
        return $attendances->filter(function($attendance, $key) use ($start, $end) {
                    $date = Carbon::parse($attendance->created_at);
                    if ((intval($date->dayOfYear) >= intval($start->dayOfYear)) && (intval($date->dayOfYear) <= intval($end->dayOfYear)))
                        return true;
                })->values();
    }

    public function holidaysOfRange($holidays, $start, $end) {
        return $holidays->filter(function($holiday, $key) use ($start, $end) {
            $condition1 = (intval($start->dayOfYear) <= intval($holiday->start_date->dayOfYear)) && (intval($end->dayOfYear) >= intval($holiday->start_date->dayOfYear));
            $condition2 = false;
            if($holiday->end_date)
                $condition2 = (intval($start->dayOfYear) <= intval($holiday->end_date->dayOfYear)) && (intval($end->dayOfYear) >= intval($holiday->end_date->dayOfYear));
            return  ($condition1 || $condition2);
        });
    }

}
