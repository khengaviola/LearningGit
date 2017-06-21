@extends('layouts.master')

@section('title')
    {{"Job Order"}}
@stop

@section('style')
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('assets/datatables/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('assets/datatables/datatables-responsive/css/dataTables.responsive.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('assets/plugins/fullcalendar/fullcalendar.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('assets/plugins/select2/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('assets/plugins/pace/pace.min.css') }}">
@stop

@section('content')
    <div id="jobCarousel" class="carousel slide">
        <div class="carousel-inner" role="listbox">
            <div id="jobIndex" class="item active">
                <div class="col-md-4">
                    <div id="actionBox" class="box box-primary box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title">Actions</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="box-body">
                            <button id="addNew" class="btn btn-success btn-block">
                                <i class="glyphicon glyphicon-plus"></i> Add New
                            </button>
                            <button id="viewMonth" class="btn btn-primary btn-block disabled">
                                <i class="fa fa-calendar"></i> Month View
                            </button>
                            <button id="viewWeek" class="btn btn-warning btn-block">
                                <i class="fa fa-calendar-minus-o"></i> Week View
                            </button>
                            <button id="viewDay" class="btn btn-danger btn-block">
                                <i class="fa fa-calendar-o"></i> Day View
                            </button>
                            <button id="viewTable" class="btn btn-info btn-block" href="#tabularTab" aria-controls="tabularTab" role="tab" data-toggle="tab">
                                <i class="fa fa-exchange"></i> Tabular View
                            </button>
                            <button id="viewCalendar" class="btn btn-info btn-block hidden" href="#calendarTab" aria-controls="calendarTab" role="tab" data-toggle="tab">
                                <i class="fa fa-exchange"></i> Calendar View
                            </button>
                        </div>
                    </div>
                    <div id="detailBox" class="box box-info hidden">
                        <div class="box-header with-border">
                            <h3 class="box-title">Job Order Details</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="box-body" style="font-size: 15px">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Job Id:</label> <span id="detailId"></span>
                                </div>
                                <div class="col-md-6">
                                    <a id="detailPDF" href="" type="button" class="btn btn-primary btn-sm hidden" data-toggle="tooltip" data-placement="top" title="View PDF">
                                        <i class="glyphicon glyphicon-eye-open"></i>
                                    </a>
                                    <a id="detailUpdate" href="" type="button" class="btn btn-primary btn-sm hidden" data-toggle="tooltip" data-placement="top" title="Update record">
                                        <i class="glyphicon glyphicon-edit"></i>
                                    </a>
                                    <button onclick="" id="detailProcess" type="button" class="btn btn-success btn-sm hidden" data-toggle="tooltip" data-placement="top" title="Process record">
                                        <i class="glyphicon glyphicon-tasks"></i>
                                    </button>
                                    <button onclick="" id="detailFinalize" type="button" class="btn btn-success btn-sm hidden" data-toggle="tooltip" data-placement="top" title="Finalize record">
                                        <i class="glyphicon glyphicon-check"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 pull-left">
                                    <label>Start:</label><br><span id="detailStart"></span>
                                </div>
                                <div class="col-md-6 pull-right">
                                    <label>End:</label><br><span id="detailEnd"></span>
                                </div>
                            </div>
                            <label>Vehicle:</label><br>
                            <ul>
                                <li>Plate: <span id="detailPlate"></span></li>
                                <li>Model: <span id="detailModel"></span></li>
                                <li>Mileage: <span id="detailMileage"></span></li>
                            </ul>
                            <label>Customer:</label> <span id="detailCustomer"></span><br>
                            <label>Technician(s):</label>
                            <ul id="detailTechs"></ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade in active" id="calendarTab">
                            <div class="box box-primary">
                                <div class="box-body no-padding">
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="tabularTab">
                            <div class="box box-primary">
                                <div class="box-body dataTable_wrapper">
                                    <table id="list" class="table table-striped responsive">
                                        <thead>
                                            <tr>
                                                <th>Vehicle</th>
                                                <th>Customer</th>
                                                <th class="pull-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($jobs as $job)
                                                <tr>
                                                    <td>
                                                        <li>Plate: {{$job->plate}}</li>
                                                        <li>Model: {{$job->make}} - {{$job->year}} {{$job->model}} ({{$job->transmission}})</li>
                                                        @if($job->mileage!=null)
                                                        <li>Mileage: {{$job->mileage}}</li>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <li>Name: {{$job->firstName}} {{$job->middleName}} {{$job->lastName}}</li>
                                                        <li>Address: {{$job->street}} {{$job->brgy}} {{$job->city}}</li>
                                                        <li>Contact No.: {{$job->contact}}</li>
                                                        @if($job->email!=null)
                                                        <li>{{$job->email}}</li>
                                                        @endif
                                                    </td>
                                                    <td class="pull-right">
                                                        @if(!$job->isFinalize)
                                                            <button onclick="finalizeModal('{{$job->jobId}}')" type="button" class="btn btn-success btn-sm"  data-toggle="tooltip" data-placement="top" title="Finalize record">
                                                                <i class="glyphicon glyphicon-check"></i>
                                                            </button>
                                                            <a href="{{url('/job/'.$job->jobId.'/edit')}}" type="button" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Update record">
                                                                <i class="glyphicon glyphicon-edit"></i>
                                                            </a>
                                                            {!! Form::open(['method'=>'patch','action' => ['JobController@finalize',$job->jobId],'id'=>'fin'.$job->jobId]) !!}
                                                            {!! Form::close() !!}
                                                        @else
                                                            <a href="{{url('/job/pdf/'.$job->jobId)}}" type="button" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="View PDF">
                                                                <i class="glyphicon glyphicon-eye-open"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="jobForm" class="item">
                {!! Form::open(['url' => 'job']) !!}
                {!! Form::hidden('start',null,[
                    'class' => 'form-control',
                    'id' => 'start']) 
                !!}
                @include('job.formCreate')
                {!! Form::close() !!}
            </div>
            <div id="processForm" class="item">
                <div class="col-md-8">
                    <button id="backProcess" type="button" class="btn btn-success btn-md"><i class="fa fa-angle-double-left"></i> Back</button><br><br>
                    <div id="processBox" class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Process Job Order</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                                <i class="fa fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="box-body">
                            <label>Progress:</label>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;width: 0%;">
                                    0% Complete
                                </div>
                            </div><br>
                            <div class="dataTable_wrapper">
                                <table id="processList" class="table table-striped responsive">
                                    <thead>
                                        <tr>
                                            <th width="5%"></th>
                                            <th>Item</th>
                                            <th width="10%" class="text-right">Quantity</th>
                                            <th width="10%" class="text-right">Completed</th>
                                            <th class="text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Finalize --}}
            <div id="finalizeModal" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span></button>
                            <h4 class="modal-title">Finalize</h4>
                        </div>
                        <div class="modal-body">
                            <div style="text-align:center">Are you sure you want to finalize this record?</div>
                            <br>
                            <div class="dataTable_wrapper">
                                <table id="finalizeList" class="table table-striped responsive">
                                    <thead>
                                        <tr>
                                            <th width="10%" class="text-right">Quantity</th>
                                            <th>Item</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                            <button id="finalize" type="button" class="btn btn-success">Finalize</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('script')
    <script src="{{ URL::asset('assets/plugins/pace/pace.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/select2/select2.full.min.js') }}"></script>
    <script src="{{ URL::asset('assets/datatables/datatables/media/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/datatables/datatables-plugins/integration/bootstrap/3/dataTables.bootstrap.min.js') }}"></script>
    <script src="{{ URL::asset('assets/datatables/datatables-responsive/js/dataTables.responsive.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fullcalendar/moment.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fullcalendar/fullcalendar.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/input-mask/inputmask.js')}}"></script>
    <script src="{{ URL::asset('assets/plugins/input-mask/inputmask.extensions.js')}}"></script>
    <script src="{{ URL::asset('assets/plugins/input-mask/inputmask.numeric.extensions.js')}}"></script>
    <script src="{{ URL::asset('assets/plugins/input-mask/inputmask.phone.extensions.js')}}"></script>
    <script src="{{ URL::asset('assets/plugins/input-mask/jquery.inputmask.js')}}"></script>
    <script src="{{ URL::asset('js/customer.js') }}"></script>
    <script src="{{ URL::asset('js/job.js') }}"></script>
    <script src="{{ URL::asset('js/jobFinal.js') }}"></script>
    <script src="{{ URL::asset('js/jobProcess.js') }}"></script>
    <script src="{{ URL::asset('js/jobCalendar.js') }}"></script>
    <script>
        $(document).ready(function (){
            $('#list').DataTable({
                responsive: true,
            });
            $('#tJob').addClass('active');
            var customers = [
                @foreach($customers as $customer)
                    '{{$customer->firstName}} {{$customer->middleName}} {{$customer->lastName}}',
                @endforeach
            ];    
            var activeTechnicians = [
                @if(old('technician'))
                    @foreach(old('technician') as $technician)
                        "{{$technician}}",
                    @endforeach
                @endif
            ];
            @foreach($jobs as $job)
                event = {
                    id: {{$job->jobId}},
                    title: '{{$job->plate}}',
                    start: '{{$job->start}}',
                    @if($job->isComplete && $job->total==$job->paid)
                        color: '#00a65a'
                    @elseif(!$job->isComplete && $job->isFinalize && $job->total==$job->paid)
                        color: '#00c0ef'
                    @elseif(!$job->isComplete && $job->isFinalize && $job->total!=$job->paid)
                        color: '#f39c12'
                    @else
                        color: '#3c8dbc'
                    @endif
                };
                $('#calendar').fullCalendar('renderEvent', event);
            @endforeach        
            $("#technician").val(activeTechnicians);
            @if(old('modelId'))
                $("#model").val({{old('modelId')}});
            @endif
            $(".select2").select2();
            $('#firstName').autocomplete({source: customers});
            @if(!old('contact'))
                $('#contact').inputmask("+63 999 9999 999");
            @else
                $('#jobCarousel').carousel(1);
                @if(old('contact')[2] == '2' && old('contact')[14] == 'l')
                    $('#contact').inputmask("(02) 999 9999 loc. 9999");
                @elseif(old('contact')[2] == '2')
                    $('#contact').inputmask("(02) 999 9999");
                @else
                    $('#contact').inputmask("+63 999 9999 999");
                @endif
            @endif
            @if(!old('plate'))
                $('#plate').inputmask("AAA 999");
            @else
                @if(strlen(old('plate')) == 7)
                    $('#plate').inputmask("AAA 999");
                @elseif(strlen(old('plate')) == 8)
                    $('#plate').inputmask("AAA 9999");
                @elseif(strlen(old('plate')) == 6)
                    @if(old('plate')[3] != ' ')
                        $('#plate').inputmask("AA 9999");
                    @else
                        $('#plate').inputmask("AAA 99");
                    @endif
                @elseif(strlen(old('plate')) == 1)
                    $('#plate').inputmask("9");
                @else
                    $('#plate').inputmask();
                    $('#plate').val("For Registration");
                @endif
            @endif
        });
    </script>
    @include('layouts.oldItems')
@stop