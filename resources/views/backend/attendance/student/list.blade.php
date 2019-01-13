<!-- Master page  -->
@extends('backend.layouts.master')

<!-- Page title -->
@section('pageTitle') Student Attendance @endsection
<!-- End block -->


<!-- BEGIN PAGE CONTENT-->
@section('pageContent')
    <!-- Section header -->
    <section class="content-header">
        <h1>
            Student Attendance
            <small>List</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{URL::route('user.dashboard')}}"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><i class="fa icon-attendance"></i> Attendance</li>
            <li class="active">Student</li>
        </ol>
    </section>
    <!-- ./Section header -->
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header">
                        @if(AppHelper::getInstituteCategory() == 'college')
                            <div class="col-md-3">
                                <div class="form-group has-feedback">
                                    {!! Form::select('academic_year', $academic_years, $acYear , ['placeholder' => 'Pick a year...','class' => 'form-control select2', 'required' => 'true']) !!}
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group has-feedback">
                                {!! Form::select('class_id', $classes, $class_id , ['placeholder' => 'Pick a class...','class' => 'form-control select2', 'required' => 'true']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group has-feedback">
                                {!! Form::select('section_id', $sections, $section_id , ['placeholder' => 'Pick a section...','class' => 'form-control select2', 'id' => 'student_list_filter', 'required' => 'true']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group has-feedback">
                                <input type='text' class="form-control date_picker"   name="attendance_date" placeholder="date" value="{{$attendance_date}}" required minlength="10" maxlength="11" />
                            </div>
                        </div>
                        <div class="box-tools pull-right">
                            <a class="btn btn-info btn-sm" href="{{ URL::route('student_attendance.create') }}"><i class="fa fa-plus-circle"></i> Add New</a>
                            <a class="btn btn-primary btn-sm" href="{{ URL::route('student_attendance.create_file') }}"><i class="fa fa-upload"></i> Upload File</a>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="listDataTableWithSearch" class="table table-bordered table-striped list_view_table display responsive no-wrap" width="100%">
                                <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="50%">Name</th>
                                    <th width="15%">Regi. No.</th>
                                    <th width="15%">Roll No.</th>
                                    <th width="20%">Status</th>
                                    <th class="notexport" width="15%">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attendances as $attendance)
                                    <tr>
                                        <td>
                                            {{$loop->iteration}}
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <!-- todo: have problem in mobile device -->
                                            <input class="statusChange" type="checkbox" data-pk="{{$attendance->id}}" @if($attendance->status) checked @endif data-toggle="toggle" data-on="<i class='fa fa-check-circle'></i>" data-off="<i class='fa fa-ban'></i>" data-onstyle="success" data-offstyle="danger">
                                        </td>

                                    </tr>
                                @endforeach

                                </tbody>
                                <tfoot>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="50%">Name</th>
                                    <th width="10%">Regi. No.</th>
                                    <th width="10%">Roll No.</th>
                                    <th width="10%">Status</th>
                                    <th class="notexport" width="15%">Action</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>

    </section>
    <!-- /.content -->
@endsection
<!-- END PAGE CONTENT-->

<!-- BEGIN PAGE JS-->
@section('extraScript')
    <script type="text/javascript">
        $(document).ready(function () {
            window.postUrl = '{{URL::Route("student_attendance.status", 0)}}';
            window.section_list_url = '{{URL::Route("academic.section")}}';
            window.changeExportColumnIndex = 4;
            window.excludeFilterComlumns = [0,5];
            Academic.attendanceInit();
            $('title').text($('title').text() + '-' + $('select[name="class_id"] option[selected]').text() + '(' + $('select[name="section_id"] option[selected]').text() +')');
        });
    </script>
@endsection
<!-- END PAGE JS-->
