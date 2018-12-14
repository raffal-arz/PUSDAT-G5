<?php

namespace App\Http\Controllers\Backend;

use App\AcademicYear;
use App\Http\Helpers\AppHelper;
use App\IClass;
use App\Registration;
use App\Section;
use App\Student;
use App\User;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $classes = IClass::where('status', AppHelper::ACTIVE)
            ->pluck('name', 'id');
        $iclass = null;
        $students = [];
        $sections = [];

        // get query parameter for filter the fetch
        $class_id = $request->query->get('class',0);
        $section_id = $request->query->get('section',0);


        $classInfo = null;
        $sectionInfo = null;
        if($class_id){
            // now check is academic year set or not
            $settings = AppHelper::getAppSettings();
            if(!isset($settings['academic_year']) || (int)($settings['academic_year']) < 1){
                return redirect()->route('student.index')
                    ->with("error",'Academic year not set yet! Please go to settings and set it.')
                    ->withInput();
            }
            $acYear = $settings['academic_year'];

            //get student
            $students = Registration::where('class_id', $class_id)
                ->where('academic_year_id', $acYear)
                ->section($section_id)
                ->with('student')
//                ->with('section')
                ->orderBy('student_id','asc')
                ->get();

            //if section is mention then full this class section list
            if($section_id){
                $sections = Section::where('status', AppHelper::ACTIVE)
                    ->where('class_id', $class_id)
                    ->pluck('name', 'id');

                $sectionInfo =  Section::select('name')->where('id', $section_id)->first();
            }

            $iclass = $class_id;

            $classInfo = IClass::select('name')->where('id',$class_id)->first();
        }

        return view('backend.student.list', compact('students', 'classes', 'iclass', 'sections', 'section_id', 'classInfo', 'sectionInfo'));

    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = IClass::where('status', AppHelper::ACTIVE)
            ->pluck('name', 'id');
        $student = null;
        $gender = 1;
        $religion = 1;
        $bloodGroup = 1;
        $nationality = 'Bangladeshi';
        $group = 'None';
        $shift = 'Day';
        $regiInfo = null;
        $sections = [];
        $iclass = null;
        $section = null;

        // check for institute type and set gender default value
        $settings = AppHelper::getAppSettings();
        if(isset($settings['institute_type']) && intval($settings['institute_type']) == 2){
          $gender = 2;
        }


        return view('backend.student.add', compact(
            'regiInfo',
            'student',
            'gender',
            'religion',
            'bloodGroup',
            'nationality',
            'classes',
            'sections',
            'group',
            'shift',
            'iclass',
            'section'
        ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate form
        $messages = [
            'photo.max' => 'The :attribute size must be under 200kb.',
            'photo.dimensions' => 'The :attribute dimensions min 150 X 150.',
        ];
        $rules = [
            'name' => 'required|min:5|max:255',
            'photo' => 'mimes:jpeg,jpg,png|max:200|dimensions:min_width=150,min_height=150',
            'dob' => 'min:10|max:10',
            'gender' => 'required|integer',
            'religion' => 'nullable|integer',
            'blood_group' => 'nullable|integer',
            'nationality' => 'required|max:50',
            'phone_no' => 'nullable|max:15',
            'extra_activity' => 'nullable|max:15',
            'note' => 'nullable|max:500',
            'father_name' => 'nullable|max:255',
            'father_phone_no' => 'nullable|max:15',
            'mother_name' => 'nullable|max:255',
            'mother_phone_no' => 'nullable|max:15',
            'guardian' => 'nullable|max:255',
            'guardian_phone_no' => 'nullable|max:15',
            'present_address' => 'nullable|max:500',
            'permanent_address' => 'required|max:500',
            'card_no' => 'nullable|min:4|max:50|unique:registrations,card_no',
            'username' => 'nullable|min:5|max:255|unique:users,username',
            'password' => 'nullable|min:6|max:50',
            'email' => 'nullable|email|max:255|unique:students,email',
            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'shift' => 'nullable|max:15',
            'roll_no' => 'nullable|max:20',
            'board_regi_no' => 'nullable|max:50',
            'fourth_subject' => 'nullable|integer',

        ];

        $createUser = false;

        if(strlen($request->get('username',''))){
            $rules['email' ] = 'email|max:255|unique:students,email|unique:users,email';
            $createUser = true;

        }

        $this->validate($request, $rules);

        // now check is academic year set or not
        $settings = AppHelper::getAppSettings();

        if(!isset($settings['academic_year']) || (int)($settings['academic_year']) < 1){
            return redirect()->route('student.create')
                ->with("error",'Academic year not set yet! Please go to settings and set it.')
                ->withInput();
        }

        $data = $request->all();

        if($data['nationality'] == 'Other'){
            $data['nationality']  = $data['nationality_other'];
        }

        $imgStorePath = "public/student/".$request->get('class_id',0);
        if($request->hasFile('photo')) {
            $storagepath = $request->file('photo')->store($imgStorePath);
            $fileName = basename($storagepath);
            $data['photo'] = $fileName;
        }
        else{
            $data['photo'] = $request->get('oldPhoto','');
        }


        DB::beginTransaction();
        try {
            //now create user
            if ($createUser) {
                $user = User::create(
                    [
                        'name' => $data['name'],
                        'username' => $request->get('username'),
                        'email' => $data['email'],
                        'password' => bcrypt($request->get('password')),
                        'remember_token' => null,
                    ]
                );
                //now assign the user to role
                UserRole::create(
                    [
                        'user_id' => $user->id,
                        'role_id' => AppHelper::USER_STUDENT
                    ]
                );
                $data['user_id'] = $user->id;
            }
            // now save employee
            $student = Student::create($data);

            $classInfo = IClass::find($data['class_id']);
            $acYearId = $settings['academic_year'];
            $academicYearInfo = AcademicYear::find($acYearId);
            $regiNo = $academicYearInfo->start_date->format('y') . (string)$classInfo->numeric_value;

            $totalStudent = Registration::where('academic_year_id', $academicYearInfo->id)
                ->where('class_id', $classInfo->id)->count();
            $regiNo .= str_pad(++$totalStudent,3,'0',STR_PAD_LEFT);


            $registrationData = [
                'regi_no' => $regiNo,
                'student_id' => $student->id,
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'academic_year_id' => $academicYearInfo->id,
                'roll_no' => $data['roll_no'],
                'shift' => $data['shift'],
                'card_no' => $data['card_no'],
                'board_regi_no' => $data['board_regi_no'],
                'fourth_subject' => $data['fourth_subject'] ? $data['fourth_subject'] : 0
            ];

            Registration::create($registrationData);

            // now commit the database
            DB::commit();
            $request->session()->flash('message', "Student registration number is ".$regiNo);

            //now notify the admins about this record
            $msg = $data['name']." student added by ".auth()->user()->name;
            $nothing = AppHelper::sendNotificationToAdmins('info', $msg);
            // Notification end

            return redirect()->route('student.create')->with('success', 'Student added!');


        }
        catch(\Exception $e){
            DB::rollback();
            $message = str_replace(array("\r", "\n","'","`"), ' ', $e->getMessage());
            return redirect()->route('student.create')->with("error",$message);
        }

        return redirect()->route('student.create');


    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //get student
        $student = Registration::where('id', $id)
            ->with('student')
            ->with('class')
            ->with('section')
            ->with('acYear')
            ->first();
        if(!$student){
            abort(404);
        }
        $username = '';
        $fourthSubject = $student->fourth_subject ? $student->fourth_subject : '';
        if($student->student->user_id){
            $user = User::find($student->student->user_id);
            $username = $user->username;
        }
        return view('backend.student.view', compact('student', 'username', 'fourthSubject'));


    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $regiInfo = Registration::find($id);
        if(!$regiInfo){
            abort(404);
        }
        $student =  Student::find($regiInfo->student_id);
        if(!$student){
            abort(404);
        }
        $classes = IClass::where('status', AppHelper::ACTIVE)
            ->pluck('name', 'id');
        $sections = Section::where('class_id', $regiInfo->class_id)->where('status', AppHelper::ACTIVE)
            ->pluck('name', 'id');
        $gender = $student->gender;
        $religion = $student->religion;
        $bloodGroup = $student->blood_group;
        $nationality = $student->nationality;
        $shift = $regiInfo->shift;

        $section = $regiInfo->section_id;
        $iclass = $regiInfo->class_id;

        return view('backend.student.add', compact(
            'regiInfo',
            'student',
            'gender',
            'religion',
            'bloodGroup',
            'nationality',
            'classes',
            'sections',
            'shift',
            'iclass',
            'section'
        ));

    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $regiInfo = Registration::find($id);
        if(!$regiInfo){
            abort(404);
        }
        $student =  Student::find($regiInfo->student_id);
        if(!$student){
            abort(404);
        }

        //validate form
        $messages = [
            'photo.max' => 'The :attribute size must be under 200kb.',
            'photo.dimensions' => 'The :attribute dimensions min 150 X 150.',
        ];
        $rules = [
            'name' => 'required|min:5|max:255',
            'photo' => 'mimes:jpeg,jpg,png|max:200|dimensions:min_width=150,min_height=150',
            'dob' => 'min:10|max:10',
            'gender' => 'required|integer',
            'religion' => 'nullable|integer',
            'blood_group' => 'nullable|integer',
            'nationality' => 'required|max:50',
            'phone_no' => 'nullable|max:15',
            'extra_activity' => 'nullable|max:15',
            'note' => 'nullable|max:500',
            'father_name' => 'nullable|max:255',
            'father_phone_no' => 'nullable|max:15',
            'mother_name' => 'nullable|max:255',
            'mother_phone_no' => 'nullable|max:15',
            'guardian' => 'nullable|max:255',
            'guardian_phone_no' => 'nullable|max:15',
            'present_address' => 'nullable|max:500',
            'permanent_address' => 'required|max:500',
            'card_no' => 'nullable|min:4|max:50|unique:registrations,card_no,'.$regiInfo->id,
            'email' => 'nullable|email|max:255|unique:students,email,'.$student->id.'|email|unique:users,email,'.$student->user_id,
//            'class_id' => 'required|integer',
            'section_id' => 'required|integer',
            'shift' => 'nullable|max:15',
            'roll_no' => 'nullable|max:20',
            'board_regi_no' => 'nullable|max:50',
            'fourth_subject' => 'nullable|integer',

        ];


        $this->validate($request, $rules);

        // now check is academic year set or not
        $settings = AppHelper::getAppSettings();
        if(!isset($settings['academic_year']) || (int)($settings['academic_year']) < 1){
            return redirect()->route('student.index')
                ->with("error",'Academic year not set yet! Please go to settings and set it.')
                ->withInput();
        }

        $data = $request->all();

        if($data['nationality'] == 'Other'){
            $data['nationality']  = $data['nationality_other'];
        }

        $imgStorePath = "public/student/".$request->get('class_id',0);
        if($request->hasFile('photo')) {
            $storagepath = $request->file('photo')->store($imgStorePath);
            $fileName = basename($storagepath);
            $data['photo'] = $fileName;

            //if file change then delete old one
            $oldFile = $request->get('oldPhoto','');
            if( $oldFile != ''){
                $file_path = $imgStorePath.'/'.$oldFile;
                Storage::delete($file_path);
            }
        }
        else{
            $data['photo'] = $request->get('oldPhoto','');
        }



        $registrationData = [
//            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'],
            'roll_no' => $data['roll_no'],
            'shift' => $data['shift'],
            'card_no' => $data['card_no'],
            'board_regi_no' => $data['board_regi_no'],
            'fourth_subject' => $data['fourth_subject'] ? $data['fourth_subject'] : 0
        ];

        // now check if student academic information changed, if so then log it
        $isChanged = false;
        $logData = [];
        $timeNow = Carbon::now();
        if($regiInfo->section_id != $data['section_id']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'section',
                'meta_value' => $regiInfo->section_id,
                'created_at' => $timeNow,

            ];
        }
        if($regiInfo->roll_no != $data['roll_no']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'roll no',
                'meta_value' => $regiInfo->roll_no,
                'created_at' => $timeNow,

            ];
        }


        if($regiInfo->shift != $data['shift']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'shift',
                'meta_value' => $regiInfo->shift,
                'created_at' => $timeNow,

            ];
        }

        if($regiInfo->card_no != $data['card_no']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'card no',
                'meta_value' => $regiInfo->card_no,
                'created_at' => $timeNow,

            ];
        }
        if($regiInfo->board_regi_no != $data['board_regi_no']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'board regi no',
                'meta_value' => $regiInfo->board_regi_no,
                'created_at' => $timeNow,

            ];
        }
        if($regiInfo->fourth_subject != $data['fourth_subject']){
            $isChanged = true;
            $logData[] = [
                'student_id' => $regiInfo->student_id,
                'academic_year_id' => $regiInfo->academic_year_id,
                'meta_key' => 'fourth subject',
                'meta_value' => $regiInfo->fourth_subject,
                'created_at' => $timeNow,

            ];
        }

        $message = 'Something went wrong!';
        DB::beginTransaction();
        try {

            // save registration data
            $regiInfo->fill($registrationData);
            $regiInfo->save();

            // now save student
            $student->fill($data);
            $student->save();

            //if have changes then insert log
            if($isChanged){
                DB::table('student_info_log')->insert($logData);
            }
            // now commit the database
            DB::commit();

            return redirect()->route('student.index', ['class' => $regiInfo->class_id])->with('success', 'Student updated!');


        }
        catch(\Exception $e){
            DB::rollback();
            $message = str_replace(array("\r", "\n","'","`"), ' ', $e->getMessage());
//            dd($message);
        }

        return redirect()->route('student.edit', $regiInfo->id)->with("error",$message);;



    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $registration = Registration::find($id);
        if(!$registration){
           abort(404);
        }
        $student =  Student::find($registration->student_id);
        if(!$student){
            abort(404);
        }

        $message = 'Something went wrong!';
        DB::beginTransaction();
        try {

            $registration->delete();
            $student->delete();
            if($student->user_id){
                $user = User::find($student->user_id);
                $user->delete();
            }
            DB::commit();


            //now notify the admins about this record
            $msg = $student->name." student deleted by ".auth()->user()->name;
            $nothing = AppHelper::sendNotificationToAdmins('info', $msg);
            // Notification end

            return redirect()->route('student.index')->with('success', 'Student deleted.');

        }
        catch(\Exception $e){
            DB::rollback();
            $message = str_replace(array("\r", "\n","'","`"), ' ', $e->getMessage());
        }
        return redirect()->route('student.index')->with('error', $message);

    }

    /**
     * status change
     * @return mixed
     */
    public function changeStatus(Request $request, $id=0)
    {

        $registration = Registration::find($id);
        if(!$registration){
            return [
                'success' => false,
                'message' => 'Record not found!'
            ];
        }
        $student =  Student::find($registration->student_id);
        if(!$student){
            return [
                'success' => false,
                'message' => 'Record not found!'
            ];
        }

        $student->status = (string)$request->get('status');
        $registration->status = (string)$request->get('status');
        if($student->user_id){
            $user = User::find($student->user_id);
            $user->status = (string)$request->get('status');
        }

        $message = 'Something went wrong!';
        DB::beginTransaction();
        try {

            $registration->save();
            $student->save();
            if($student->user_id) {
                $user->save();
            }
            DB::commit();

            return [
                'success' => true,
                'message' => 'Status updated.'
            ];


        }
        catch(\Exception $e){
            DB::rollback();
            $message = str_replace(array("\r", "\n","'","`"), ' ', $e->getMessage());
        }

        return [
            'success' => false,
            'message' => $message
        ];


    }
}
